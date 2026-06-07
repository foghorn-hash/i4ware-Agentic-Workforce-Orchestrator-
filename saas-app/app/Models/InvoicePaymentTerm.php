<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoices;
use App\Models\InvoicePaymentTermTranslation;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePaymentTerm extends Model
{
    use HasFactory;

    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'invoice_payment_terms';

    protected $fillable = [
        'domain',
        'days_to_pay',
    ];

    public function translations()
    {
        return $this->hasMany(InvoicePaymentTermTranslation::class);
    }

    public function translatedName($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations
            ->where('locale', strtoupper($locale))
            ->first()
            ->name ?? $this->payment_term_name ?? null;
    }

    /**
     * Relaatio: yksi maksuehto voi kuulua usealle laskulle.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'payment_term_id');
    }
}
