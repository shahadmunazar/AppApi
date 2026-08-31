<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN transaction_type ENUM('credit', 'debit', 'bonus', 'won', 'loss', 'withdrawal', 'reverse', 'refund', 'rejected') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN transaction_type ENUM('credit', 'debit', 'bonus', 'won', 'loss', 'withdrawal', 'reverse') NOT NULL");
    }
};
