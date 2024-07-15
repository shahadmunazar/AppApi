<?php
namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Played_Game;
use App\Models\SubCategory;
use App\Models\Transaction;
use App\Models\User;
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
        // Fetch the category with status 'opened'
        $category = Category::where('status', 'opened')->first();

        if ($category) {
            // Get the category ID and opened number
            $category_id = $category->id;
            $opened_number = $category->no_open;

            // Fetch all subcategories of the opened category
            $subCategories = SubCategory::where('category_id', $category_id)->get();

            foreach ($subCategories as $subCategory) {
                // Fetch Played_Game records for each subcategory with the specified conditions
                $playedGames = Played_Game::where('category_id', $category_id)
                    ->where('play_game_id', $subCategory->id)
                    ->whereNull('today_number')
                    ->whereNull('open_time_number')
                    ->whereIn('status', ['not_opened', 'waiting'])
                    ->get();

                // Process each played game
                foreach ($playedGames as $playedGame) {
                    $entered_number = $playedGame->entered_number;
                    $entered_amount = $playedGame->entered_amount;
                    $user_play_game_id = $playedGame->user_id;

                    // Fetch the user associated with the played game
                    $user = User::find($user_play_game_id);

                    if ($user) {
                        if ($entered_number === $opened_number) {
                            $won_amount = $entered_amount * 90;
                            $user->balance += $won_amount;
                            // $user->available_balance += $won_amount;

                            Transaction::create([
                                'user_id' => $playedGame->user_id,
                                'amount' => $won_amount,
                                'transaction_type' => 'won',
                                'description' => 'Won the game',
                                'available_balance' => $user->balance,
                            ]);

                            $playedGame->today_number = $opened_number;
                            $playedGame->open_time_number = now();
                            $playedGame->status = 'won';
                        } else {
                            Transaction::create([
                                'user_id' => $playedGame->user_id,
                                'amount' => $entered_amount,
                                'transaction_type' => 'loss',
                                'description' => 'Lost the game',
                                'available_balance' => $user->available_balance,
                            ]);

                            $playedGame->today_number = $opened_number;
                            $playedGame->after_open_number_block = $opened_number; // Assuming the same number is used
                            $playedGame->open_time_number = now();
                            $playedGame->status = 'lost';
                        }

                        // Save the updated user and played game records
                        $user->save();
                        $playedGame->save();
                    }
                }
            }
        } else {
            // Handle case when no category with status 'opened' is found
            $this->info('No category with status "opened" found');
        }

        return 0;
    }
}
