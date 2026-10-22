<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $primaryKey = 'course_id';
    protected $fillable = ['course_id','course_code','course_name','teacher_id','term','category','description','color','symbol'];
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function enrollments() { return $this->hasMany(Enrollment::class, 'course_id'); }
    public function lessons() { return $this->hasMany(Lesson::class, 'course_id')->orderBy('lesson_order'); }
    public function assignments() { return $this->hasMany(Assignment::class, 'course_id'); }
    public function grades() { return $this->hasMany(Grade::class, 'course_id'); }
}
