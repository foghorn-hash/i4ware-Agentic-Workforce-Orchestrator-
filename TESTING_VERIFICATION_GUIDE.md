# Automated Invoices - Testing & Verification Guide

## Pre-Deployment Testing

Use this guide to verify the automated invoicing system works correctly before enabling in production.

## Phase 1: Database & Models

### 1.1 Verify Migrations

Run migrations:
```bash
cd /Users/jayathracm/Desktop/i4ware_ERP/saas-app
php artisan migrate
```

Expected output:
```
Migrating: 2026_05_14_000001_add_total_amount_to_invoices_table
Migrated:  2026_05_14_000001_add_total_amount_to_invoices_table (0.45s)
Migrating: 2026_05_14_000002_add_number_of_users_to_customers_table
Migrated:  2026_05_14_000002_add_number_of_users_to_customers_table (0.32s)
Migrating: 2026_05_14_000003_add_name_to_payment_terms_table
Migrated:  2026_05_14_000003_add_name_to_payment_terms_table (0.28s)
```

### 1.2 Verify Database Columns

```bash
php artisan tinker

# Check invoices table columns
>>> Schema::getColumnListing('invoices');

# Verify new columns exist
>>> Schema::hasColumn('invoices', 'total_amount');           // true
>>> Schema::hasColumn('invoices', 'sellers_reference');     // true
>>> Schema::hasColumn('invoices', 'buyers_reference');      // true

# Check customers table
>>> Schema::hasColumn('customers', 'number_of_users');      // true

# Check payment terms
>>> Schema::hasColumn('invoice_payment_terms', 'name');     // true
```

### 1.3 Seed Payment Terms

```bash
php artisan db:seed --class=InvoicePaymentTermsSeeder

# Verify seeding
php artisan tinker
>>> App\Models\InvoicePaymentTerm::all();
# Should show 7 payment terms with FI, EN, SV translations
```

**Expected Output:**
```
Collection {
  all: [
    {id: 1, domain: "www.i4ware.fi", days_to_pay: 0, name: "Due on receipt"},
    {id: 2, domain: "www.i4ware.fi", days_to_pay: 7, name: "Net 7"},
    {id: 3, domain: "www.i4ware.fi", days_to_pay: 14, name: "Net 14"},
    ...
  ]
}
```

## Phase 2: Configuration

### 2.1 Set Up SaaS Pricing

```bash
php artisan tinker

# Add pricing setting
>>> App\Models\Settings::create([
    'domain' => 'www.i4ware.fi',
    'setting_key' => 'saas_price_per_month_per_user',
    'setting_value' => '5.00'
]);

# Verify
>>> App\Models\Settings::where('setting_key', 'saas_price_per_month_per_user')->first();
```

Expected output:
```
{
  domain: "www.i4ware.fi",
  setting_key: "saas_price_per_month_per_user",
  setting_value: "5.00"
}
```

### 2.2 Create Test Customers

```bash
php artisan tinker

# Create test customers
>>> App\Models\Customer::create([
    'name' => 'Test Company 1',
    'number_of_users' => 3,
    'email' => 'billing1@test.local',
    'domain' => 'www.i4ware.fi'
]);

>>> App\Models\Customer::create([
    'name' => 'Test Company 2',
    'number_of_users' => 5,
    'email' => 'billing2@test.local',
    'domain' => 'www.i4ware.fi'
]);

>>> App\Models\Customer::create([
    'name' => 'Test Company 3',
    'number_of_users' => 2,
    'email' => 'billing3@test.local',
    'domain' => 'www.i4ware.fi'
]);

# Verify
>>> App\Models\Customer::where('domain', 'www.i4ware.fi')->count();  // 3
```

## Phase 3: Command Testing

### 3.1 Test Dry Run

```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run
```

**Expected Output:**
```
Starting automated invoice generation for domain: www.i4ware.fi
DRY RUN - No invoices were created. Preview:

  Customer Name         Amount    Due Date
  ─────────────────────────────────────────
  Test Company 1        €15.00    2026-05-15
  Test Company 2        €25.00    2026-05-15
  Test Company 3        €10.00    2026-05-15
```

**Verification Points:**
- ✓ All 3 customers listed
- ✓ Amounts correct: users × price (3×5, 5×5, 2×5)
- ✓ Due date shows (should be current date + 14 days for Net 14)
- ✓ No invoices in database (use `--dry-run`)

### 3.2 Test Actual Invoice Generation

```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi --force
```

