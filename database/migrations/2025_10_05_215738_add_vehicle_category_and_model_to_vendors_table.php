<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Add vehicle category and model fields
            $table->foreignId('vehicle_category_id')->nullable()->after('user_type_id')
                ->constrained('vehicle_categories')->onDelete('set null');

            $table->foreignId('vehicle_model_id')->nullable()->after('vehicle_category_id')
                ->constrained('vehicle_models')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['vehicle_category_id']);
            $table->dropForeign(['vehicle_model_id']);
            $table->dropColumn(['vehicle_category_id', 'vehicle_model_id']);
        });
    }
};
