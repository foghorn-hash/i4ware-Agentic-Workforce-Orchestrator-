<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InvoicePaymentTerm;
use App\Models\InvoicePaymentTermTranslation;

class InvoicePaymentTermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domain = 'www.i4ware.fi';

        // Payment terms definitions
        $terms = [
            ['days' => 0, 'name_fi' => 'Due on receipt', 'name_en' => 'Due on receipt', 'name_sv' => 'Förfallet vid mottagandet'],
            ['days' => 7, 'name_fi' => 'Net 7', 'name_en' => 'Net 7', 'name_sv' => 'Netto 7'],
            ['days' => 14, 'name_fi' => 'Net 14', 'name_en' => 'Net 14', 'name_sv' => 'Netto 14'],
            ['days' => 15, 'name_fi' => 'Net 15', 'name_en' => 'Net 15', 'name_sv' => 'Netto 15'],
            ['days' => 30, 'name_fi' => 'Net 30', 'name_en' => 'Net 30', 'name_sv' => 'Netto 30'],
            ['days' => 60, 'name_fi' => 'Net 60', 'name_en' => 'Net 60', 'name_sv' => 'Netto 60'],
            ['days' => 90, 'name_fi' => 'Net 90', 'name_en' => 'Net 90', 'name_sv' => 'Netto 90'],
        ];

        foreach ($terms as $termData) {
            $paymentTerm = InvoicePaymentTerm::firstOrCreate(
                [
                    'domain' => $domain,
                    'days_to_pay' => $termData['days'],
                ],
                [
                    'name' => $termData['name_fi'],
                ]
            );

            // Create translations for each language
            $locales = ['FI' => $termData['name_fi'], 'EN' => $termData['name_en'], 'SV' => $termData['name_sv']];

            foreach ($locales as $locale => $name) {
                InvoicePaymentTermTranslation::firstOrCreate(
                    [
                        'invoice_payment_term_id' => $paymentTerm->id,
                        'locale' => $locale,
                    ],
                    [
                        'name' => $name,
                    ]
                );
            }
        }
    }
}
