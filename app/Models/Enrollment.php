<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $primaryKey = 'enrollment_id';
    protected $fillable = ['course_id','student_id','progress','enrolled_at'];
    protected function casts(): array { return ['progress' => 'integer', 'enrolled_at' => 'datetime']; }
    public function course() { return $this->belongsTo(Course::class, 'course_id'); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
}
