<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('truck_bookings', function (Blueprint $table) {
            // Make these fields nullable
            $table->unsignedBigInteger('truck_type_id')->nullable()->change();
            $table->string('truck_type_name')->nullable()->change();
            $table->unsignedBigInteger('truck_specification_id')->nullable()->change();
            $table->decimal('truck_length', 8, 2)->nullable()->change();
            $table->integer('tyre_count')->nullable()->change();
            $table->decimal('truck_height', 8, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('truck_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('truck_type_id')->nullable(false)->change();
            $table->string('truck_type_name')->nullable(false)->change();
            $table->unsignedBigInteger('truck_specification_id')->nullable(false)->change();
            $table->decimal('truck_length', 8, 2)->nullable(false)->change();
            $table->integer('tyre_count')->nullable(false)->change();
            $table->decimal('truck_height', 8, 2)->nullable(false)->change();
        });
    }
};
