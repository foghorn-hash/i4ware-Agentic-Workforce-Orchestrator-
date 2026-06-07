<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\InvoicePaymentTerm;

class InvoicePaymentTermTranslation extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'invoice_payment_term_translations';
    protected $fillable = [
        'invoice_payment_term_id',
        'locale',
        'name'
    ];

    public function paymentTerm()
    {
        return $this->belongsTo(InvoicePaymentTerm::class);
    }
}
