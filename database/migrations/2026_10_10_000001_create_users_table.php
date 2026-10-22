<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('full_name',160);
            $table->string('user_code',40)->unique();
            $table->string('email',190)->unique();
            $table->string('password');
            $table->enum('role',['student','teacher','admin']);
            $table->string('status',20)->default('Hoạt động');
            $table->rememberToken();
            $table->timestamps();
            $table->index(['role','status']);
        });
        Schema::create('password_reset_tokens', function(Blueprint $table){$table->string('email')->primary();$table->string('token');$table->timestamp('created_at')->nullable();});
        Schema::create('sessions', function(Blueprint $table){$table->string('id')->primary();$table->foreignId('user_id')->nullable()->index();$table->string('ip_address',45)->nullable();$table->text('user_agent')->nullable();$table->longText('payload');$table->integer('last_activity')->index();});
    }
    public function down(): void { Schema::dropIfExists('sessions'); Schema::dropIfExists('password_reset_tokens'); Schema::dropIfExists('users'); }
};
