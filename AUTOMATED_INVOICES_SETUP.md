# Automated Sales Invoices Setup Guide

## Overview

This document describes the setup and usage of the automated sales invoices feature for i4ware SaaS customers at domain `www.i4ware.fi`. The system automatically generates monthly sales invoices for all active customers on a scheduled basis.

## Architecture

### Components

1. **Console Command** (`GenerateSalesInvoices`): Triggered by the task scheduler to generate invoices
2. **Service Layer** (`InvoiceGenerationService`): Business logic for invoice generation
3. **Database Models**: Invoice, Customer, InvoicePaymentTerm, InvoiceRow
4. **API Endpoints**: Configuration and management of automation settings
5. **Console Scheduler** (Laravel Task Scheduler): Schedules daily execution

### Database Schema Changes

The following migrations add necessary columns:
- `invoices.total_amount` - Total invoice amount in EUR
- `invoices.sellers_reference` - Seller's reference code
- `invoices.buyers_reference` - Buyer's reference code
- `customers.number_of_users` - Number of users for SaaS pricing calculation
- `invoice_payment_terms.name` - Payment term name (e.g., "Net 30")

## Installation Steps

### 1. Run Migrations

Run the new migrations to add required database columns:

```bash
cd /path/to/saas-app
php artisan migrate
```

This will execute:
- `2026_05_14_000001_add_total_amount_to_invoices_table.php`
- `2026_05_14_000002_add_number_of_users_to_customers_table.php`
- `2026_05_14_000003_add_name_to_payment_terms_table.php`

### 2. Seed Payment Terms (Optional)

Populate default payment terms for the domain:

```bash
php artisan db:seed --class=InvoicePaymentTermsSeeder
```

This creates standard payment terms:
- Due on receipt (0 days)
- Net 7 (7 days)
- Net 14 (14 days)
- Net 15 (15 days)
- Net 30 (30 days)
- Net 60 (60 days)
- Net 90 (90 days)

With translations in Finnish (FI), English (EN), and Swedish (SV).

## Configuration

### 1. Configure SaaS Pricing

Set the price per user per month via API:

```bash
POST /api/invoices-automation/settings
{
  "saas_price_per_month_per_user": 5.00
}
```

Or via database:
```bash
php artisan tinker
Settings::updateOrCreate(
  ['domain' => 'www.i4ware.fi', 'setting_key' => 'saas_price_per_month_per_user'],
  ['setting_value' => 5.00]
);
```

### 2. Enable Invoice Automation

```bash
POST /api/invoices-automation/settings
{
  "invoice_automation_enabled": true,
  "invoice_generation_day": 25,
  "default_payment_term_id": 3
}
```

**Parameters:**
- `saas_price_per_month_per_user` (float): Price per user per month in EUR
- `invoice_automation_enabled` (boolean): Enable/disable automation
- `invoice_generation_day` (integer, 1-28): Day to start generating invoices
- `default_payment_term_id` (integer): Default payment term ID for customers

### 3. Customer Setup

Ensure each customer has:
- Valid `email` address
- `number_of_users` set (for SaaS pricing calculation)
- `domain` field set to `www.i4ware.fi`
- Not soft-deleted (deleted_at is NULL)

Example customer data:
```json
{
  "name": "Acme Corporation",
  "email": "billing@acme.com",
  "number_of_users": 5,
  "business_id": "1234567-8",
  "vat_id": "FI12345678",
  "domain": "www.i4ware.fi"
}
```

## Scheduler Configuration

The invoice generation is scheduled daily at **02:00 AM** (Helsinki timezone).

### Location: `app/Console/Kernel.php`

```php
$schedule->command('invoices:generate-sales', ['--domain=www.i4ware.fi'])
         ->dailyAt('02:00')
         ->timezone('Europe/Helsinki')
         ->withoutOverlapping()
         ->onFailure(function () {
             Log::error('Sales invoice generation job failed');
         })
         ->onSuccess(function () {
             Log::info('Sales invoice generation job completed successfully');
         });
```

### Running the Scheduler

To enable the scheduler, add this cron job to your Linux/Unix system:

```bash
* * * * * php /path/to/saas-app/artisan schedule:run >> /dev/null 2>&1
```

This cron job runs Laravel's task scheduler every minute. The scheduler will then execute commands that are due.

**For development/testing without cron:**

```bash
php artisan schedule:work
```

## Manual Execution

### Run Invoice Generation Manually

```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi
```

### Dry Run (Preview without Creating)

```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run
```

### Force Generation (Even if not end of month)

```bash
php artisan invoices:generate-sales --domain=www.i4ware.fi --force
```

## Invoice Generation Logic

### When Invoices Are Generated

By default, invoices are generated from the 25th day of the month onwards (via the scheduler at 02:00 AM daily).

- **Invoice Date**: First day of the current month
- **Due Date**: Invoice date + payment term days
- **Status**: `open` (ready for sending/payment)
- **Amount**: `number_of_users × saas_price_per_month_per_user`

