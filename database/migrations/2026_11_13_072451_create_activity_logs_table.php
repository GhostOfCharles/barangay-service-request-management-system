<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Activity Log: one row per change made by Staff or the Administrator. Rows are never updated or deleted.
     * The user's name and role are copied in, so entries stay readable after an account is renamed.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name');
            $table->string('user_role', 20);
            $table->string('action', 50)->index();
            $table->nullableMorphs('subject');
            $table->string('subject_label')->nullable();
            $table->string('description', 500);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
