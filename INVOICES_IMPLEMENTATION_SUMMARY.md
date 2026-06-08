# Automated Sales Invoices - Implementation Summary

## Files Created

### 1. Console Command
**File**: `app/Console/Commands/GenerateSalesInvoices.php`
- Command signature: `invoices:generate-sales`
- Options: `--domain`, `--force`, `--dry-run`
- Handles scheduling logic and error reporting
- Integrates with InvoiceGenerationService

### 2. Service Layer
**File**: `app/Services/InvoiceGenerationService.php`
- Main business logic for invoice generation
- Methods:
  - `generateMonthlyInvoices()` - Generate invoices for all customers
  - `createInvoiceForCustomer()` - Create single invoice
  - `getNextInvoiceNumber()` - Auto-generate invoice numbers
  - `getPricingForDomain()` - Fetch pricing configuration

### 3. Database Migrations
- `2026_05_14_000001_add_total_amount_to_invoices_table.php` - Add total_amount, sellers_reference, buyers_reference
- `2026_05_14_000002_add_number_of_users_to_customers_table.php` - Add number_of_users
- `2026_05_14_000003_add_name_to_payment_terms_table.php` - Add payment term name

### 4. Database Seeder
**File**: `database/seeders/InvoicePaymentTermsSeeder.php`
- Creates default payment terms (0, 7, 14, 15, 30, 60, 90 days)
- Includes translations for FI, EN, SV

### 5. API Controller Methods
**File**: `app/Http/Controllers/SettingsController.php`
- `getInvoiceAutomationSettings()` - GET /api/invoices-automation/settings
- `updateInvoiceAutomationSettings()` - POST /api/invoices-automation/settings

### 6. API Routes
**File**: `routes/api.php`
- Added `/invoices-automation/settings` routes

## Model Updates Required

### Customer Model
Ensure the following attributes are fillable:
```php
protected $fillable = [
    'name',
    'number_of_users',  // Add this
    'email',
    'phone_number',
    'business_id',
    'vat_id',
    'address_line_1',
    'address_line_2',
    'zip',
    'city',
    'domain',
];
```

### Invoice Model
Ensure the following attributes are fillable:
```php
protected $fillable = [
    'customer_id',
    'payment_term_id',
    'sellers_reference',
    'buyers_reference',
    'complaints_within',
    'invoice_number',
    'reference_code',
    'sepa_reference',
    'invoice_date',
    'domain',
    'due_date',
    'status',
    'total_amount',  // Add this
];
```

### Settings Model
Already exists and has required columns.

### InvoicePaymentTerm Model
Already exists with translations support.

## Scheduler Configuration

**Location**: `app/Console/Kernel.php`

The schedule runs daily at 02:00 AM Helsinki time:
```php
$schedule->command('invoices:generate-sales', ['--domain=www.i4ware.fi'])
         ->dailyAt('02:00')
         ->timezone('Europe/Helsinki')
         ->withoutOverlapping()
         ->onFailure(...)
         ->onSuccess(...);
```

## System Requirements

1. **Helpers**: Assumes these helpers exist:
   - `App\Helpers\ReferenceHelper::generateReferenceNumber()`
   - `App\Helpers\SepaReference::generate()`

2. **Dependencies**: Already in composer.json:
   - Laravel Framework
   - Carbon (for date handling)

3. **System**: Linux/Unix with cron capability

## Configuration Settings Required

These should be stored in `settings` table:

| Key | Example Value | Description |
|-----|---------------|-------------|
| `saas_price_per_month_per_user` | `5.00` | Price per user monthly |
| `invoice_automation_enabled` | `true` | Enable/disable automation |
| `invoice_generation_day` | `25` | Day to generate invoices |
| `default_payment_term_id` | `3` | Default payment term |

## Execution Flow

```
Linux Cron (every minute)
    ↓
Laravel Scheduler (schedule:run command)
    ↓
GenerateSalesInvoices Console Command (daily at 02:00)
    ↓
InvoiceGenerationService
    ├─ Fetch all active customers for domain
    ├─ For each customer:
    │   ├─ Get payment term (or use default)
    │   ├─ Calculate due date
    │   ├─ Generate invoice number & reference codes
    │   ├─ Create Invoice record
    │   └─ Create InvoiceRow with line items
    └─ Return results with created count & failures
```

## Error Handling

1. **Transaction Management**: Uses DB::beginTransaction() and DB::rollBack() on errors
2. **Per-Customer Error Isolation**: Failure for one customer doesn't stop others
3. **Detailed Logging**: All errors logged to `storage/logs/laravel.log`
4. **Dry Run Support**: Test execution without database modifications

## Security Considerations

1. **Domain Isolation**: Each domain's invoices are isolated via `domain` column
2. **Authentication**: API endpoints require `auth:api` middleware
3. **Validation**: Input validation for all settings
4. **Logging**: All operations logged for audit trail

## Performance Considerations

1. **Batch Processing**: All customer queries optimized with eager loading
2. **No N+1 Queries**: Single query fetches all customers, then processes
3. **Lock Mechanism**: `withoutOverlapping()` prevents concurrent executions
4. **Timezone Aware**: Uses proper timezone configuration

## Testing

Quick test script:
```bash
# Dry run to see what would be generated
php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run

# Generate for testing (include all customers)
php artisan invoices:generate-sales --domain=www.i4ware.fi --force

# Check recent logs
tail -100 storage/logs/laravel.log | grep -i invoice
```

## Integration Checklist

- [ ] All migrations executed
- [ ] Payment terms seeded
- [ ] Settings configured via API or database
- [ ] Console command registered
- [ ] Scheduler configured in Kernel.php
- [ ] Cron job added to system
- [ ] API routes registered
- [ ] Test invoice generation (dry run)
- [ ] Verify invoices in database
- [ ] Check logs for errors
- [ ] Document for team
