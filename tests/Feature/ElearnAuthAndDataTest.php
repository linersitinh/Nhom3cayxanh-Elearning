<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ElearnAuthAndDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_read_state(): void
    {
        $this->getJson('/api/state')->assertUnauthorized();
    }

    public function test_demo_student_can_login_and_get_state(): void
    {
        $this->postJson('/api/login',['identifier'=>'2380600984','password'=>'Demo123!'])->assertOk()->assertJsonPath('user.role','student');
        $response=$this->getJson('/api/state')->assertOk()->assertJsonStructure(['users','courses','assignments','grades','enrollments']);
        $this->assertSame([1],array_column($response->json('users'),'id'));
    }

    public function test_student_cannot_view_other_students_submissions(): void
    {
        $this->actingAs(User::where('user_code','2380600984')->first());
        $response=$this->getJson('/api/state')->assertOk();
        foreach($response->json('assignments') as $assignment) foreach($assignment['submissions'] as $submission) $this->assertSame(1,$submission['userId']);
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        $student=User::where('user_code','2380600984')->first();
        $this->actingAs($student)->getJson('/api/reports/course-summary')->assertForbidden();
    }

    public function test_student_cannot_submit_to_unenrolled_course(): void
    {
        $this->actingAs(User::where('user_code','2380600984')->first());
        $state=$this->getJson('/api/state')->assertOk()->json();
        $state['assignments'][]=['id'=>998,'courseId'=>4,'title'=>'Forged','type'=>'Bài tập','due'=>'2026-10-31','points'=>10,'status'=>'Chưa nộp','submissions'=>[['userId'=>1,'submitted'=>'2026-10-10','answer'=>'hack','score'=>null,'feedback'=>'']],'description'=>'Forged'];
        $this->putJson('/api/state',$state)->assertForbidden();
        $this->assertSame(0,Submission::where('assignment_id',998)->count());
    }

    public function test_student_cannot_modify_points(): void
    {
        $student=User::where('user_code','2380600984')->first();
        $this->actingAs($student);
        $state=$this->getJson('/api/state')->assertOk()->json();
        $state['grades'][0]['score']=0;
        $this->putJson('/api/state',$state)->assertForbidden();
    }

    public function test_duplicate_enrollment_is_rejected_by_database(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Enrollment::create(['course_id'=>1,'student_id'=>1,'progress'=>0]);
    }


    public function test_student_can_enroll_in_another_course(): void
    {
        $this->actingAs(User::where('user_code','2380600984')->first());
        $state=$this->getJson('/api/state')->assertOk()->json();
        $state['enrollments'][]=['courseId'=>4,'studentId'=>1,'progress'=>0];
        $this->putJson('/api/state',$state)->assertOk();
        $this->assertDatabaseHas('enrollments',['course_id'=>4,'student_id'=>1,'progress'=>0]);
    }

    public function test_student_can_submit_assignment_after_enrollment(): void
    {
        $this->actingAs(User::where('user_code','2380600984')->first());
        $state=$this->getJson('/api/state')->assertOk()->json();
        foreach ($state['assignments'] as &$assignment) {
            if ($assignment['id'] === 1) $assignment['submissions'][]=['userId'=>1,'submitted'=>'2026-10-10','answer'=>'Bài làm kiểm thử','score'=>null,'feedback'=>''];
        }
        unset($assignment);
        $this->putJson('/api/state',$state)->assertOk();
        $this->assertDatabaseHas('submissions',['assignment_id'=>1,'student_id'=>1,'answer'=>'Bài làm kiểm thử']);
    }

    public function test_teacher_cannot_grade_course_owned_by_another_teacher(): void
    {
        $teacher=User::where('user_code','GV002')->first();
        $this->actingAs($teacher);
        $state=$this->getJson('/api/state')->assertOk()->json();
        $state['assignments'][]=['id'=>123456,'courseId'=>1,'title'=>'Forged grade','type'=>'Bài tập','due'=>'2026-10-31','points'=>10,'status'=>'Đã nộp','submissions'=>[['userId'=>1,'submitted'=>'2026-10-09','answer'=>'existing','score'=>9,'feedback'=>'forged']],'description'=>'forged'];
        $this->putJson('/api/state',$state)->assertForbidden();
    }

    public function test_course_assignment_and_progress_schema_are_seeded(): void
    {
        $this->assertGreaterThan(0,Course::count());
        $this->assertGreaterThan(0,Assignment::count());
        $this->assertSame(68,(int)Enrollment::where('course_id',1)->where('student_id',1)->value('progress'));
    }
}
