<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\InvoicePaymentTerm;
use App\Models\InvoiceRow;
use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\ReferenceHelper;
use App\Helpers\SepaReference;

class InvoiceGenerationService
{
    /**
     * Generate monthly sales invoices for all active customers of a domain
     *
     * @param string $domain
     * @param bool $dryRun
     * @return array
     */
    public function generateMonthlyInvoices(string $domain, bool $dryRun = false): array
    {
        $result = [
            'created' => 0,
            'failed' => 0,
            'generated' => [],
            'failures' => [],
            'preview' => [],
        ];

        try {
            DB::beginTransaction();

            $isProviderDomain = false;
            $providerCustomer = null;
            $providerDomain = Domain::where('domain', $domain)->first();

            if ($providerDomain && $providerDomain->is_admin) {
                $isProviderDomain = true;
                $providerCustomer = $this->ensureCustomerRecordForDomain($domain, $domain);
            }

            $customers = $this->getInvoiceCustomersForDomain($domain);

            if ($customers->isEmpty()) {
                Log::info("No invoice customers found for domain: {$domain}");
                DB::commit();
                return $result;
            }

            // Get default payment term for domain (fallback to Net 30)
            $defaultPaymentTerm = InvoicePaymentTerm::where('domain', $domain)
                ->orderBy('created_at')
                ->first();

            $paymentDays = $defaultPaymentTerm ? $defaultPaymentTerm->days_to_pay : 30;

            // Get SaaS pricing from settings
            $pricePerUserPerMonth = $this->getPricingForDomain($domain);

            foreach ($customers as $customer) {
                try {
                        $invoiceData = $this->createInvoiceForCustomer(
                        $customer,
                        $paymentDays,
                        $pricePerUserPerMonth,
                        $defaultPaymentTerm,
                        $dryRun,
                        $isProviderDomain ? $providerCustomer : null
                    );

                    $invoice = $invoiceData['invoice'];
                    $totalAmount = $invoiceData['total_amount'];

                    if (!$dryRun) {
                        $result['created']++;
                        $result['generated'][] = [
                            $customer->name,
                            $invoice->invoice_number,
                            $this->formatAmount($totalAmount),
                            $invoice->due_date->format('Y-m-d'),
                        ];
                    } else {
                        $result['preview'][] = [
                            $customer->name,
                            $this->formatAmount($totalAmount),
                            $invoice->due_date->format('Y-m-d'),
                        ];
                    }
                } catch (\Exception $e) {
                    $result['failed']++;
                    $result['failures'][] = [
                        'customer' => $customer->name,
                        'reason' => $e->getMessage(),
                    ];
                    Log::error("Failed to create invoice for customer {$customer->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (!$dryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Invoice generation transaction failed", [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $result;
    }

    /**
     * Create a single invoice for a customer, and if this is a provider domain,
     * also create a matching purchase invoice for the customer domain.
     */
    private function createInvoiceForCustomer(
        Customer $customer,
        int $paymentDays,
        float $pricePerUserPerMonth,
        ?InvoicePaymentTerm $paymentTerm,
        bool $dryRun,
        ?Customer $providerCustomer = null
    ): array {
        $now = Carbon::now();
        $invoiceDate = $now->copy()->startOfMonth(); // First day of current month
        $dueDate = $invoiceDate->copy()->addDays($paymentDays);

        $invoiceRows = $this->buildInvoiceRowsForDomain($customer->domain, $pricePerUserPerMonth);

        if (empty($invoiceRows)) {
            throw new \Exception("No active users found for domain {$customer->domain}");
        }

        if ($providerCustomer && $providerCustomer->domain !== $customer->domain) {
            $salesInvoice = $this->createInvoiceRecord(
                $customer,
                $providerCustomer->domain,
                $invoiceDate,
                $dueDate,
                $paymentTerm,
                $invoiceRows,
                $dryRun
            );

            $this->createInvoiceRecord(
                $providerCustomer,
                $customer->domain,
                $invoiceDate,
                $dueDate,
                $paymentTerm,
                $invoiceRows,
                $dryRun
            );

            return [
                'invoice' => $salesInvoice,
                'total_amount' => array_sum(array_column($invoiceRows, 'total_excluding_vat')),
            ];
        }

        $invoice = $this->createInvoiceRecord(
            $customer,
            $customer->domain,
            $invoiceDate,
            $dueDate,
            $paymentTerm,
            $invoiceRows,
            $dryRun
        );

        return [
            'invoice' => $invoice,
            'total_amount' => array_sum(array_column($invoiceRows, 'total_excluding_vat')),
        ];
    }

    private function createInvoiceRecord(
        Customer $customer,
        string $invoiceDomain,
        Carbon $invoiceDate,
        Carbon $dueDate,
        ?InvoicePaymentTerm $paymentTerm,
        array $invoiceRows,
        bool $dryRun
    ): Invoice {
        $nextNumber = $this->getNextInvoiceNumber($invoiceDomain);
        $referenceCode = ReferenceHelper::generate($nextNumber);
        $sepaRef = SepaReference::generate($referenceCode);

        $invoice = new Invoice();
        $invoice->customer_id = $customer->id;
        $invoice->domain = $invoiceDomain;
        $invoice->invoice_number = $nextNumber;
        $invoice->invoice_date = $invoiceDate;
        $invoice->due_date = $dueDate;
        $invoice->reference_code = $referenceCode;
        $invoice->sepa_reference = $sepaRef;
        $invoice->status = 'open';
        $invoice->payment_term_id = $paymentTerm ? $paymentTerm->id : null;

        if (!$dryRun) {
            $invoice->save();

            foreach ($invoiceRows as $rowData) {
                $rowData['invoice_id'] = $invoice->id;
                $rowData['unit_price_including_vat'] = $rowData['unit_price_excluding_vat'];
                $rowData['total_including_vat'] = $rowData['total_excluding_vat'];
                InvoiceRow::create($rowData);
            }
        }

        return $invoice;
    }

    private function ensureCustomerRecordForDomain(string $domain, ?string $name = null): Customer
    {
        return Customer::firstOrCreate(
            ['domain' => $domain],
            ['name' => $name ?? $domain]
        );
    }

    private function buildInvoiceRowsForDomain(string $domain, float $pricePerUserPerMonth): array
    {
        $users = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.domain', $domain)
            ->where('users.is_active', 1)
            ->whereNull('users.deleted_at')
            ->select('roles.name as role_name', 'users.role')
            ->get();

        $roleCounts = [];

        foreach ($users as $user) {
            $roleName = $user->role_name ?: ($user->role ?: 'user');
            $roleCounts[$roleName] = ($roleCounts[$roleName] ?? 0) + 1;
        }

        $rows = [];

        foreach ($roleCounts as $roleName => $quantity) {
            if ($quantity === 0) {
                continue;
            }

            $unitPrice = $pricePerUserPerMonth;
            $totalExcludingVat = $quantity * $unitPrice;

            $rows[] = [
                'description' => "SaaS Services - {$roleName} users",
                'quantity' => $quantity,
                'unit' => 'users',
                'unit_price_excluding_vat' => $unitPrice,
                'vat' => 0.00,
                'total_excluding_vat' => $totalExcludingVat,
            ];
        }

        return $rows;
    }

    /**
     * Get all invoice customers for the provided domain.
     * If the provided domain is an admin SaaS provider, return customers for the provider's subdomains.
     */
    private function getInvoiceCustomersForDomain(string $domain)
    {
        $providerDomain = Domain::where('domain', $domain)->first();

        if (!$providerDomain || !$providerDomain->is_admin) {
            return Customer::where('domain', $domain)
                ->whereNull('deleted_at')
                ->get();
        }

        $rootDomain = $this->getRootDomain($domain);
        $customerDomains = Domain::where('is_admin', false)
            ->where(function ($query) use ($rootDomain) {
                $query->where('domain', 'like', '%.' . $rootDomain)
                      ->orWhere('domain', $rootDomain);
            })
            ->pluck('domain');

        if ($customerDomains->isEmpty()) {
            return collect();
        }

        $existingCustomers = Customer::whereIn('domain', $customerDomains)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('domain');

        $customers = collect();
        foreach ($customerDomains as $customerDomain) {
            if ($existingCustomers->has($customerDomain)) {
                $customers->push($existingCustomers->get($customerDomain));
                continue;
            }

            $customers->push(Customer::create([
                'name' => $customerDomain,
                'domain' => $customerDomain,
            ]));
        }

        return $customers;
    }

    private function getRootDomain(string $domain): string
    {
        $parts = explode('.', $domain);
        if (count($parts) > 2 && strtolower($parts[0]) === 'www') {
            array_shift($parts);
        }

        if (count($parts) > 2) {
            return implode('.', array_slice($parts, -2));
        }

        return implode('.', $parts);
    }

    /**
     * Get the next invoice number for a domain
     */
    private function getNextInvoiceNumber(string $domain): string
    {
        $lastInvoice = Invoice::where('domain', $domain)
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 1;

        if ($lastInvoice && $lastInvoice->invoice_number) {
            // Extract numeric part from invoice number
            preg_match('/(\d+)/', $lastInvoice->invoice_number, $matches);
            if (!empty($matches[1])) {
                $lastNumber = (int) $matches[1];
            }
        }

        $nextNumber = $lastNumber + 1;

        // Check if custom starting number is set in settings
        $startingSetting = Settings::where('domain', $domain)
            ->where('setting_key', 'invoice_start_number')
            ->first();

        if ($startingSetting && $nextNumber < (int) $startingSetting->setting_value) {
            $nextNumber = (int) $startingSetting->setting_value;
        }

        return str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get pricing configuration for domain
     */
    private function getPricingForDomain(string $domain): float
    {
        $setting = Settings::where('domain', $domain)
            ->where('setting_key', 'saas_price_per_month_per_user')
            ->first();

        return $setting ? (float) $setting->setting_value : 0.0;
    }

    /**
     * Format amount as currency string
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }
}