**Expected Output:**
```
Starting automated invoice generation for domain: www.i4ware.fi
✓ Successfully generated 3 invoices

  Customer         Invoice Number    Amount    Due Date
  ───────────────────────────────────────────────────────
  Test Company 1   000001           €15.00    2026-05-15
  Test Company 2   000002           €25.00    2026-05-15
  Test Company 3   000003           €10.00    2026-05-15
```

### 3.3 Verify Invoices in Database

```bash
php artisan tinker

# Count invoices
>>> App\Models\Invoice::where('domain', 'www.i4ware.fi')->count();  // 3

# View detailed invoice
>>> $inv = App\Models\Invoice::first();
>>> $inv->load('customer');
>>> print_r([
    'invoice_number' => $inv->invoice_number,
    'customer' => $inv->customer->name,
    'amount' => $inv->total_amount,
    'due_date' => $inv->due_date,
    'status' => $inv->status,
    'reference_code' => $inv->reference_code,
]);

# Verify amounts
>>> App\Models\Invoice::sum('total_amount');  // Should be 50.00
```

**Expected Invoice Details:**
```
Array {
  invoice_number: "000001",
  customer: "Test Company 1",
  amount: "15.00",
  due_date: "2026-05-15",
  status: "open",
  reference_code: "[generated code]"
}
```

### 3.4 Test Invoice Rows

```bash
php artisan tinker

# Check invoice rows
>>> App\Models\InvoiceRow::all();

# Each invoice should have 1 row
>>> $inv = App\Models\Invoice::first();
>>> $inv->rows;
```

Expected invoice row:
```
{
  invoice_id: 1,
  description: "SaaS Services - Monthly subscription for 3 user(s)",
  quantity: 3,
  unit: "users",
  unit_price: "5.00",
  amount: "15.00"
}
```

## Phase 4: API Testing

### 4.1 Test Settings API - Get

```bash
# Get authorization token first (use your actual token)
TOKEN="your_bearer_token_here"

curl -X GET "http://localhost:8000/api/invoices-automation/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "settings": {
    "saas_price_per_month_per_user": {
      "domain": "www.i4ware.fi",
      "setting_key": "saas_price_per_month_per_user",
      "setting_value": "5.00"
    }
  },
  "payment_terms": [
    {
      "id": 1,
      "days_to_pay": 0,
      "name": "Due on receipt"
    },
    {
      "id": 2,
      "days_to_pay": 7,
      "name": "Net 7"
    }
  ],
  "defaults": {
    "saas_price_per_month_per_user": 5.00,
    "invoice_automation_enabled": true,
    "invoice_generation_day": 25,
    "default_payment_term_id": null
  }
}
```

### 4.2 Test Settings API - Update

```bash
curl -X POST "http://localhost:8000/api/invoices-automation/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "saas_price_per_month_per_user": 7.50,
    "invoice_generation_day": 20
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Invoice automation settings updated successfully"
}
```

### 4.3 Verify Settings Updated

```bash
# Check new price in API
curl -X GET "http://localhost:8000/api/invoices-automation/settings" \
  -H "Authorization: Bearer $TOKEN"

# Or in database
php artisan tinker
>>> Settings::where('setting_key', 'saas_price_per_month_per_user')->first()->setting_value;
```

## Phase 5: Scheduler Testing

### 5.1 List Scheduled Commands

```bash
php artisan schedule:list
```

**Expected Output:**
```
| Command                                           | Interval | Description             |
|----------------------------------------------------|---------|-----------------------|
| invoices:generate-sales --domain=www.i4ware.fi | Daily   | Sales invoice generation |
```

### 5.2 Run Scheduler Once

For testing without cron:

```bash
php artisan schedule:run
```

Check logs:
```bash
tail -50 storage/logs/laravel.log | grep -i invoice
```

### 5.3 Test Scheduler Work Mode

For development (watches and runs every minute):

```bash
php artisan schedule:work
```

Leave running for ~5 minutes and observe output. Should show command execution.

## Phase 6: Error Handling

### 6.1 Test Missing Pricing Setting

```bash
# Remove pricing setting
php artisan tinker
>>> Settings::where('setting_key', 'saas_price_per_month_per_user')->delete();

# Try to generate invoices
>>> exit
php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run
```

**Expected Behavior:**
- Command should still complete
- Invoices generated with 0 price (or default)
- Check logs for any warnings

### 6.2 Test With No Customers

```bash
php artisan tinker
>>> Customer::where('domain', 'www.i4ware.fi')->delete();

>>> exit
php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run
```

**Expected Output:**
```
Starting automated invoice generation for domain: www.i4ware.fi
DRY RUN - No invoices were created. Preview:

(empty table)
```

