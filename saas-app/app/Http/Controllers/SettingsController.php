<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Domain;
use App\Models\Settings;
use App\Models\InvoicePaymentTerm;
use Auth;
use Validator;
use Illuminate\Support\Facades\Hash;
use DB;
use Illuminate\Support\Carbon;

class SettingsController extends Controller
{
	protected $user;

    public function settings(Request $request)
    {
        $user = Auth::user();

        $settings = DB::table('settings')->where('domain', env('APP_DOMAIN_ADMIN'))->where('system_var', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $settings
         ], 200);
    }

    /**
     * Get invoice automation settings for the domain
     */
    public function getInvoiceAutomationSettings(Request $request)
    {
        $user = Auth::user();
        $domain = $user->domain;

        $settings = Settings::where('domain', $domain)
            ->whereIn('setting_key', [
                'saas_price_per_month_per_user',
                'invoice_automation_enabled',
                'invoice_generation_day',
                'default_payment_term_id',
            ])
            ->get()
            ->keyBy('setting_key');

        // Get available payment terms
        $paymentTerms = InvoicePaymentTerm::where('domain', $domain)
            ->whereNull('deleted_at')
            ->get(['id', 'days_to_pay', 'name']);

        return response()->json([
            'success' => true,
            'settings' => $settings,
            'payment_terms' => $paymentTerms,
            'defaults' => [
                'saas_price_per_month_per_user' => 5.00,
                'invoice_automation_enabled' => true,
                'invoice_generation_day' => 25, // Generate from 25th onwards
                'default_payment_term_id' => null,
            ]
        ], 200);
    }

    /**
     * Update invoice automation settings
     */
    public function updateInvoiceAutomationSettings(Request $request)
    {
        $user = Auth::user();
        $domain = $user->domain;

        $validator = Validator::make($request->all(), [
            'saas_price_per_month_per_user' => 'nullable|numeric|min:0',
            'invoice_automation_enabled' => 'nullable|boolean',
            'invoice_generation_day' => 'nullable|integer|between:1,28',
            'default_payment_term_id' => 'nullable|exists:invoice_payment_terms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update settings
        $settingsToUpdate = [
            'saas_price_per_month_per_user',
            'invoice_automation_enabled',
            'invoice_generation_day',
            'default_payment_term_id',
        ];

        foreach ($settingsToUpdate as $key) {
            if ($request->has($key) && $request->input($key) !== null) {
                Settings::updateOrCreate(
                    [
                        'domain' => $domain,
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => $request->input($key),
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice automation settings updated successfully',
        ], 200);
    }
}
