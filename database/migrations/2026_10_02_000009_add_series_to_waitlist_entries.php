<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->string('repeat')->nullable()->after('end_date');
            $table->unsignedTinyInteger('repeat_count')->nullable()->after('repeat');
            $table->date('until_date')->nullable()->after('repeat_count'); // Ende des letzten Termins
        });

        DB::table('waitlist_entries')->update(['until_date' => DB::raw('end_date')]);
    }

    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropColumn(['repeat', 'repeat_count', 'until_date']);
        });
    }
};
