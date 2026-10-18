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
        // 1. Add softDeletes to users table if not exists
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 2. Add softDeletes to transactions table if not exists
        if (!Schema::hasColumn('transactions', 'deleted_at')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 3. Add softDeletes to add_money_requests table if not exists
        if (!Schema::hasColumn('add_money_requests', 'deleted_at')) {
            Schema::table('add_money_requests', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 4. Add softDeletes to withdrawal_money table if not exists
        if (!Schema::hasColumn('withdrawal_money', 'deleted_at')) {
            Schema::table('withdrawal_money', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 5. Add softDeletes to play_games table if not exists
        if (!Schema::hasColumn('play_games', 'deleted_at')) {
            Schema::table('play_games', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 6. Update foreign keys on transactions and add_money_requests from CASCADE to RESTRICT
        // This guarantees user deletion never cascades into transaction/request deletion
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign('transactions_user_id_foreign');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            });
        } catch (\Throwable $e) {
            // Foreign key might already have been adjusted or named differently
        }

        try {
            Schema::table('add_money_requests', function (Blueprint $table) {
                $table->dropForeign('add_money_requests_user_id_foreign');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            });
        } catch (\Throwable $e) {
            // Foreign key might already have been adjusted or named differently
        }

        // 7. Create separate admin_added_moneys table
        if (!Schema::hasTable('admin_added_moneys')) {
            Schema::create('admin_added_moneys', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->decimal('amount', 15, 2);
                $table->decimal('previous_balance', 15, 2)->default(0);
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->string('description')->default('Add Money By Admin');
                $table->string('status')->default('completed');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
            });

            // Backfill existing admin added money from transactions
            try {
                $existingAdminTxs = DB::table('transactions')
                    ->where('description', 'like', '%Add Money By Admin%')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($existingAdminTxs as $tx) {
                    $prevBal = max(0, (float)$tx->available_balance - (float)$tx->amount);
                    DB::table('admin_added_moneys')->insert([
                        'user_id' => $tx->user_id,
                        'transaction_id' => $tx->id,
                        'amount' => $tx->amount,
                        'previous_balance' => $prevBal,
                        'current_balance' => $tx->available_balance,
                        'description' => $tx->description ?? 'Add Money By Admin',
                        'status' => 'completed',
                        'created_at' => $tx->created_at ?? now(),
                        'updated_at' => $tx->updated_at ?? now(),
                    ]);
                }
            } catch (\Throwable $e) {
                // If transactions table doesn't have records yet, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_added_moneys');

        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasColumn('add_money_requests', 'deleted_at')) {
            Schema::table('add_money_requests', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasColumn('withdrawal_money', 'deleted_at')) {
            Schema::table('withdrawal_money', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        if (Schema::hasColumn('play_games', 'deleted_at')) {
            Schema::table('play_games', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
