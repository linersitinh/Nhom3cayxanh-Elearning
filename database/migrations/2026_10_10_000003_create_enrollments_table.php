<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('enrollments',function(Blueprint $table){$table->id('enrollment_id');$table->foreignId('course_id')->constrained('courses','course_id')->cascadeOnDelete()->cascadeOnUpdate();$table->foreignId('student_id')->constrained('users','user_id')->cascadeOnDelete()->cascadeOnUpdate();$table->unsignedTinyInteger('progress')->default(0);$table->timestamp('enrolled_at')->useCurrent();$table->timestamps();$table->unique(['course_id','student_id']);$table->index('student_id');}); }
    public function down(): void { Schema::dropIfExists('enrollments'); }
};
