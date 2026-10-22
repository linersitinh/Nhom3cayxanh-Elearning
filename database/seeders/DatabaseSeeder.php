<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // `php artisan migrate --seed` is safe on later launches: do not wipe an existing demo DB.
        // The admin-only reset endpoint clears tables explicitly before calling this seeder.
        if (User::query()->exists()) return;

        $users = [
            [1,'Nguyễn An Khang','2380600984','khang.nguyen@hutech.edu.vn','student'],
            [2,'Phạm Dương Đình Huy','2380600870','huy.pham@hutech.edu.vn','student'],
            [3,'Lê Phương Nam','2380614933','nam.le@hutech.edu.vn','student'],
            [4,'Trần Minh Anh','GV001','minhanh@hutech.edu.vn','teacher'],
            [5,'Quản trị viên','ADMIN01','admin@elearn.local','admin'],
            [6,'Võ Hoàng Yến','2380601122','yen.vo@hutech.edu.vn','student'],
            [7,'Nguyễn Quốc Bảo','GV002','quocbao@hutech.edu.vn','teacher'],
            [8,'Lê Thu Hà','GV003','thuha@hutech.edu.vn','teacher'],
        ];
        foreach($users as [$id,$name,$code,$email,$role]) User::create(['user_id'=>$id,'full_name'=>$name,'user_code'=>$code,'email'=>$email,'role'=>$role,'status'=>'Hoạt động','password'=>Hash::make('Demo123!')]);
        $courses = [
            [1,'CSDLNC-01','Cơ sở dữ liệu nâng cao',4,'Học kỳ 1 · 2026–2027','Cơ sở dữ liệu','Thiết kế cơ sở dữ liệu quan hệ, phụ thuộc hàm, chuẩn hóa, ràng buộc toàn vẹn và truy vấn SQL nâng cao.','cover1','DB'],
            [2,'WEB-02','Lập trình ứng dụng Web',7,'Học kỳ 1 · 2026–2027','Phát triển phần mềm','Nền tảng phát triển ứng dụng web, xử lý phía máy chủ, giao diện và kết nối cơ sở dữ liệu.','cover2','</>'],
            [3,'ATUD-01','An toàn ứng dụng',4,'Học kỳ 1 · 2026–2027','An toàn thông tin','Nhận diện lỗ hổng ứng dụng, kiểm soát truy cập và các nguyên tắc xây dựng phần mềm an toàn.','cover3','⌑'],
            [4,'PTPM-03','Phân tích và thiết kế phần mềm',8,'Học kỳ 1 · 2026–2027','Phân tích thiết kế','Phân tích yêu cầu, mô hình hóa hệ thống, thiết kế kiến trúc và tổ chức triển khai dự án phần mềm.','cover4','◇'],
        ];
        foreach($courses as [$id,$code,$name,$teacher,$term,$category,$description,$color,$symbol]) Course::create(['course_id'=>$id,'course_code'=>$code,'course_name'=>$name,'teacher_id'=>$teacher,'term'=>$term,'category'=>$category,'description'=>$description,'color'=>$color,'symbol'=>$symbol]);
        foreach([[1,1,68],[1,2,72],[1,3,78],[1,6,44],[2,1,42],[2,6,30],[3,1,85],[4,2,25]] as [$cid,$sid,$progress]) Enrollment::create(['course_id'=>$cid,'student_id'=>$sid,'progress'=>$progress]);
        $lessons = [
            [1,1,'Tổng quan hệ quản trị cơ sở dữ liệu','18 phút','Video bài giảng',1],[2,1,'Phụ thuộc hàm và khóa','25 phút','Tài liệu + video',2],[3,1,'Chuẩn hóa dữ liệu','32 phút','Video bài giảng',3],[4,1,'Ràng buộc toàn vẹn','20 phút','Tài liệu PDF',4],
            [5,2,'Kiến trúc ứng dụng web','22 phút','Video bài giảng',1],[6,2,'Form và xử lý dữ liệu','28 phút','Thực hành',2],[7,3,'Mô hình đe dọa','23 phút','Video bài giảng',1],[8,3,'Kiểm soát truy cập','19 phút','Tài liệu',2],[9,4,'Thu thập yêu cầu','20 phút','Video bài giảng',1],[10,4,'Mô hình Use Case','24 phút','Tài liệu',2],
        ];
        foreach($lessons as [$id,$cid,$title,$duration,$type,$order]) Lesson::create(['lesson_id'=>$id,'course_id'=>$cid,'title'=>$title,'duration'=>$duration,'lesson_type'=>$type,'lesson_order'=>$order]);
        $assignments = [
            [1,1,'Bài tập 01: Phụ thuộc hàm','Bài tập cá nhân','2026-10-13',10,true,'Xác định khóa ứng viên, phủ tối thiểu và phân tích phụ thuộc hàm cho lược đồ quan hệ đã cho.'],
            [2,1,'Thiết kế ERD hệ thống thực tế','Bài tập nhóm','2026-10-16',10,true,'Xây dựng mô hình ERD cho một hệ thống thông tin thực tế, giải thích khóa và lực lượng quan hệ.'],
            [3,2,'Thực hành Form và CRUD','Bài thực hành','2026-10-18',10,true,'Xây dựng biểu mẫu thêm, sửa, xóa dữ liệu và kiểm tra dữ liệu đầu vào.'],
            [4,3,'Phân tích rủi ro ứng dụng','Bài tập nhóm','2026-10-20',10,true,'Phân tích ba rủi ro bảo mật và đề xuất biện pháp giảm thiểu.'],
            [5,1,'Kiểm tra chương 1: Mô hình quan hệ','Trắc nghiệm','2026-10-22',10,false,'Bài kiểm tra ngắn về mô hình quan hệ, khóa và ràng buộc.'],
        ];
        foreach($assignments as [$id,$cid,$title,$type,$due,$max,$open,$desc]) Assignment::create(['assignment_id'=>$id,'course_id'=>$cid,'title'=>$title,'assignment_type'=>$type,'due_date'=>$due,'max_points'=>$max,'is_open'=>$open,'description'=>$desc]);
        Submission::create(['submission_id'=>1,'assignment_id'=>2,'student_id'=>1,'submitted_at'=>'2026-10-09 10:00:00','answer'=>'Bản ERD và mô tả nghiệp vụ nhóm 3cayxanh.']);
        Submission::create(['submission_id'=>2,'assignment_id'=>4,'student_id'=>1,'submitted_at'=>'2026-10-05 10:00:00','answer'=>'Báo cáo phân tích rủi ro phiên bản 1.']);
        Grade::create(['grade_id'=>1,'student_id'=>1,'course_id'=>3,'assignment_id'=>4,'submission_id'=>2,'work_title'=>'Phân tích rủi ro ứng dụng','score'=>8.5,'max_score'=>10,'feedback'=>'Phân tích rõ ràng. Cần bổ sung biện pháp xác minh.']);
        Grade::create(['grade_id'=>2,'student_id'=>1,'course_id'=>1,'work_title'=>'Bài thực hành 0','score'=>9,'max_score'=>10,'feedback'=>'Trình bày tốt.']);
        Grade::create(['grade_id'=>3,'student_id'=>2,'course_id'=>1,'work_title'=>'Bài thực hành 0','score'=>8,'max_score'=>10,'feedback'=>'Đạt yêu cầu.']);
        Grade::create(['grade_id'=>4,'student_id'=>3,'course_id'=>1,'work_title'=>'Bài thực hành 0','score'=>9.5,'max_score'=>10,'feedback'=>'Tốt.']);
    }
}
