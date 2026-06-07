<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoices;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'name',
        'number_of_users',
        'email',
        'phone_number',
        'business_id',
        'vat_id',
        'address_line_1',
        'address_line_2',
        'zip',
        'city',
        'domain',
        'contact_person_name',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoices::class);
    }
}
