<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('courses', function(Blueprint $table){$table->id('course_id');$table->string('course_code',40)->unique();$table->string('course_name',200);$table->foreignId('teacher_id')->constrained('users','user_id')->restrictOnDelete()->cascadeOnUpdate();$table->string('term',100);$table->string('category',100);$table->text('description');$table->string('color',30)->default('cover1');$table->string('symbol',30)->default('DB');$table->timestamps();$table->index('teacher_id');}); }
    public function down(): void { Schema::dropIfExists('courses'); }
};
