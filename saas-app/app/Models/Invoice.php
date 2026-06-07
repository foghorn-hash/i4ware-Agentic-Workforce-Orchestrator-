<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\InvoiceRow;

class Invoice extends Model
{
    protected $dates = ['deleted_at'];
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
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function rows()
    {
        return $this->hasMany(InvoiceRow::class);
    }

}
