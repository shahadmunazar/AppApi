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
        Schema::create('withdrawal_money', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('request_money');
            $table->string('mobile_no');
            $table->string('upi_id')->nullable();
            $table->string('acount_holder_name')->nullable();
            $table->integer('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('withdrawal_money_status')->default('not_accepted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdrawal_money');
    }
};
