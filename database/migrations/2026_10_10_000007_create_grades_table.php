<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('grades',function(Blueprint $table){$table->id('grade_id');$table->foreignId('student_id')->constrained('users','user_id')->cascadeOnDelete()->cascadeOnUpdate();$table->foreignId('course_id')->constrained('courses','course_id')->cascadeOnDelete()->cascadeOnUpdate();$table->unsignedBigInteger('assignment_id')->nullable();$table->unsignedBigInteger('submission_id')->nullable()->unique();$table->string('work_title',220);$table->decimal('score',6,2);$table->decimal('max_score',6,2);$table->text('feedback')->nullable();$table->timestamps();$table->foreign('assignment_id')->references('assignment_id')->on('assignments')->cascadeOnDelete()->cascadeOnUpdate();$table->foreign('submission_id')->references('submission_id')->on('submissions')->cascadeOnDelete()->cascadeOnUpdate();$table->index(['student_id','course_id']);}); }
    public function down(): void { Schema::dropIfExists('grades'); }
};
