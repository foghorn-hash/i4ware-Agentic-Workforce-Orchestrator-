# Quick Start: Automated Sales Invoices for www.i4ware.fi

## ✅ What's Been Set Up

A complete automated sales invoicing system has been implemented for your SaaS customers. Here's what was created:

### Core Components

1. **Console Command** (`GenerateSalesInvoices`)
   - Generates invoices automatically based on configuration
   - Supports dry-run mode for testing
   - Handles errors gracefully with detailed logging

2. **Service Layer** (`InvoiceGenerationService`)
   - Business logic for invoice creation
   - Calculates amounts based on user count and pricing
   - Generates invoice numbers and reference codes

3. **Database Schema**
   - Added `total_amount` to invoices
   - Added `number_of_users` to customers
   - Enhanced payment terms with translations

4. **Scheduler** (Laravel Task Scheduler)
   - Scheduled to run daily at **2:00 AM** (Helsinki time)
   - From the 25th onwards each month

5. **API Endpoints**
   - GET/POST `/api/invoices-automation/settings` - Manage configuration

## 🚀 Next Steps (In Order)

### Step 1: Run Database Migrations (2 minutes)

```bash
cd /Users/jayathracm/Desktop/i4ware_ERP/saas-app
php artisan migrate
```

This creates the necessary database columns.

### Step 2: Seed Payment Terms (1 minute)

```bash
php artisan db:seed --class=InvoicePaymentTermsSeeder
```

Creates default payment terms (Net 7, Net 30, etc.) with translations.

### Step 3: Configure SaaS Pricing (5 minutes)

Set your monthly pricing per user. Choose ONE method:

**Option A: Via API (Recommended)**
```bash
curl -X POST http://localhost:8000/api/invoices-automation/settings \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "saas_price_per_month_per_user": 5.00,
    "invoice_automation_enabled": true,
    "invoice_generation_day": 25
  }'
```

**Option B: Via Tinker (Database)**
```bash
php artisan tinker
>>> App\Models\Settings::updateOrCreate(
    ['domain' => 'www.i4ware.fi', 'setting_key' => 'saas_price_per_month_per_user'],
    ['setting_value' => 5.00]
);
```

### Step 4: Set Customer User Counts (5 minutes)

Each customer needs a `number_of_users` value for pricing calculation:

```bash
php artisan tinker
>>> $customer = App\Models\Customer::find(1);
>>> $customer->update(['number_of_users' => 5]);
```

Or via your existing customer management API.

### Step 5: Test Locally (5 minutes)

**Dry run (safe, shows preview):**
```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run
```

**Force generation for testing (will create invoices):**
```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi --force
```

**Check results in database:**
```bash
php artisan tinker
>>> App\Models\Invoice::latest()->take(5)->get();
```

### Step 6: Set Up Cron Job (2 minutes)

Add this line to your Linux/Unix cron:

```bash
* * * * * php /Users/jayathracm/Desktop/i4ware_ERP/saas-app/artisan schedule:run >> /dev/null 2>&1
```

Edit crontab:
```bash
crontab -e
# Paste the line above
```

For **development/testing without cron**, run this instead:
```bash
php artisan schedule:work
```

### Step 7: Monitor & Verify (Ongoing)

Check logs for successful execution:
```bash
tail -f /Users/jayathracm/Desktop/i4ware_ERP/saas-app/storage/logs/laravel.log | grep -i invoice
```

Expected log entry:
```
[2026-05-14 02:00:15] local.INFO: Sales invoice generation job completed successfully
```

## 📋 Configuration Summary

### Required Settings

| Setting | Value | Notes |
|---------|-------|-------|
| `saas_price_per_month_per_user` | e.g., 5.00 | EUR per user monthly |
| `invoice_automation_enabled` | true | Enable automation |
| `invoice_generation_day` | 25 | Start generating from 25th |
| `default_payment_term_id` | 3 (Net 14) | Default payment terms |

### Customer Requirements

Each customer must have:
- `name` - Company name
- `email` - Billing email
- `number_of_users` - Count for SaaS pricing
- `domain` - Must be 'www.i4ware.fi'
- `deleted_at` - Must be NULL (not soft-deleted)

## 🎯 How It Works

```
Daily at 2:00 AM (via Cron)
    ↓
Scheduler checks if day >= 25
    ↓
If yes: Fetch all active customers for www.i4ware.fi
    ↓
For each customer:
  - Calculate: number_of_users × price_per_month_per_user = total
  - Generate invoice number (e.g., 000042)
  - Generate reference codes for payments
  - Create Invoice record with status='open'
    ↓
Store in database and log results
```

## 🧪 Testing Checklist

Before going live, verify:

- [ ] Migrations executed: `php artisan migrate:status`
- [ ] Pricing configured: Check DB or API
- [ ] Test customers have `number_of_users` set
- [ ] Dry run works: `php artisan invoices:generate-sales --dry-run`
- [ ] Test run creates invoices: Check DB table `invoices`
- [ ] Logs show success: Check `storage/logs/laravel.log`
- [ ] Cron job exists: `crontab -l`
- [ ] Scheduler runs: Check logs at scheduled time (or use `schedule:work`)

## 🔧 Common Tasks

### View Current Configuration
```bash
curl http://localhost:8000/api/invoices-automation/settings \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Update Pricing
```bash
curl -X POST http://localhost:8000/api/invoices-automation/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"saas_price_per_month_per_user": 7.50}'
```

### Generate Invoices Immediately (for testing)
```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi --force
```

### View Generated Invoices
```bash
php artisan tinker
>>> App\Models\Invoice::where('domain', 'www.i4ware.fi')->latest()->get();
```

### Check Scheduled Commands
```bash
php artisan schedule:list
```

## 📚 Documentation Files

Detailed documentation is available:

1. **[AUTOMATED_INVOICES_SETUP.md](./AUTOMATED_INVOICES_SETUP.md)** - Complete setup guide
2. **[INVOICES_IMPLEMENTATION_SUMMARY.md](./INVOICES_IMPLEMENTATION_SUMMARY.md)** - Technical details

## ⚠️ Troubleshooting

**No invoices generated?**
1. Check scheduler is running: `php artisan schedule:work`
2. Verify settings: `php artisan tinker` → `Settings::all()`
3. Check customer count: `Customer::where('domain', 'www.i4ware.fi')->count()`
4. Run dry-run: `php artisan invoices:generate-sales --dry-run --force`

**Wrong invoice amounts?**
1. Check pricing: `Settings::where('setting_key', 'saas_price_per_month_per_user')->first()`
2. Check customer users: `Customer::find(1)->number_of_users`
3. Formula: `users × price = total`

**Duplicate invoices?**
- Scheduler has `withoutOverlapping()` to prevent this
- Check if cron is running multiple times: `ps aux | grep artisan`

## 📞 Need Help?

1. Check logs: `tail -f storage/logs/laravel.log`
2. Run dry-run test: `php artisan invoices:generate-sales --dry-run`
3. Use Tinker to inspect data: `php artisan tinker`
4. Review setup documentation: See files above

---

**Setup completed**: ✅ All code is ready
**Next step**: Execute migration and configuration (Steps 1-4 above)
**Timeline**: Should be live within 30 minutes
