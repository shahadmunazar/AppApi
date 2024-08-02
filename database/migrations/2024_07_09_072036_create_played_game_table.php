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
        Schema::create('played_game', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('played_game_id')->nullable();
            $table->string('played_game_type')->nullable();
            $table->string('entered_number')->nullable();
            $table->decimal('entered_amount', 8, 2)->nullable();
            $table->decimal('won_game_price', 8, 2)->nullable();
            $table->decimal('loss_game_price', 8, 2)->nullable();
            $table->string('open_today_number')->nullable();
            $table->enum('is_open', ['waiting', 'Open_muber', 'pending'])->default('pending');
            $table->enum('status', ['won', 'lost', 'pending'])->default('pending');
            $table->timestamps();

            // Foreign key constraints (optional)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('played_game_id')->references('id')->on('sub_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('played_game');
    }
};
