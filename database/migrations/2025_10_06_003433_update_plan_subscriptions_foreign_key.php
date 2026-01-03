<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            // Drop old foreign key
            $table->dropForeign(['user_id']);

            // Add new foreign key to users table
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            // Revert back to vendors
            $table->dropForeign(['user_id']);

            $table->foreign('user_id')
                  ->references('id')
                  ->on('vendors')
                  ->onDelete('cascade');
        });
    }
};
