<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_request_id')->constrained()->cascadeOnDelete();
            $table->date('previous_end_date');
            $table->date('requested_end_date');
            $table->text('message')->nullable();
            $table->string('status')->default('pending'); // pending | approved | declined
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['loan_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_extensions');
    }
};
