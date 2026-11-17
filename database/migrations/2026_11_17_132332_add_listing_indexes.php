<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Combined indexes for the busiest lists, which filter by one column and sort by another:
     * service requests by status and date received, the ticket inbox by status and last update,
     * and the Activity Log by user and time. Added as a new migration because the live database
     * already has these tables.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->index(['status', 'received_at']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['status', 'updated_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'received_at']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status', 'updated_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
