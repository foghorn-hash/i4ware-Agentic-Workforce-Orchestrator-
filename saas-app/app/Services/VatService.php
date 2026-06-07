<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VatService
{
    public function validateVAT($vatId)
    {
        $response = Http::get("http://apilayer.net/api/validate", [
            'access_key' => env('VATLAYER_KEY'),
            'vat_number' => $vatId,
        ]);

        return $response->json();
    }
}
