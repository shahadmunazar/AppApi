<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\PlayGame;
use App\Models\Transaction;
use Illuminate\Console\Command;

class PlayedGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'played:game';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and process played games for categories with status opened';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Initialize an array to store non-matching totals
        $non_matching_totals = [];

        // Fetch the category with status 'opened'
        $category = Category::where('status', 'opened')->first();
        $category_id = $category->id;
        $today_numbers = $category->no_open;

        if ($category_id) {
            $playGames = PlayGame::where('category_id', $category_id)->get();

            foreach ($playGames as $playgame) {
                $playgame_id = $playgame->play_game_id;
                $entered_numbers_today = $playgame->entered_number;
                $user_id = $playgame->user_id;
                $entered_amount_today = $playgame->entered_amount;

                if ($today_numbers === $entered_numbers_today) {
                    // Numbers match
                    if ($playgame_id == 2) {
                        $won_price_game = $entered_amount_today * 9;
                        $this->info('Check for same numbers: ' . $won_price_game . ', User ID: ' . $user_id);

                        // Add a transaction for a win
                        Transaction::create([
                            'user_id' => $user_id,
                            'transaction_type' => 'won',
                            'amount' => $won_price_game,
                            'available_balance' => $won_price_game,
                        ]);
                    } elseif (in_array($playgame_id, [1, 3, 4])) {
                        $won_price_game = $entered_amount_today * 95;
                        $this->info('This is matching numbers for rest of all: ' . $won_price_game . ', User ID: ' . $user_id);

                        // Add a transaction for a win
                        Transaction::create(['user_id' => $user_id,
                            'transaction_type' => 'won',
                            'amount' => $won_price_game
                        ]);
                    }
                } else {
                    // Numbers do not match
                    if (!isset($non_matching_totals[$user_id])) {
                        $non_matching_totals[$user_id] = 0;
                    }
                    $non_matching_totals[$user_id] += $entered_amount_today;
                    $this->info('These Numbers Are not matched: ' . $entered_amount_today . ', User ID: ' . $user_id);
                }
            }

            // Add transactions for losses
            foreach ($non_matching_totals as $user_id => $total_amount) {
                Transaction::create(['user_id' => $user_id,
                    'transaction_type' => 'loss',
                    'amount' => $total_amount,
                    'available_balance' => $total_amount
                ]);
                $this->info('User ID: ' . $user_id . ' Total non-matching amount (loss): ' . $total_amount);
            }
        }

        return 0;
    }
}
