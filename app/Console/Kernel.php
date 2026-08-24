<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('whats:message')
            ->everyTenMinutes() 
            // ->between('06:00', '23:59') 
            ->timezone('Asia/Kolkata');
            $schedule->command('whats:messageone')
            ->everyFourMinutes() 
            // ->between('06:00', '23:59') 
            ->timezone('Asia/Kolkata');
            $schedule->command('whats:messagetwo')
            ->everyFiveMinutes() 
            // ->between('06:00', '23:59') 
            ->timezone('Asia/Kolkata');
            $schedule->command('whats:messagethree')
            ->everyThreeMinutes() 
            // ->between('06:00', '23:59') 
            ->timezone('Asia/Kolkata');
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
