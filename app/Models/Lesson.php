<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $primaryKey = 'lesson_id';
    protected $fillable = ['lesson_id','course_id','title','duration','lesson_type','lesson_order'];
    public function course() { return $this->belongsTo(Course::class, 'course_id'); }
}
