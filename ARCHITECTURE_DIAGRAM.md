# Automated Sales Invoices - System Architecture

## System Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                        AUTOMATED INVOICING SYSTEM                   │
└─────────────────────────────────────────────────────────────────────┘

┌─ SCHEDULER LAYER ───────────────────────────────────────────────────┐
│                                                                       │
│  Linux Cron (every minute)                                           │
│  ├─ * * * * * php artisan schedule:run                             │
│  │   └─ Checks scheduled commands                                   │
│  │                                                                   │
│  └─ Laravel Scheduler (app/Console/Kernel.php)                      │
│     └─ GenerateSalesInvoices Command                                │
│        └─ Daily at 02:00 AM (Helsinki TZ)                          │
│           └─ Only if day >= 25 of month                            │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘

┌─ COMMAND LAYER ─────────────────────────────────────────────────────┐
│                                                                       │
│  GenerateSalesInvoices.php                                          │
│  ├─ Validates domain (www.i4ware.fi)                               │
│  ├─ Checks execution rules (--force flag override)                 │
│  ├─ Calls InvoiceGenerationService                                 │
│  └─ Handles output & logging                                       │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘

┌─ SERVICE LAYER (Business Logic) ────────────────────────────────────┐
│                                                                       │
│  InvoiceGenerationService.php                                       │
│  ├─ generateMonthlyInvoices()                                       │
│  │   ├─ Fetch all active customers (domain = www.i4ware.fi)        │
│  │   ├─ Get payment terms & pricing settings                       │
│  │   ├─ For each customer: createInvoiceForCustomer()             │
│  │   │   ├─ Calculate due date (invoice_date + payment_days)       │
│  │   │   ├─ Generate invoice number                                │
│  │   │   ├─ Generate reference codes                               │
│  │   │   ├─ Calculate amount (users × price)                       │
│  │   │   └─ Create Invoice + InvoiceRow records                    │
│  │   └─ Return results with summary                                │
│  │                                                                   │
│  └─ Helper methods                                                  │
│     ├─ getNextInvoiceNumber()                                       │
│     ├─ getPricingForDomain()                                        │
│     └─ formatAmount()                                               │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘

