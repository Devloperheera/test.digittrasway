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
        Schema::table('plans', function (Blueprint $table) {
            // Add duration_days column after duration_type
            if (!Schema::hasColumn('plans', 'duration_days')) {
                $table->integer('duration_days')->default(30)->after('duration_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'duration_days')) {
                $table->dropColumn('duration_days');
            }
        });
    }
};
