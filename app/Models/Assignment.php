<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $primaryKey = 'assignment_id';
    protected $fillable = ['assignment_id','course_id','title','assignment_type','due_date','max_points','is_open','description'];
    protected function casts(): array { return ['due_date' => 'date:Y-m-d', 'max_points' => 'decimal:2', 'is_open' => 'boolean']; }
    public function course() { return $this->belongsTo(Course::class, 'course_id'); }
    public function submissions() { return $this->hasMany(Submission::class, 'assignment_id'); }
    public function grades() { return $this->hasMany(Grade::class, 'assignment_id'); }
}
