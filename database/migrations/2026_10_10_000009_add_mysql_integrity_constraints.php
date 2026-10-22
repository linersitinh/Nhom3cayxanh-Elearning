<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        DB::statement('ALTER TABLE enrollments ADD CONSTRAINT chk_enrollments_progress CHECK (progress BETWEEN 0 AND 100)');
        DB::statement('ALTER TABLE assignments ADD CONSTRAINT chk_assignments_max_points CHECK (max_points > 0 AND max_points <= 1000)');
        DB::statement('ALTER TABLE grades ADD CONSTRAINT chk_grades_score CHECK (score >= 0 AND max_score > 0 AND score <= max_score)');
        DB::unprepared("CREATE TRIGGER trg_courses_teacher_insert BEFORE INSERT ON courses FOR EACH ROW BEGIN IF (SELECT role FROM users WHERE user_id=NEW.teacher_id) <> 'teacher' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Course teacher_id must reference a teacher'; END IF; END");
        DB::unprepared("CREATE TRIGGER trg_courses_teacher_update BEFORE UPDATE ON courses FOR EACH ROW BEGIN IF (SELECT role FROM users WHERE user_id=NEW.teacher_id) <> 'teacher' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Course teacher_id must reference a teacher'; END IF; END");
        DB::unprepared("CREATE TRIGGER trg_enrollments_student_insert BEFORE INSERT ON enrollments FOR EACH ROW BEGIN IF (SELECT role FROM users WHERE user_id=NEW.student_id) <> 'student' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Enrollment student_id must reference a student'; END IF; END");
        DB::unprepared("CREATE TRIGGER trg_enrollments_student_update BEFORE UPDATE ON enrollments FOR EACH ROW BEGIN IF (SELECT role FROM users WHERE user_id=NEW.student_id) <> 'student' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Enrollment student_id must reference a student'; END IF; END");
        DB::unprepared("CREATE TRIGGER trg_submissions_eligibility_insert BEFORE INSERT ON submissions FOR EACH ROW BEGIN IF (SELECT role FROM users WHERE user_id=NEW.student_id) <> 'student' OR NOT EXISTS (SELECT 1 FROM assignments a JOIN enrollments e ON e.course_id=a.course_id WHERE a.assignment_id=NEW.assignment_id AND e.student_id=NEW.student_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Submission requires an enrolled student'; END IF; END");
        DB::unprepared("CREATE TRIGGER trg_submissions_eligibility_update BEFORE UPDATE ON submissions FOR EACH ROW BEGIN IF (SELECT role FROM users WHERE user_id=NEW.student_id) <> 'student' OR NOT EXISTS (SELECT 1 FROM assignments a JOIN enrollments e ON e.course_id=a.course_id WHERE a.assignment_id=NEW.assignment_id AND e.student_id=NEW.student_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Submission requires an enrolled student'; END IF; END");
        DB::unprepared("CREATE TRIGGER trg_grades_consistency_insert BEFORE INSERT ON grades FOR EACH ROW BEGIN IF NEW.score < 0 OR NEW.max_score <= 0 OR NEW.score > NEW.max_score OR (SELECT role FROM users WHERE user_id=NEW.student_id) <> 'student' OR (NEW.assignment_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM assignments a JOIN enrollments e ON e.course_id=a.course_id WHERE a.assignment_id=NEW.assignment_id AND a.course_id=NEW.course_id AND e.student_id=NEW.student_id)) OR (NEW.submission_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM submissions s WHERE s.submission_id=NEW.submission_id AND s.student_id=NEW.student_id AND s.assignment_id=NEW.assignment_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Grade does not match student, assignment, submission or score range'; END IF; END");
        DB::unprepared("CREATE TRIGGER trg_grades_consistency_update BEFORE UPDATE ON grades FOR EACH ROW BEGIN IF NEW.score < 0 OR NEW.max_score <= 0 OR NEW.score > NEW.max_score OR (SELECT role FROM users WHERE user_id=NEW.student_id) <> 'student' OR (NEW.assignment_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM assignments a JOIN enrollments e ON e.course_id=a.course_id WHERE a.assignment_id=NEW.assignment_id AND a.course_id=NEW.course_id AND e.student_id=NEW.student_id)) OR (NEW.submission_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM submissions s WHERE s.submission_id=NEW.submission_id AND s.student_id=NEW.student_id AND s.assignment_id=NEW.assignment_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Grade does not match student, assignment, submission or score range'; END IF; END");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') return;
        foreach (['trg_grades_consistency_update','trg_grades_consistency_insert','trg_submissions_eligibility_update','trg_submissions_eligibility_insert','trg_enrollments_student_update','trg_enrollments_student_insert','trg_courses_teacher_update','trg_courses_teacher_insert'] as $trigger) DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
        DB::statement('ALTER TABLE grades DROP CHECK chk_grades_score');
        DB::statement('ALTER TABLE assignments DROP CHECK chk_assignments_max_points');
        DB::statement('ALTER TABLE enrollments DROP CHECK chk_enrollments_progress');
    }
};
