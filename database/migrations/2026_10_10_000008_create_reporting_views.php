<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::unprepared('CREATE VIEW v_course_summary AS SELECT c.course_id,c.course_code,c.course_name,u.full_name AS teacher_name,COUNT(DISTINCT e.student_id) AS student_count,COUNT(DISTINCT a.assignment_id) AS assignment_count,COUNT(DISTINCT l.lesson_id) AS lesson_count,COALESCE(ROUND(AVG(e.progress),1),0) AS avg_progress FROM courses c JOIN users u ON u.user_id=c.teacher_id LEFT JOIN enrollments e ON e.course_id=c.course_id LEFT JOIN assignments a ON a.course_id=c.course_id LEFT JOIN lessons l ON l.course_id=c.course_id GROUP BY c.course_id,c.course_code,c.course_name,u.full_name');
        DB::unprepared('CREATE VIEW v_submission_summary AS SELECT a.assignment_id,a.title,c.course_name,COUNT(s.submission_id) AS submission_count,SUM(CASE WHEN g.grade_id IS NOT NULL THEN 1 ELSE 0 END) AS graded_count,COUNT(s.submission_id)-COUNT(DISTINCT CASE WHEN g.grade_id IS NOT NULL THEN s.submission_id END) AS pending_count FROM assignments a JOIN courses c ON c.course_id=a.course_id LEFT JOIN submissions s ON s.assignment_id=a.assignment_id LEFT JOIN grades g ON g.submission_id=s.submission_id GROUP BY a.assignment_id,a.title,c.course_name');
    }
    public function down(): void { DB::unprepared('DROP VIEW IF EXISTS v_submission_summary'); DB::unprepared('DROP VIEW IF EXISTS v_course_summary'); }
};
