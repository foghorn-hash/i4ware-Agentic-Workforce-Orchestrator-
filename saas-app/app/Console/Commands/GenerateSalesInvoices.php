<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceGenerationService;
use Carbon\Carbon;

class GenerateSalesInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-sales
                            {--domain=www.i4ware.fi : Domain to generate invoices for}
                            {--force : Force generation even if it\'s not end of month}
                            {--dry-run : Show what would be generated without creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate sales invoices for SaaS customers at the end of the month';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceGenerationService $invoiceService)
    {
        $domain = $this->option('domain');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("Starting automated invoice generation for domain: {$domain}");

        // Check if it's safe to run (end of month or force flag)
        if (!$force && !$this->shouldGenerateInvoices()) {
            $this->warn("Not at end of month. Use --force flag to generate invoices anyway.");
            return Command::SUCCESS;
        }

        try {
            // Generate invoices
            $result = $invoiceService->generateMonthlyInvoices(
                $domain,
                $dryRun
            );

            if ($dryRun) {
                $this->info("DRY RUN - No invoices were created. Preview:");
                $this->table(
                    ['Customer Name', 'Amount', 'Due Date'],
                    $result['preview']
                );
            } else {
                $this->info("✓ Successfully generated {$result['created']} invoices");

                if (!empty($result['failed'])) {
                    $this->warn("⚠ Failed to generate invoices for {$result['failed']} customers");
                    foreach ($result['failures'] as $failure) {
                        $this->line("  - {$failure['customer']}: {$failure['reason']}");
                    }
                }

                $this->table(
                    ['Customer', 'Invoice Number', 'Amount', 'Due Date'],
                    $result['generated']
                );
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error generating invoices: {$e->getMessage()}");
            \Log::error('Invoice generation failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Check if we should generate invoices (near end of month)
     * Generates invoices from 25th to end of month
     */
    private function shouldGenerateInvoices(): bool
    {
        $now = Carbon::now();
        $dayOfMonth = $now->day;
        $daysInMonth = $now->daysInMonth;

        // Generate invoices from 25th onwards each month
        return $dayOfMonth >= 25;
    }
}
