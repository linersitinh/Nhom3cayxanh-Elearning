<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('lessons',function(Blueprint $table){$table->id('lesson_id');$table->foreignId('course_id')->constrained('courses','course_id')->cascadeOnDelete()->cascadeOnUpdate();$table->string('title',220);$table->string('duration',40)->default('');$table->string('lesson_type',80)->default('Tài liệu');$table->unsignedInteger('lesson_order')->default(1);$table->timestamps();$table->index(['course_id','lesson_order']);}); }
    public function down(): void { Schema::dropIfExists('lessons'); }
};