### 6.3 Test With Soft-Deleted Customers

```bash
php artisan tinker
>>> $cust = Customer::find(1);
>>> $cust->delete();  # Soft delete
>>> exit

php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run
```

**Expected Behavior:**
- Soft-deleted customer ignored
- Not included in generation

### 6.4 Test Duplicate Prevention

```bash
# Run command twice quickly
php artisan invoices:generate-sales --domain=www.i4ware.fi --force
# Immediately run again
php artisan invoices:generate-sales --domain=www.i4ware.fi --force
```

**Expected Behavior:**
- Second run should not create duplicates
- Check invoice count remains same

## Phase 7: Logging & Monitoring

### 7.1 Check Log Format

```bash
tail -100 storage/logs/laravel.log | grep -i "invoice\|Sales"
```

**Expected Log Entries:**
```
[2026-05-14 02:00:15] local.INFO: Sales invoice generation job completed successfully
[2026-05-14 02:00:15] local.INFO: Generated 3 invoices
[2026-05-14 02:00:15] local.INFO: Invoice generation task completed
```

### 7.2 Simulate Error Logging

```bash
# Run with invalid domain
php artisan invoices:generate-sales --domain=invalid.domain
```

**Expected Behavior:**
- Command completes (0 customers found)
- Logs show completion
- No errors

## Phase 8: Performance Testing

### 8.1 Test With Large Customer Count

```bash
php artisan tinker

# Create 100 test customers
>>> for ($i = 1; $i <= 100; $i++) {
    Customer::create([
        'name' => "Test Company $i",
        'number_of_users' => rand(1, 10),
        'email' => "billing$i@test.local",
        'domain' => 'www.i4ware.fi'
    ]);
}

>>> exit

# Time the command
time php artisan invoices:generate-sales --domain=www.i4ware.fi --force
```

**Expected Performance:**
- 100 invoices: < 5 seconds
- 1000 invoices: < 30 seconds
- Check memory usage: < 50MB

## Phase 9: Cron Job Setup Testing

### 9.1 Add Test Cron Entry

```bash
# Edit crontab
crontab -e

# Add this line (runs every minute for 1 hour to test)
* * * * * php /Users/jayathracm/Desktop/i4ware_ERP/saas-app/artisan schedule:run >> /Users/jayathracm/Desktop/i4ware_ERP/cron.log 2>&1

# Save and verify
crontab -l
```

### 9.2 Monitor Cron Execution

```bash
# Watch cron log in real-time
tail -f /Users/jayathracm/Desktop/i4ware_ERP/cron.log

# Or check system logs
log stream --predicate 'process == "cron"' --level debug
```

### 9.3 Verify Cron Runs Command

After 1-2 minutes:
```bash
# Check if Laravel command ran
tail -50 storage/logs/laravel.log | grep -i invoice

# Should show scheduler activity
```

## Final Verification Checklist

Before moving to production:

- [ ] All migrations executed without errors
- [ ] Database columns verified
- [ ] Payment terms seeded (7 terms with translations)
- [ ] Test customers created
- [ ] Pricing setting configured
- [ ] Dry run shows correct preview
- [ ] Invoices created successfully
- [ ] Invoice amounts calculated correctly
- [ ] Invoice numbers auto-incremented
- [ ] Reference codes generated
- [ ] API endpoints working (GET/POST)
- [ ] Settings updatable via API
- [ ] Scheduler command listed: `php artisan schedule:list`
- [ ] Logs show successful execution
- [ ] Error handling works (missing settings, no customers)
- [ ] Soft-deleted customers ignored
- [ ] Performance acceptable (100+ customers < 5 seconds)
- [ ] Cron job added and running
- [ ] No duplicate invoices on re-run

## Troubleshooting Test Failures

| Test | Failure | Solution |
|------|---------|----------|
| Migrations fail | Column exists | Already migrated, safe to ignore |
| Dry run shows 0 | No customers | Add test customers with `number_of_users` |
| Wrong amounts | Wrong price | Verify `saas_price_per_month_per_user` setting |
| Command not found | Not registered | Ensure file in `app/Console/Commands/` |
| API 404 | Routes missing | Run `php artisan route:list` to verify |
| Scheduler not running | Cron not set | Add cron entry: `* * * * * php artisan schedule:run` |
| Duplicate invoices | Race condition | Already handled by `withoutOverlapping()` |

---

**Estimated Testing Time**: 30-45 minutes
**Success Criteria**: All 9 phases completed without critical failures
