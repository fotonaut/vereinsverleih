<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('deposit_cents')->nullable();
            $table->string('image_path')->nullable();
            $table->string('lending_scope')->default('none');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['lending_scope', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
