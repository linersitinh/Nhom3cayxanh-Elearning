<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('assignments',function(Blueprint $table){$table->id('assignment_id');$table->foreignId('course_id')->constrained('courses','course_id')->cascadeOnDelete()->cascadeOnUpdate();$table->string('title',220);$table->string('assignment_type',80);$table->date('due_date');$table->decimal('max_points',6,2);$table->boolean('is_open')->default(true);$table->text('description');$table->timestamps();$table->index(['course_id','due_date']);}); }
    public function down(): void { Schema::dropIfExists('assignments'); }
};