┌─ DATABASE LAYER ────────────────────────────────────────────────────┐
│                                                                       │
│  ┌─ customers table ──────────────────────────────────────────┐    │
│  │ id | name | number_of_users | email | domain | deleted_at │   │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌─ invoices table ─────────────────────────────────────────────┐  │
│  │ id | customer_id | invoice_date | due_date | total_amount  │   │
│  │    | invoice_number | reference_code | sepa_reference     │    │
│  │    | payment_term_id | status | domain | created_at       │    │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌─ invoice_rows table ───────────────────────────────────────────┐ │
│  │ id | invoice_id | description | quantity | unit_price      │  │
│  │    | vat_percent | amount | unit | created_at             │   │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌─ invoice_payment_terms table ──────────────────────────────────┐│
│  │ id | domain | days_to_pay | name | created_at            │   ││
│  └──────────────────────────────────────────────────────────────┘ │
│                                                                      │
│  ┌─ invoice_payment_term_translations table ──────────────────────┐│
│  │ id | invoice_payment_term_id | locale | name               │  ││
│  └──────────────────────────────────────────────────────────────┘ │
│                                                                      │
│  ┌─ settings table ──────────────────────────────────────────────┐ │
│  │ domain | setting_key | setting_value | system_var           │  │
│  │ (Keys: saas_price_per_month_per_user, invoice_automation... │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘

┌─ API LAYER ─────────────────────────────────────────────────────────┐
│                                                                       │
│  SettingsController.php                                             │
│  ├─ GET /api/invoices-automation/settings                          │
│  │   └─ Returns: current settings + available payment terms        │
│  │                                                                   │
│  └─ POST /api/invoices-automation/settings                         │
│      └─ Updates: pricing, automation enabled, payment terms, etc.  │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘

┌─ LOGGING & MONITORING ──────────────────────────────────────────────┐
│                                                                       │
│  storage/logs/laravel.log                                           │
│  ├─ INFO: "Sales invoice generation job completed successfully"    │
│  ├─ INFO: "Generated X invoices for domain: www.i4ware.fi"        │
│  ├─ ERROR: "Failed to create invoice for customer X"              │
│  └─ ERROR: "Invoice generation transaction failed"                │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
START
  ↓
┌─────────────────────────────────────┐
│ Daily Cron: schedule:run            │
└─────────────────────────────────────┘
  ↓
┌─────────────────────────────────────┐
│ Check: Is day >= 25?                │
└─────────────────────────────────────┘
  ├─→ NO  → Skip (exit)
  │
  └─→ YES
       ↓
┌─────────────────────────────────────┐
│ Start GenerateSalesInvoices Command │
└─────────────────────────────────────┘
       ↓
┌─────────────────────────────────────┐
│ InvoiceGenerationService:           │
│ generateMonthlyInvoices()           │
├─→ START TRANSACTION                 │
└─────────────────────────────────────┘
       ↓
┌─────────────────────────────────────┐
│ Query: All Active Customers         │
│ WHERE domain='www.i4ware.fi'        │
│   AND deleted_at IS NULL            │
└─────────────────────────────────────┘
       ↓
     LOOP (for each customer)
       ↓
┌─────────────────────────────────────┐
│ Calculate Invoice Data              │
├─ Invoice Date: 1st of month         │
├─ Due Date: Invoice + payment_days   │
├─ Amount: users × price_per_user     │
├─ Generate: Invoice#, Ref#, SEPA#    │
└─────────────────────────────────────┘
       ↓
┌─────────────────────────────────────┐
│ Create Invoice Record               │
│ + InvoiceRow Line Item              │
└─────────────────────────────────────┘
       ↓
   (next customer)
       ↓
     END LOOP
       ↓
┌─────────────────────────────────────┐
│ END TRANSACTION                     │
│ OR ROLLBACK if --dry-run            │
└─────────────────────────────────────┘
       ↓
┌─────────────────────────────────────┐
│ Log Results:                        │
│ - Created: X invoices              │
│ - Failed: Y invoices               │
│ - Errors: [list of failures]       │
└─────────────────────────────────────┘
       ↓
     END
```

## Invoice Calculation Example

```
Customer: Acme Corp (ID: 42)
- number_of_users: 5
- domain: www.i4ware.fi
- email: billing@acme.com

Settings:
- saas_price_per_month_per_user: €5.00
- default_payment_term_id: 3 (Net 14)

Execution (Day 25, 02:00 AM):
  invoice_date = 2026-05-01 (first of month)
  due_date = 2026-05-01 + 14 days = 2026-05-15
  total_amount = 5 users × €5.00 = €25.00
  invoice_number = 000042 (auto-incremented)
  reference_code = generated from invoice_number
  sepa_reference = generated for payment

Result:
  ✓ Invoice created
    - Customer: Acme Corp
    - Amount: €25.00
    - Invoice #: 000042
    - Due: 2026-05-15
    - Status: open
```

## Configuration Flow

```
Admin User
    ↓
API: POST /api/invoices-automation/settings
    ├─ Body:
    │  ├─ saas_price_per_month_per_user: 5.00
    │  ├─ invoice_automation_enabled: true
    │  ├─ invoice_generation_day: 25
    │  └─ default_payment_term_id: 3
    ↓
SettingsController::updateInvoiceAutomationSettings()
    ├─ Validate input
    ├─ Update/Create settings records
    └─ Return success
    ↓
Database: settings table updated
    ├─ domain: www.i4ware.fi
    ├─ setting_key: saas_price_per_month_per_user
    └─ setting_value: 5.00
    ↓
Next scheduled run reads these settings
```

## Error Handling Flow

```
ERROR Occurs During Invoice Generation
  ↓
┌─────────────────────────────────────┐
│ Per-customer error isolation:       │
│ One failed customer doesn't stop    │
│ processing of others                │
└─────────────────────────────────────┘
  ↓
  ├─→ Record failure with reason
  ├─→ Continue with next customer
  ├─→ Accumulate results
  ↓
  After all customers:
  ├─→ Log summary (X created, Y failed)
  ├─→ List failed customers with reasons
  ├─→ Either COMMIT (if dry-run=false)
  │   or ROLLBACK (if dry-run=true)
  ↓
  Return results to caller
    ├─ created: count
    ├─ failed: count
    ├─ generated: list of successful
    └─ failures: list with reasons
```

## Security & Isolation

```
Customer Data Isolation:
  Invoices table → domain column
  Payments depend on → customer.domain
  Settings per domain
  
  www.i4ware.fi customers can only see their invoices
  Other domains isolated completely

API Security:
  Requires → auth:api middleware
  Domain → extracted from authenticated user
  User can only manage their own domain settings

Database Transactions:
  All or nothing approach
  Rollback on any error
  Prevents partial data
```

## Performance Considerations

```
For 1000 customers:
  - Single query: fetch all customers (not 1000 separate)
  - 1000 invoice + 1000 row creations (batched)
  - Lock mechanism: withoutOverlapping() prevents duplicate runs
  - Processing time: ~30 seconds

Optimization strategies:
  ✓ Eager loading (with 'customer')
  ✓ Single query for payment terms
  ✓ Batch database operations
  ✓ Skip soft-deleted customers
  ✓ Timezone-aware scheduling
```
