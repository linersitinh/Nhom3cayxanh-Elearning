<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $primaryKey = 'grade_id';
    protected $fillable = ['grade_id','student_id','course_id','assignment_id','submission_id','work_title','score','max_score','feedback'];
    protected function casts(): array { return ['score' => 'decimal:2', 'max_score' => 'decimal:2']; }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function course() { return $this->belongsTo(Course::class, 'course_id'); }
    public function assignment() { return $this->belongsTo(Assignment::class, 'assignment_id'); }
    public function submission() { return $this->belongsTo(Submission::class, 'submission_id'); }
}
