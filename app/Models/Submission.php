<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $primaryKey = 'submission_id';
    protected $fillable = ['submission_id','assignment_id','student_id','submitted_at','answer'];
    protected function casts(): array { return ['submitted_at' => 'datetime']; }
    public function assignment() { return $this->belongsTo(Assignment::class, 'assignment_id'); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function grade() { return $this->hasOne(Grade::class, 'submission_id'); }
}
