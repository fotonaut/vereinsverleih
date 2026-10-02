<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('overdue_count');
            $table->string('return_condition')->nullable()->after('returned_at');
            $table->text('return_note')->nullable()->after('return_condition');
            $table->boolean('deposit_returned')->default(false)->after('return_note');
        });
    }

    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropColumn(['returned_at', 'return_condition', 'return_note', 'deposit_returned']);
        });
    }
};
