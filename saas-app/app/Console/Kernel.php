<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Generate monthly sales invoices for SaaS customers
        // Runs daily, but only executes when conditions are met (day >= 25)
        $schedule->command('invoices:generate-sales', ['--domain=www.i4ware.fi'])
                 ->dailyAt('02:00') // Run at 2 AM daily
                 ->timezone('Europe/Helsinki')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Log::error('Sales invoice generation job failed');
                 })
                 ->onSuccess(function () {
                     \Log::info('Sales invoice generation job completed successfully');
                 });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
