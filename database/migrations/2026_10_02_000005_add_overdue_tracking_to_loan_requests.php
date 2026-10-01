<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->timestamp('overdue_notified_at')->nullable()->after('reminded_at');
            $table->unsignedTinyInteger('overdue_count')->default(0)->after('overdue_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropColumn(['overdue_notified_at', 'overdue_count']);
        });
    }
};
