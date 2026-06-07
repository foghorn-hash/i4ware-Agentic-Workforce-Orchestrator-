<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceRow extends Model
{
    use HasFactory;

    protected $table = 'invoice_rows';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price_excluding_vat',
        'vat',
        'total_excluding_vat',
        'total_including_vat',
        'unit',
        'domain'
    ];

    /**
     * Relationship: Each row belongs to an invoice.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
