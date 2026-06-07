<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'domain',
        'order_number',
        'vendor_id',
        'order_date',
        'total_amount',
        'status'
    ];

    /**
     * Scope a query to a specific domain.
     */
    public function scopeForDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }
}
