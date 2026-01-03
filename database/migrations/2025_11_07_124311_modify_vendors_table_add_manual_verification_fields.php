<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // ✅ Drop old boolean columns if they exist (if you already ran previous migration)
            if (Schema::hasColumn('vendors', 'aadhar_manual')) {
                $table->dropColumn('aadhar_manual');
            }
            if (Schema::hasColumn('vendors', 'rc_manual')) {
                $table->dropColumn('rc_manual');
            }
            if (Schema::hasColumn('vendors', 'dl_manual')) {
                $table->dropColumn('dl_manual');
            }

            // ✅ ADD NEW TEXT COLUMNS (for manual data entry)
            $table->text('aadhar_manual')->nullable()->after('aadhar_back')->comment('Manual aadhar verification data (JSON)');
            $table->text('rc_manual')->nullable()->after('rc_verified')->comment('Manual RC verification data (JSON)');
            $table->text('dl_manual')->nullable()->after('dl_verified')->comment('Manual DL verification data (JSON)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['aadhar_manual', 'rc_manual', 'dl_manual']);
        });
    }
};
