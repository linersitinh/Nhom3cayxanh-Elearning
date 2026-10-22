<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('submissions',function(Blueprint $table){$table->id('submission_id');$table->foreignId('assignment_id')->constrained('assignments','assignment_id')->cascadeOnDelete()->cascadeOnUpdate();$table->foreignId('student_id')->constrained('users','user_id')->cascadeOnDelete()->cascadeOnUpdate();$table->timestamp('submitted_at');$table->mediumText('answer');$table->timestamps();$table->unique(['assignment_id','student_id']);$table->index(['student_id','submitted_at']);}); }
    public function down(): void { Schema::dropIfExists('submissions'); }
};
