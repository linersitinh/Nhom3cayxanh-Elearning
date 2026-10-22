<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';
    protected $fillable = ['user_id', 'full_name', 'user_code', 'email', 'password', 'role', 'status'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['password' => 'hashed']; }
    public function courses() { return $this->hasMany(Course::class, 'teacher_id'); }
    public function enrollments() { return $this->hasMany(Enrollment::class, 'student_id'); }
    public function submissions() { return $this->hasMany(Submission::class, 'student_id'); }
    public function grades() { return $this->hasMany(Grade::class, 'student_id'); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isTeacher(): bool { return $this->role === 'teacher'; }
    public function isStudent(): bool { return $this->role === 'student'; }
    public function getNameAttribute(): string { return $this->full_name; }
}
