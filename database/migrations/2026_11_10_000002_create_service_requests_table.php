<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->unique();
            $table->foreignId('resident_id')->constrained()->restrictOnDelete();
            $table->string('category', 30)->index();
            $table->text('description');
            $table->string('document_status', 20);
            $table->date('date_received')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
