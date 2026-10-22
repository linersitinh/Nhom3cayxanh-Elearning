<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Throwable;

class ElearnController extends Controller
{
    public function home() { return view('elearn'); }

    public function health(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            return response()->json(['ok'=>true,'app'=>'E-Learn 3cayxanh','version'=>'5.0-laravel','database'=>DB::connection()->getDriverName(),'counts'=>[
                'users'=>User::count(),'courses'=>Course::count(),'enrollments'=>Enrollment::count(),'lessons'=>Lesson::count(),'assignments'=>Assignment::count(),'submissions'=>Submission::count(),'grades'=>Grade::count(),
            ]]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok'=>false,'error'=>'Không kết nối được cơ sở dữ liệu. Kiểm tra cấu hình .env và MySQL.'],503);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $key = 'elearn-login:'.$request->ip().'|'.mb_strtolower((string)$request->input('identifier'));
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return response()->json(['error'=>'Bạn đã đăng nhập sai quá nhiều lần. Hãy thử lại sau một phút.'],429);
        }
        $data = $request->validate(['identifier'=>['required','string','max:190'],'password'=>['required','string','max:200']]);
        $user = User::query()->where('email', $data['identifier'])->orWhere('user_code', $data['identifier'])->first();
        if (!$user || $user->status !== 'Hoạt động' || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            return response()->json(['error'=>'Thông tin đăng nhập không hợp lệ hoặc tài khoản đang bị khóa.'],422);
        }
        Auth::login($user);
        $request->session()->regenerate();
        RateLimiter::clear($key);
        return response()->json(['ok'=>true,'user'=>$this->publicUser($user),'csrfToken'=>$request->session()->token()]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok'=>true,'csrfToken'=>$request->session()->token()]);
    }

    public function session(Request $request): JsonResponse
    {
        return response()->json(['ok'=>true,'user'=>$this->publicUser($request->user()),'expiresIn'=>7200]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json($this->buildState($request->user()));
    }

    public function updateState(Request $request): JsonResponse
    {
        $actor = $request->user();
        $payload = $request->validate([
            'users'=>'required|array','courses'=>'required|array','assignments'=>'required|array','grades'=>'required|array','enrollments'=>'required|array',
        ]);
        try {
            DB::transaction(function () use ($actor, $payload) {
                if ($actor->isStudent()) $this->syncStudent($actor, $payload);
                elseif ($actor->isTeacher()) $this->syncTeacher($actor, $payload);
                elseif ($actor->isAdmin()) $this->syncAdmin($actor, $payload);
                else abort(403, 'Vai trò không hợp lệ.');
            }, 3);
            return response()->json(['ok'=>true,'message'=>'Dữ liệu đã được lưu vào MySQL.','state'=>$this->buildState($actor)]);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (ValidationException $e) {
            return response()->json(['error'=>'Dữ liệu không hợp lệ.','errors'=>$e->errors()],422);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error'=>$e->getMessage() ?: 'Thao tác bị từ chối.'], $e->getStatusCode());
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error'=>$e instanceof \Illuminate\Database\QueryException ? 'Dữ liệu vi phạm ràng buộc cơ sở dữ liệu hoặc bị trùng.' : 'Không thể lưu dữ liệu. Kiểm tra thông tin và quyền truy cập.'],422);
        }
    }

    public function courseSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xem báo cáo này.');
        return response()->json(['items'=>DB::table('v_course_summary')->orderBy('course_id')->get()]);
    }

    public function submissionSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xem báo cáo này.');
        return response()->json(['items'=>DB::table('v_submission_summary')->orderBy('assignment_id')->get()]);
    }

    public function schema(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xem schema.');
        $tables = DB::select("SELECT TABLE_NAME AS name, TABLE_TYPE AS type FROM information_schema.tables WHERE table_schema = DATABASE() AND TABLE_TYPE IN ('BASE TABLE','VIEW') ORDER BY TABLE_TYPE,TABLE_NAME");
        return response()->json(['tables'=>$tables]);
    }

    public function resetDemo(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ quản trị viên được đặt lại dữ liệu demo.');
        DB::transaction(function () {
            foreach (['grades','submissions','assignments','lessons','enrollments','courses','users'] as $table) DB::table($table)->delete();
            app(\Database\Seeders\DatabaseSeeder::class)->run();
        });
        return response()->json(['ok'=>true,'state'=>$this->buildState($request->user())]);
    }

    private function publicUser(User $user): array
    {
        return ['id'=>(int)$user->user_id,'name'=>$user->full_name,'code'=>$user->user_code,'email'=>$user->email,'role'=>$user->role,'status'=>$user->status];
    }

    private function buildState(User $actor): array
    {
        $allCourses = Course::with(['teacher','lessons','enrollments'])->orderBy('course_id')->get();
        $enrolledCourseIds = Enrollment::where('student_id',$actor->user_id)->pluck('course_id')->map(fn($v)=>(int)$v)->all();
        $ownCourseIds = $actor->isTeacher() ? $allCourses->where('teacher_id',$actor->user_id)->pluck('course_id')->map(fn($v)=>(int)$v)->all() : [];
        $courses = [];
        foreach ($allCourses as $course) {
            if ($actor->isTeacher() && (int)$course->teacher_id !== (int)$actor->user_id) continue;
            $progresses = $course->enrollments->pluck('progress')->map(fn($v)=>(int)$v);
            $ownEnrollment = $course->enrollments->firstWhere('student_id',$actor->user_id);
            $lessons = $course->lessons->map(fn($l)=>['id'=>(int)$l->lesson_id,'title'=>$l->title,'duration'=>$l->duration,'type'=>$l->lesson_type])->values()->all();
            if ($actor->isStudent() && !in_array((int)$course->course_id,$enrolledCourseIds,true)) $lessons = [];
            $courses[] = ['id'=>(int)$course->course_id,'code'=>$course->course_code,'name'=>$course->course_name,'teacher'=>$course->teacher?->full_name ?? 'Chưa phân công','teacherId'=>(int)$course->teacher_id,'term'=>$course->term,'students'=>$course->enrollments->count(),'progress'=>$actor->isStudent() ? (int)($ownEnrollment?->progress ?? 0) : ($progresses->count() ? (int)round($progresses->avg()) : 0),'color'=>$course->color,'symbol'=>$course->symbol,'category'=>$course->category,'description'=>$course->description,'lessons'=>$lessons];
        }

        $assignmentQuery = Assignment::with(['course','submissions.grade'])->orderBy('assignment_id');
        if ($actor->isTeacher()) $assignmentQuery->whereIn('course_id',$ownCourseIds);
        if ($actor->isStudent()) $assignmentQuery->whereIn('course_id',$enrolledCourseIds);
        $assignments = [];
        foreach ($assignmentQuery->get() as $a) {
            $subs = [];
            foreach ($a->submissions as $s) {
                if ($actor->isStudent() && (int)$s->student_id !== (int)$actor->user_id) continue;
                $subs[] = ['userId'=>(int)$s->student_id,'submitted'=>$s->submitted_at?->format('Y-m-d'),'answer'=>$s->answer,'score'=>$s->grade ? (float)$s->grade->score : null,'feedback'=>$s->grade?->feedback ?? ''];
            }
            $own = collect($subs)->firstWhere('userId',(int)$actor->user_id);
            $status = !$a->is_open ? 'Chưa mở' : ($actor->isStudent() ? (($own && $own['score'] !== null) ? 'Đã chấm' : ($own ? 'Đã nộp' : 'Chưa nộp')) : (count($subs) ? 'Đã nộp' : 'Chưa nộp'));
            $assignments[] = ['id'=>(int)$a->assignment_id,'courseId'=>(int)$a->course_id,'title'=>$a->title,'type'=>$a->assignment_type,'due'=>$a->due_date->format('Y-m-d'),'points'=>(float)$a->max_points,'status'=>$status,'submissions'=>$subs,'description'=>$a->description];
        }

        $gradeQuery = Grade::query()->orderBy('grade_id');
        if ($actor->isStudent()) $gradeQuery->where('student_id',$actor->user_id);
        if ($actor->isTeacher()) $gradeQuery->whereIn('course_id',$ownCourseIds);
        $grades = $gradeQuery->get()->map(fn($g)=>['userId'=>(int)$g->student_id,'courseId'=>(int)$g->course_id,'work'=>$g->work_title,'score'=>(float)$g->score,'max'=>(float)$g->max_score,'feedback'=>$g->feedback ?? '', 'assignmentId'=>$g->assignment_id ? (int)$g->assignment_id : null])->all();

        $enrollmentQuery = Enrollment::query()->orderBy('course_id')->orderBy('student_id');
        if ($actor->isStudent()) $enrollmentQuery->where('student_id',$actor->user_id);
        if ($actor->isTeacher()) $enrollmentQuery->whereIn('course_id',$ownCourseIds);
        $enrollments = $enrollmentQuery->get()->map(fn($e)=>['courseId'=>(int)$e->course_id,'studentId'=>(int)$e->student_id,'progress'=>(int)$e->progress])->all();

        $visibleUserIds = [];
        if ($actor->isAdmin()) $users = User::orderBy('user_id')->get();
        elseif ($actor->isStudent()) $users = collect([$actor]);
        else {
            $visibleUserIds = array_unique(array_merge([(int)$actor->user_id], Enrollment::whereIn('course_id',$ownCourseIds)->pluck('student_id')->map(fn($v)=>(int)$v)->all()));
            $users = User::whereIn('user_id',$visibleUserIds)->orderBy('user_id')->get();
        }
        return ['users'=>$users->map(fn($u)=>$this->publicUser($u))->values()->all(),'courses'=>$courses,'assignments'=>$assignments,'grades'=>$grades,'enrollments'=>$enrollments];
    }

    private function assertUserSnapshot(array $payload, array $current): void
    {
        $submitted = collect($payload['users'] ?? [])->sortBy('id')->values()->all();
        $stored = collect($current['users'] ?? [])->sortBy('id')->values()->all();
        if ($submitted != $stored) abort(403, 'Không được tự sửa danh sách người dùng.');
    }

    private function indexById(array $rows): array
    {
        $index=[];
        foreach ($rows as $row) { if (!is_array($row) || !isset($row['id']) || !is_numeric($row['id'])) abort(422,'Danh sách chứa bản ghi không hợp lệ.'); $index[(string)$row['id']]=$row; }
        return $index;
    }

    private function syncStudent(User $actor, array $p): void
    {
        $current=$this->buildState($actor); $this->assertUserSnapshot($p,$current);
        $oldEnroll=[]; foreach($current['enrollments'] as $e)$oldEnroll[$e['courseId'].':'.$e['studentId']]=$e;
        $newEnroll=[]; foreach($p['enrollments'] as $e){if((int)($e['studentId']??0)!==(int)$actor->user_id)abort(403,'Sinh viên chỉ được đăng ký bằng tài khoản của mình.');$key=(int)($e['courseId']??0).':'.(int)$e['studentId'];$newEnroll[$key]=$e;}
        foreach($oldEnroll as $key=>$old){if(!isset($newEnroll[$key]))abort(403,'Không được xóa đăng ký học phần đã tồn tại.');if((int)($newEnroll[$key]['progress']??-1)!==(int)$old['progress']){ $progress=filter_var($newEnroll[$key]['progress']??null,FILTER_VALIDATE_INT);if($progress===false||$progress<0||$progress>100)abort(422,'Tiến độ phải từ 0 đến 100.');[$cid]=$this->splitEnrollmentKey($key);Enrollment::where('course_id',$cid)->where('student_id',$actor->user_id)->update(['progress'=>$progress]);}}
        foreach(array_diff_key($newEnroll,$oldEnroll) as $e){$cid=(int)$e['courseId'];$progress=filter_var($e['progress']??0,FILTER_VALIDATE_INT);if($progress===false||$progress<0||$progress>100)abort(422,'Tiến độ phải từ 0 đến 100.');if(!Course::where('course_id',$cid)->exists())abort(422,'Lớp học phần không tồn tại.');Enrollment::create(['course_id'=>$cid,'student_id'=>$actor->user_id,'progress'=>$progress,'enrolled_at'=>now()]);}
        $currentAssignments=$this->indexById($current['assignments']);$incomingAssignments=$this->indexById($p['assignments']);
        if(array_diff_key($currentAssignments,$incomingAssignments))abort(403,'Không được xóa bài tập khỏi dữ liệu gửi lên.');
        if(array_diff_key($incomingAssignments,$currentAssignments))abort(403,'Sinh viên không được tự tạo bài tập hoặc gửi bài vào lớp chưa đăng ký.');
        foreach($currentAssignments as $aid=>$old){$incoming=$incomingAssignments[$aid];foreach(['courseId','title','type','due','points','description'] as $f)if(!$this->sameValue($incoming[$f]??null,$old[$f]??null))abort(403,'Sinh viên không được sửa nội dung bài tập.') ;
            $subs=$incoming['submissions']??null;if(!is_array($subs)||count($subs)>1)abort(403,'Sinh viên chỉ được cập nhật bài nộp của mình.');foreach($subs as $sub)if((int)($sub['userId']??0)!==(int)$actor->user_id)abort(403,'Sinh viên chỉ được cập nhật bài nộp của mình.');
            $oldSub=collect($old['submissions'])->firstWhere('userId',(int)$actor->user_id);$newSub=$subs[0]??null;
            if($oldSub && !$newSub)abort(403,'Không được xóa bài nộp đã gửi.');
            if(!$oldSub && $newSub){if(($newSub['score']??null)!==null || trim((string)($newSub['feedback']??''))!=='')abort(403,'Sinh viên không được tự chấm điểm hoặc thêm nhận xét của giảng viên.');$assignment=Assignment::findOrFail((int)$aid);if(!$assignment->is_open)abort(403,'Bài tập chưa mở.');if(!Enrollment::where('course_id',$assignment->course_id)->where('student_id',$actor->user_id)->exists())abort(403,'Sinh viên chưa đăng ký lớp học phần.');$answer=trim((string)($newSub['answer']??''));if($answer==='')abort(422,'Nội dung bài nộp không được để trống.');Submission::create(['assignment_id'=>$assignment->assignment_id,'student_id'=>$actor->user_id,'submitted_at'=>now(),'answer'=>$answer]);}
            if($oldSub && $newSub){if(!$this->sameValue($newSub['score']??null,$oldSub['score']??null)||($newSub['feedback']??'')!==($oldSub['feedback']??''))abort(403,'Sinh viên không được tự sửa điểm hoặc nhận xét.');if(($newSub['answer']??'')!==($oldSub['answer']??'')){if($oldSub['score']!==null)abort(403,'Không thể sửa bài đã được chấm.');$answer=trim((string)$newSub['answer']);if($answer==='')abort(422,'Nội dung bài nộp không được để trống.');Submission::where('assignment_id',$aid)->where('student_id',$actor->user_id)->update(['answer'=>$answer,'submitted_at'=>now()]);}}
        }
        if(($p['grades']??[])!=$current['grades'])abort(403,'Sinh viên không được thay đổi điểm.');
    }

    private function splitEnrollmentKey(string $key): array { return array_map('intval',explode(':',$key)); }

    private function sameValue(mixed $left, mixed $right): bool
    {
        if (is_numeric($left) && is_numeric($right)) return abs((float)$left - (float)$right) < 0.000001;
        return $left === $right;
    }

    private function syncTeacher(User $actor, array $p): void
    {
        $current=$this->buildState($actor);$this->assertUserSnapshot($p,$current);
        if(($p['enrollments']??[])!=$current['enrollments'])abort(403,'Giảng viên không được sửa đăng ký học phần.');
        $oldCourses=$this->indexById($current['courses']);$inCourses=$this->indexById($p['courses']);if(array_diff_key($oldCourses,$inCourses))abort(403,'Không được xóa lớp học phần.');
        $courseMap=[];
        foreach($inCourses as $clientId=>$c){
            if((int)($c['teacherId']??0)!==(int)$actor->user_id)abort(403,'Giảng viên chỉ quản lý lớp do mình phụ trách.');
            if(isset($oldCourses[$clientId])){ $old=$oldCourses[$clientId];foreach(['code','name','teacherId','term','category','description','color','symbol'] as $f)if(!$this->sameValue($c[$f]??null,$old[$f]??null))abort(403,'Không được sửa thông tin lớp học phần hiện có qua bản demo.');$course=Course::findOrFail((int)$clientId);$oldLessons=$old['lessons']??[];$lessons=$c['lessons']??[];if(count($lessons)<count($oldLessons)||array_slice($lessons,0,count($oldLessons))!=$oldLessons)abort(403,'Không được sửa hoặc xóa bài học đã có.');foreach(array_slice($lessons,count($oldLessons)) as $i=>$lesson){$title=trim((string)($lesson['title']??''));if($title==='')abort(422,'Tên bài học không được để trống.');Lesson::create(['course_id'=>$course->course_id,'title'=>$title,'duration'=>(string)($lesson['duration']??''),'lesson_type'=>(string)($lesson['type']??'Tài liệu'),'lesson_order'=>$course->lessons()->count()+1]);}$courseMap[$clientId]=(int)$course->course_id;
            } else { $code=trim((string)($c['code']??''));$name=trim((string)($c['name']??''));if($code===''||$name==='')abort(422,'Mã và tên lớp học phần là bắt buộc.');$course=Course::create(['course_code'=>$code,'course_name'=>$name,'teacher_id'=>$actor->user_id,'term'=>(string)($c['term']??'Học kỳ 1'),'category'=>(string)($c['category']??'Khác'),'description'=>(string)($c['description']??''),'color'=>(string)($c['color']??'cover1'),'symbol'=>(string)($c['symbol']??'DB')]);$courseMap[$clientId]=(int)$course->course_id;foreach(($c['lessons']??[]) as $i=>$lesson){Lesson::create(['course_id'=>$course->course_id,'title'=>(string)$lesson['title'],'duration'=>(string)($lesson['duration']??''),'lesson_type'=>(string)($lesson['type']??'Tài liệu'),'lesson_order'=>$i+1]);}}
        }
        $oldAssignments=$this->indexById($current['assignments']);$inAssignments=$this->indexById($p['assignments']);if(array_diff_key($oldAssignments,$inAssignments))abort(403,'Không được xóa bài tập.');$assignmentMap=[];
        foreach($inAssignments as $clientAid=>$a){$clientCourse=(string)($a['courseId']??'');$realCourse=$courseMap[$clientCourse]??(int)$clientCourse;if(isset($oldAssignments[$clientAid])){ $old=$oldAssignments[$clientAid];foreach(['courseId','title','type','due','points','description'] as $f)if(!$this->sameValue($a[$f]??null,$old[$f]??null))abort(403,'Không được sửa nội dung bài tập đã tạo.');$assignment=Assignment::findOrFail((int)$clientAid);$assignmentMap[$clientAid]=(int)$assignment->assignment_id;$this->syncTeacherSubmissions($actor,$assignment,$old,$a); }
            else {if(!isset($courseMap[$clientCourse]) && !Course::where('course_id',$realCourse)->where('teacher_id',$actor->user_id)->exists())abort(403,'Chỉ được giao bài trong lớp mình phụ trách.');if(!empty($a['submissions']))abort(403,'Bài tập mới không được có bài nộp tạo sẵn.');$max=(float)($a['points']??0);if($max<=0||$max>1000)abort(422,'Điểm tối đa phải lớn hơn 0 và không quá 1000.');$assignment=Assignment::create(['course_id'=>$realCourse,'title'=>trim((string)$a['title']),'assignment_type'=>(string)($a['type']??'Bài tập'),'due_date'=>(string)($a['due']??now()->toDateString()),'max_points'=>$max,'is_open'=>($a['status']??'')!=='Chưa mở','description'=>(string)($a['description']??'')]);$assignmentMap[$clientAid]=(int)$assignment->assignment_id;}
        }
    }

    private function syncTeacherSubmissions(User $actor, Assignment $assignment, array $old, array $new): void
    {
        $oldSubs=collect($old['submissions']??[])->keyBy(fn($s)=>(string)$s['userId']);$newSubs=collect($new['submissions']??[])->keyBy(fn($s)=>(string)$s['userId']);if($oldSubs->keys()->sort()->values()->all()!==$newSubs->keys()->sort()->values()->all())abort(403,'Giảng viên không được tự tạo hoặc xóa bài nộp.');
        foreach($oldSubs as $uid=>$os){$ns=$newSubs[$uid];foreach(['userId','submitted','answer'] as $f)if(($ns[$f]??null)!==($os[$f]??null))abort(403,'Giảng viên không được sửa nội dung bài nộp của sinh viên.');$score=$ns['score']??null;$feedback=(string)($ns['feedback']??'');if($score===null){if($os['score']!==null)abort(403,'Không được xóa điểm đã ghi nhận.');continue;}if(!is_numeric($score)||(float)$score<0||(float)$score>(float)$assignment->max_points)abort(422,'Điểm nằm ngoài thang điểm của bài tập.');$submission=Submission::where('assignment_id',$assignment->assignment_id)->where('student_id',(int)$uid)->firstOrFail();$student=$submission->student;if(!$student || !$student->isStudent())abort(422,'Người nộp bài không phải sinh viên.');Grade::updateOrCreate(['submission_id'=>$submission->submission_id],['student_id'=>$student->user_id,'course_id'=>$assignment->course_id,'assignment_id'=>$assignment->assignment_id,'work_title'=>$assignment->title,'score'=>(float)$score,'max_score'=>(float)$assignment->max_points,'feedback'=>$feedback]);}
    }

    private function syncAdmin(User $actor, array $p): void
    {
        $current=$this->buildState($actor);$oldUsers=$this->indexById($current['users']);$newUsers=$this->indexById($p['users']);if(array_diff_key($oldUsers,$newUsers))abort(403,'Không được xóa tài khoản đã tồn tại qua snapshot.');
        foreach($newUsers as $id=>$u){if(isset($oldUsers[$id])){if($u!=$oldUsers[$id])abort(403,'Bản demo không cho sửa thông tin tài khoản hiện có.');continue;}if(!in_array($u['role']??'', ['student','teacher','admin'],true))abort(422,'Vai trò tài khoản không hợp lệ.');User::create(['full_name'=>trim((string)$u['name']),'user_code'=>trim((string)$u['code']),'email'=>mb_strtolower(trim((string)$u['email'])),'role'=>$u['role'],'status'=>$u['status']??'Hoạt động','password'=>Hash::make('Demo123!')]);}
        $oldCourseIds=array_column($current['courses'],'id');$newCourseIds=array_column($p['courses'],'id');if(array_diff($oldCourseIds,$newCourseIds))abort(403,'Không được xóa lớp học phần.');
        $courseMap=[];$oldCourses=$this->indexById($current['courses']);$inCourses=$this->indexById($p['courses']);
        foreach($inCourses as $cid=>$c){if(isset($oldCourses[$cid])){$old=$oldCourses[$cid];$course=Course::findOrFail((int)$cid);if(($c['lessons']??[])!==($old['lessons']??[])){if(array_slice($c['lessons'],0,count($old['lessons']))!=$old['lessons'])abort(403,'Không được sửa bài học đã tồn tại.');foreach(array_slice($c['lessons'],count($old['lessons'])) as $l)Lesson::create(['course_id'=>$course->course_id,'title'=>$l['title'],'duration'=>$l['duration']??'','lesson_type'=>$l['type']??'Tài liệu','lesson_order'=>$course->lessons()->count()+1]);}$courseMap[$cid]=(int)$cid;}else{$teacher=User::where('user_id',(int)($c['teacherId']??0))->where('role','teacher')->firstOrFail();$course=Course::create(['course_code'=>$c['code'],'course_name'=>$c['name'],'teacher_id'=>$teacher->user_id,'term'=>$c['term']??'Học kỳ 1','category'=>$c['category']??'Khác','description'=>$c['description']??'','color'=>$c['color']??'cover1','symbol'=>$c['symbol']??'DB']);$courseMap[$cid]=(int)$course->course_id;foreach(($c['lessons']??[]) as $i=>$l)Lesson::create(['course_id'=>$course->course_id,'title'=>$l['title'],'duration'=>$l['duration']??'','lesson_type'=>$l['type']??'Tài liệu','lesson_order'=>$i+1]);}}
        // Admin may add enrollments but existing registrations are immutable in this demo.
        $oldEnroll=collect($current['enrollments'])->keyBy(fn($e)=>$e['courseId'].':'.$e['studentId']);$newEnroll=collect($p['enrollments'])->keyBy(fn($e)=>$e['courseId'].':'.$e['studentId']);if($oldEnroll->keys()->diff($newEnroll->keys())->isNotEmpty())abort(403,'Không được xóa đăng ký học phần.');foreach($newEnroll as $key=>$e){$realCid=$courseMap[(string)$e['courseId']]??(int)$e['courseId'];$student=User::where('user_id',(int)$e['studentId'])->where('role','student')->firstOrFail();if(!$oldEnroll->has($key))Enrollment::firstOrCreate(['course_id'=>$realCid,'student_id'=>$student->user_id],['progress'=>max(0,min(100,(int)($e['progress']??0)))]);}
        $oldAssignments=$this->indexById($current['assignments']);$inAssignments=$this->indexById($p['assignments']);if(array_diff_key($oldAssignments,$inAssignments))abort(403,'Không được xóa bài tập.');
        foreach($inAssignments as $aid=>$a){if(isset($oldAssignments[$aid])){$this->syncTeacherSubmissions($actor,Assignment::findOrFail((int)$aid),$oldAssignments[$aid],$a);continue;}$realCid=$courseMap[(string)$a['courseId']]??(int)$a['courseId'];$course=Course::findOrFail($realCid);if(!empty($a['submissions']))abort(403,'Không được tạo sẵn bài nộp cho bài tập mới.');Assignment::create(['course_id'=>$course->course_id,'title'=>$a['title'],'assignment_type'=>$a['type']??'Bài tập','due_date'=>$a['due']??now()->toDateString(),'max_points'=>$a['points']??10,'is_open'=>($a['status']??'')!=='Chưa mở','description'=>$a['description']??'']);}
    }
}
