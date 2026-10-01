<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requester_club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->string('requester_type'); // club | private
            $table->string('requester_name');
            $table->string('requester_email');
            $table->unsignedInteger('quantity')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('waiting'); // unverified | waiting | notified | cancelled | expired
            $table->timestamp('notified_at')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamps();

            $table->index(['item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
