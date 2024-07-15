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
        Schema::create('play_games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('category_id')->nullable();
            $table->string('Playing_Name')->nullable();
            $table->string('play_type')->nullable();
            $table->string('ander_harup')->nullable();
            $table->string('bahar_harup')->nullable();
            $table->string('play_game_id')->nullable();
            $table->decimal('today_number', 8, 2)->nullable();
            $table->string('after_open_number_block')->default('not_opened')->nullable();
            $table->string('open_time_number')->nullable();
            $table->decimal('loss_amount', 8, 2)->nullable();
            $table->decimal('won_amount', 8, 2)->nullable();
            $table->integer('entered_number')->nullable();
            $table->decimal('entered_amount', 8, 2)->nullable();
            $table->enum('status', ['won', 'lost', 'waiting', 'not_opened'])->default('waiting'); // New field for game status
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
        Schema::dropIfExists('play_games');
    }
};
