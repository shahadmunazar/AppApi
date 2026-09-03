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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('bonus_balance', 15, 2)->default(0);
            $table->decimal('deposit_balance', 15, 2)->default(0);
            $table->decimal('winning_balance', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bonus_balance');
            $table->dropColumn('deposit_balance');
            $table->dropColumn('winning_balance');
        });
    }
};