### Invoice Number Generation

Invoice numbers are auto-generated sequentially:
- Format: `000001`, `000002`, etc. (6-digit zero-padded)
- Respects custom starting number from `invoice_start_number` setting

### Reference Codes

Two reference codes are generated:
1. **Reference Code**: Generated from invoice number using `ReferenceHelper::generateReferenceNumber()`
2. **SEPA Reference**: Generated using `SepaReference::generate()` for electronic payments

## API Endpoints

### Get Invoice Automation Settings

```bash
GET /api/invoices-automation/settings
```

**Response:**
```json
{
  "success": true,
  "settings": {
    "saas_price_per_month_per_user": {
      "id": 1,
      "setting_key": "saas_price_per_month_per_user",
      "setting_value": "5.00",
      "domain": "www.i4ware.fi"
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

### Update Invoice Automation Settings

```bash
POST /api/invoices-automation/settings
Content-Type: application/json

{
  "saas_price_per_month_per_user": 7.50,
  "invoice_automation_enabled": true,
  "invoice_generation_day": 25,
  "default_payment_term_id": 3
}
```

**Response:**
```json
{
  "success": true,
  "message": "Invoice automation settings updated successfully"
}
```

## Monitoring & Logging

### Log File Location

Invoice generation logs are written to:
```
storage/logs/laravel.log
```

### Log Examples

**Successful generation:**
```
[2026-05-14 02:00:15] local.INFO: Sales invoice generation job completed successfully
[2026-05-14 02:00:15] local.INFO: Generated 15 invoices for domain: www.i4ware.fi
```

**Failed invoice for a customer:**
```
[2026-05-14 02:00:15] local.ERROR: Failed to create invoice for customer 42
[2026-05-14 02:00:15] local.ERROR: Customer has no email address
```

### Checking Execution Status

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Check scheduled commands
php artisan schedule:list

# Test the command
php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run
```

## Troubleshooting

### Issue: No invoices are being generated

**Possible causes:**
1. **Scheduler not running**: Verify cron job exists: `crontab -l`
2. **Not end of month**: Test with `--force` flag
3. **No customers**: Verify customers exist: `SELECT * FROM customers WHERE domain = 'www.i4ware.fi'`
4. **Pricing not set**: Check settings: `SELECT * FROM settings WHERE setting_key = 'saas_price_per_month_per_user'`

**Solutions:**
```bash
# Test manually
php artisan invoices:generate-sales --domain=www.i4ware.fi --force --dry-run

# Check database
php artisan tinker
>>> Customer::where('domain', 'www.i4ware.fi')->count()
>>> Settings::where('setting_key', 'saas_price_per_month_per_user')->first()
```

### Issue: Duplicate invoices created

**Cause**: Command was run multiple times on the same day.

**Prevention**:
- `withoutOverlapping()` is configured in the scheduler to prevent concurrent runs
- Check if cron job is running multiple times: `crontab -l`

### Issue: Invoice amounts are wrong

**Possible causes:**
1. **Wrong pricing**: Check `saas_price_per_month_per_user` setting
2. **Wrong user count**: Verify `customers.number_of_users` value
3. **VAT calculation**: Current implementation uses 0% VAT (customizable)

**Solution:**
```bash
php artisan tinker
>>> $customer = Customer::find(1);
>>> $customer->number_of_users
>>> $setting = Settings::where('setting_key', 'saas_price_per_month_per_user')->first()
>>> $setting->setting_value * $customer->number_of_users
```

## Testing Checklist

Use this checklist to verify the setup:

- [ ] Migrations executed successfully: `php artisan migrate`
- [ ] Database columns exist: Check `invoices`, `customers`, `invoice_payment_terms` tables
- [ ] Payment terms seeded: `php artisan db:seed --class=InvoicePaymentTermsSeeder`
- [ ] Settings configured: SaaS price, automation enabled, payment terms
- [ ] Test customers exist with valid `number_of_users`
- [ ] Dry run successful: `php artisan invoices:generate-sales --domain=www.i4ware.fi --dry-run`
- [ ] Cron job configured: `crontab -e` shows Laravel scheduler
- [ ] Logs show successful generation: `tail -f storage/logs/laravel.log`
- [ ] API endpoints working: Test GET and POST to `/api/invoices-automation/settings`

## Future Enhancements

1. **Invoice Sending**: Email invoices automatically to customer
2. **Payment Integration**: Track payments via Stripe/Paytrail
3. **Custom Templates**: Use domain-specific invoice templates
4. **Bulk Operations**: Generate invoices for multiple domains
5. **Retry Logic**: Automatic retry for failed invoice generations
6. **Notifications**: Admin notifications for failed generations
7. **Audit Trail**: Track all invoice generation activities

## Support

For issues or questions:
1. Check logs: `tail -f storage/logs/laravel.log`
2. Run dry-run tests: `php artisan invoices:generate-sales --dry-run`
3. Verify settings: Use API endpoints to check configuration
4. Check database directly: `php artisan tinker`
