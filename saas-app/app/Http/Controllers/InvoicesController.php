<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\User;
use App\Helpers\ReferenceHelper;
use App\Helpers\SepaReference;
use App\Models\Customer;
use App\Models\InvoicePaymentTerm;
use App\Models\InvoicePaymentTermTranslation;
use Auth;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Mpdf\Mpdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

class InvoicesController extends Controller
{

    protected $user;

    public function __construct()
    {
        //$this->apiToken = uniqid(base64_encode(Str::random(40)));
        $this->middleware('auth:api');
        $this->user = new User;
    }

    private function normalizeI18nLocale($locale, $default = 'FI')
    {
        if (!$locale || !is_string($locale)) {
            return $default;
        }

        $normalized = strtoupper(str_replace('-', '_', trim($locale)));
        $primaryLocale = explode('_', $normalized)[0];
        $supportedLocales = ['EN', 'FI', 'SV'];

        if (!in_array($primaryLocale, $supportedLocales, true)) {
            return $default;
        }

        return $primaryLocale;
    }

    private function requestedI18nLocale(Request $request, $default = 'FI')
    {
        return $this->normalizeI18nLocale(
            $request->query('i18n'),
            $default
        );
    }

    /**
     * Return list of domains invoice should be visible to this user.
     */
    private function allowedInvoiceDomains($user)
    {
        $domains = [];
        if ($user && isset($user->domain)) {
            $domains[] = $user->domain;
        }
   
        return array_unique($domains);
    }

    private function invoiceBelongsToUser($invoice, $user)
    {
        if (!$invoice) return false;
        if ($user && isset($user->role) && $user->role === 'admin') return true;
        $allowed = $this->allowedInvoiceDomains($user);
        return in_array($invoice->domain, $allowed, true);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $user = Auth::user();
        $domain = $user->domain;
        // Otetaan query parametrit: page/per_page (default 15) ja hakusana `q`
        $perPage = $request->query('per_page', 15);
        $search = $request->query('q', null);

        // Perustellaan kysely ja lisätään hakuehto tarvittaessa
        $query = Invoice::where('domain', $domain)
            ->with('customer:id,name,business_id,email');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('reference_code', 'like', '%' . $search . '%')
                  ->orWhere('buyers_reference', 'like', '%' . $search . '%')
                  ->orWhere('sellers_reference', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', '%' . $search . '%')
                         ->orWhere('business_id', 'like', '%' . $search . '%');
                  });
            });
        }

        $invoices = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $invoices->items(),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
                'total'        => $invoices->total()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->domain) {
            return response()->json([
                'success' => false,
                'message' => 'User domain not found'
            ], 400);
        }

        // Validoi syötteet
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'payment_term' => 'nullable|integer|exists:invoice_payment_terms,id',
            'invoice_number' => 'required|string|max:255',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'sellers_reference' => 'nullable|string|max:255',
            'buyers_reference' => 'nullable|string|max:255',
            'complaints_within' => 'nullable|string|max:255',
            'status' => 'required|in:draft,open,paid,partial,overdue,overpaid'
        ]);

        try {
            // Tarkista että laskunumero ei ole jo käytössä tässä domainissa
            $existingInvoice = Invoice::where('domain', $user->domain)
                ->where('invoice_number', $validated['invoice_number'])
                ->first();

            if ($existingInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice number already exists'
                ], 400);
            }

            // Luo uusi lasku
            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'payment_term_id' => $validated['payment_term'] ?? null,
                'invoice_number' => $validated['invoice_number'],
                'reference_code' => ReferenceHelper::generate($validated['invoice_number']),
                'sepa_reference' => SepaReference::generate($validated['invoice_number']),
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'status' => $validated['status'],
                'sellers_reference' => $validated['sellers_reference'] ?? null,
                'buyers_reference' => $validated['buyers_reference'] ?? null,
                'complaints_within' => $validated['complaints_within'] ?? null,
                'domain' => $user->domain
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();
        $domain = $user->domain;
        $invoice = DB::table('invoices')->where('id', $id)->first();

        if (!$this->invoiceBelongsToUser((object)$invoice, $user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice
        ]);
    }

    public function getLines($id)
    {
        $user = Auth::user();
        $domain = $user->domain;

        // Varmista että lasku kuuluu käyttäjän domainiin
        $invoice = Invoice::where('id', $id)->first();
        if (!$this->invoiceBelongsToUser($invoice, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        // Hae laskun rivitiedot
        $lines = DB::table('invoice_rows')
            ->where('invoice_id', $invoice->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $lines
        ]);
    }

    /**
     * Lisää rivin laskulle.
     */

    public function addLine(Request $request, $id)
    {
        $user = Auth::user();
        $domain = $user->domain;

        // Varmista että lasku kuuluu käyttäjän domainiin
        $invoice = Invoice::where('id', $id)->first();
        if (!$this->invoiceBelongsToUser($invoice, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        // Validoi syötteet (kaikki kentät ovat valinnaisia, käytetään oletusarvoja)
        $validated = $request->validate([
            'description' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:10',
            'unit_price_excluding_vat' => 'nullable|numeric|min:0',
            'total_excluding_vat' => 'nullable|numeric|min:0',
            'total_including_vat' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
        ]);

        try {
            // Lisää rivitiedot invoice_rows-tauluun oletusarvoilla
            DB::table('invoice_rows')->insert([
                'invoice_id' => $invoice->id,
                'description' => $validated['description'] ?? '',
                'quantity' => $validated['quantity'] ?? 0,
                'unit' => $validated['unit'] ?? 't',
                'unit_price_excluding_vat' => $validated['unit_price_excluding_vat'] ?? 0,
                'total_excluding_vat' => $validated['total_excluding_vat'] ?? 0,
                'total_including_vat' => $validated['total_including_vat'] ?? 0,
                'vat' => $validated['vat'] ?? 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Line item added successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding line item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateLines(Request $request, $id)
    {
        $user = Auth::user();
        $domain = $user->domain;

        // Varmista että lasku kuuluu käyttäjän domainiin
        $invoice = Invoice::where('id', $id)->first();
        if (!$this->invoiceBelongsToUser($invoice, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        // Validoi syötteet
        $validated = $request->validate([
            'lines' => 'required|array',
            'lines.*.id' => 'required|integer',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.quantity' => 'nullable|numeric|min:0',
            'lines.*.unit' => 'nullable|string|max:10',
            'lines.*.unit_price_excluding_vat' => 'nullable|numeric|min:0',
            'lines.*.unit_price_including_vat' => 'nullable|numeric|min:0',
            'lines.*.total_excluding_vat' => 'nullable|numeric|min:0',
            'lines.*.total_including_vat' => 'nullable|numeric|min:0',
            'lines.*.vat' => 'nullable|numeric|min:0',
        ]);

        try {
            // Päivitä jokainen rivi
            foreach ($validated['lines'] as $lineData) {
                DB::table('invoice_rows')
                    ->where('id', $lineData['id'])
                    ->where('invoice_id', $invoice->id)
                    ->update([
                        'description' => $lineData['description'] ?? '',
                        'quantity' => $lineData['quantity'] ?? 0,
                        'unit' => $lineData['unit'] ?? 't',
                        'unit_price_excluding_vat' => $lineData['unit_price_excluding_vat'] ?? 0,
                        'unit_price_including_vat' => $lineData['unit_price_including_vat'] ?? 0,
                        'total_excluding_vat' => $lineData['total_excluding_vat'] ?? 0,
                        'total_including_vat' => $lineData['total_including_vat'] ?? 0,
                        'vat' => $lineData['vat'] ?? 0,
                        'updated_at' => now()
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice lines updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice lines: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteLine($id, $lineId)
    {
        // Implementation for deleting a line item
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        // Varmista että lasku kuuluu käyttäjän domainiin tai on adminin luoma (jolloin asiakkaat saavat vain lukea)
        $invoice = Invoice::where('id', $id)->first();
        if (!$this->invoiceBelongsToUser($invoice, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        // Only allow updates when the invoice belongs to the user's domain, or user is admin
        if ($invoice->domain !== ($user->domain ?? null) && ($user->role ?? '') !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden'
            ], 403);
        }

        // Validoi syötteet
        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'payment_term_id' => 'nullable|integer|exists:invoice_payment_terms,id',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'sellers_reference' => 'nullable|string|max:255',
            'buyers_reference' => 'nullable|string|max:255',
            'complaints_within' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,open,paid,partial,overdue,overpaid'
        ]);

        try {
            // Päivitä vain ne kentät jotka on annettu
            if (isset($validated['customer_id'])) {
                $invoice->customer_id = $validated['customer_id'];
            }
            if (isset($validated['payment_term_id'])) {
                $invoice->payment_term_id = $validated['payment_term_id'];
            }
            if (isset($validated['invoice_date'])) {
                $invoice->invoice_date = $validated['invoice_date'];
            }
            if (isset($validated['due_date'])) {
                $invoice->due_date = $validated['due_date'];
            }
            if (isset($validated['sellers_reference'])) {
                $invoice->sellers_reference = $validated['sellers_reference'];
            }
            if (isset($validated['buyers_reference'])) {
                $invoice->buyers_reference = $validated['buyers_reference'];
            }
            if (isset($validated['complaints_within'])) {
                $invoice->complaints_within = $validated['complaints_within'];
            }
            if (isset($validated['status'])) {
                $invoice->status = $validated['status'];
            }

            $invoice->save();

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Download invoice as PDF from filled Excel template
     */
    public function downloadInvoice(Request $request, $id)
    {
        $user = Auth::user();

        // Hae lasku ja varmista että se kuuluu käyttäjän domainiin tai on adminin luoma
        $invoice = Invoice::where('id', $id)->first();

        if (!$this->invoiceBelongsToUser($invoice, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        }

        $customer = Customer::where('id', $invoice->customer_id)
            ->where(function($q) use ($invoice, $user) {
                // customer may belong to the invoice domain; allow admin-domain invoices to be visible
                $q->where('domain', $invoice->domain);
            })->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $paymentTerm = InvoicePaymentTerm::where('id', $invoice->payment_term_id)
            ->where('domain', $invoice->domain)
            ->first();

        if (!$paymentTerm) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice payment term not found'
            ], 404);
        }

        $locale = $this->requestedI18nLocale($request, 'FI');
        $fallbackLocales = array_unique([$locale, 'EN', 'FI']);
        $paymentTermTranslation = null;

        foreach ($fallbackLocales as $fallbackLocale) {
            $paymentTermTranslation = InvoicePaymentTermTranslation::where('invoice_payment_term_id', $paymentTerm->id)
                ->where('locale', $fallbackLocale)
                ->first();

            if ($paymentTermTranslation) {
                break;
            }
        }

        if (!$paymentTermTranslation) {
            $paymentTermTranslation = InvoicePaymentTermTranslation::where('invoice_payment_term_id', $paymentTerm->id)
                ->first();
        }

        if (!$paymentTermTranslation) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice payment term translation not found'
            ], 404);
        }

        // Hae domain-tiedot ja Excel-pohjan polku
        // Use invoice's domain for domain-specific settings/template
        $domainData = DB::table('domains')
            ->where('domain', $invoice->domain)
            ->first();

        if (!$domainData || !isset($domainData->invoice_template_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice template not configured for this domain'
            ], 400);
        }

        $templatePath = storage_path('app/' . $domainData->invoice_template_path);

        if (!file_exists($templatePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice template file not found'
            ], 404);
        }

        try {
            // Lataa Excel-pohja
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Hae laskurivit
            $lines = DB::table('invoice_rows')
                ->where('invoice_id', $invoice->id)
                ->get();

            // Dynaaminen rivitys asiakkaan tiedoille
            $customerRow = 6;

            if (!empty($customer->name)) {
                $sheet->setCellValue('A' . $customerRow, $customer->name);
                $sheet->mergeCells('A' . $customerRow . ':D' . $customerRow);
                $customerRow++;
            }

            if (!empty($customer->address_line_1)) {
                $sheet->setCellValue('A' . $customerRow, $customer->address_line_1);
                $sheet->mergeCells('A' . $customerRow . ':D' . $customerRow);
                $customerRow++;
            }

            if (!empty($customer->address_line_2)) {
                $sheet->setCellValue('A' . $customerRow, $customer->address_line_2);
                $sheet->mergeCells('A' . $customerRow . ':D' . $customerRow);
                $customerRow++;
            }

            if (!empty($customer->zip) || !empty($customer->city) || !empty($customer->country)) {
                $addressParts = array_filter([
                    $customer->zip,
                    $customer->city,
                    $customer->country
                ]);
                $sheet->setCellValue('A' . $customerRow, implode(' ', $addressParts));
                $sheet->mergeCells('A' . $customerRow . ':D' . $customerRow);
                $customerRow++;
            }

            if (!empty($customer->business_id)) {
                $sheet->setCellValue('A' . $customerRow, "Y-tunnus: " . $customer->business_id);
                $sheet->mergeCells('A' . $customerRow . ':D' . $customerRow);
                $customerRow++;
            }

            if (!empty($customer->vat_id)) {
                $sheet->setCellValue('A' . $customerRow, "ALV Rek. " . $customer->vat_id);
                $sheet->mergeCells('A' . $customerRow . ':D' . $customerRow);
                $customerRow++;
            }

            $sheet->setCellValue('F2', $invoice->invoice_number);
            $sheet->setCellValue('F3', $customer->id ?? '');
            $sheet->setCellValue('F4', $invoice->buyers_reference ?? '');
            $sheet->setCellValue('F5', $invoice->sellers_reference ?? '');
            $sheet->setCellValue('F6', $invoice->invoice_date);
            $sheet->setCellValue('F7', $invoice->due_date);
            $sheet->setCellValue('F8', $paymentTermTranslation->name ?? '');
            $sheet->setCellValue('F9', $invoice->complaints_within ?? '');
            $sheet->setCellValue('F11', $invoice->reference_code ?? '');

            // Täytä laskurivit (oletetaan että rivit alkavat riviltä 10)
            $startRow = 14;
            // Käytä bcadd:ia kelluvien laskuvirheiden välttämiseksi
            $totalIncludingVat = '0.00';
            foreach ($lines as $index => $line) {
                $row = $startRow + $index;
                $sheet->setCellValue('A' . $row, $line->description);
                $sheet->setCellValue('B' . $row, $line->quantity);
                $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $sheet->setCellValue('C' . $row, $line->unit);
                $sheet->setCellValue('D' . $row, $line->unit_price_excluding_vat);
                $sheet->setCellValue('E' . $row, $line->vat);
                $sheet->setCellValue('F' . $row, $line->unit_price_including_vat);
                $sheet->setCellValue('G' . $row, $line->total_including_vat);
                $totalIncludingVat = bcadd($totalIncludingVat, (string) $line->total_including_vat, 2);
            }

            // Laske yhteissumma viimeisen rivin jälkeen
            $summaryRow = $startRow + count($lines);
            $endRow = max($summaryRow, $startRow);

            // Lisää viiva summarivin yläpuolelle
            $sheet->getStyle('A' . $summaryRow . ':G' . $summaryRow)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $sheet->setCellValue('F' . $summaryRow, 'Yhteensä: ');
            $sheet->setCellValue('G' . $summaryRow, (float) $totalIncludingVat);
            $sheet->getStyle('G' . $summaryRow)->getNumberFormat()->setFormatCode('0.00 "€"');

            // Muotoile eurot kahdella desimaalilla
            $sheet->getStyle('D' . $startRow . ':D' . $endRow)->getNumberFormat()->setFormatCode('0.00 "€"');
            $sheet->getStyle('F' . $startRow . ':F' . $endRow)->getNumberFormat()->setFormatCode('0.00 "€"');
            $sheet->getStyle('G' . $startRow . ':G' . $endRow)->getNumberFormat()->setFormatCode('0.00 "€"');

            // Luo suomalainen pankkiviivakoodi (Code 128) maksua varten
            $ibanRaw = preg_replace('/\s+/', '', (string) ($domainData->iban1 ?? ''));
            // IBAN ilman maatunnusta, 16 numeroa, täytetään nollilla vasemmalta
            $ibanNumeric = preg_replace('/^FI/', '', strtoupper($ibanRaw));
            $ibanNumeric = preg_replace('/\D/', '', $ibanNumeric);
            $ibanComponent = str_pad(substr($ibanNumeric, 0, 16), 16, '0', STR_PAD_LEFT);

            // Viite: käytetään suomalainen viitenumero, täytetään 20 numeroon
            $referenceRaw = preg_replace('/\s+/', '', (string) ($invoice->reference_code ?? ''));
            $referenceNumeric = preg_replace('/\D/', '', $referenceRaw);
            $referenceComponent = str_pad(substr($referenceNumeric, 0, 20), 20, '0', STR_PAD_LEFT);

            // Summa senteissä, 8 merkkiä (6 euroa + 2 senttiä)
            $amountInCents = (int) round(((float) $totalIncludingVat) * 100);
            $amountComponent = str_pad((string) $amountInCents, 8, '0', STR_PAD_LEFT);

            // Eräpäivä YYMMDD, tai 000000 jos puuttuu
            $dueDateComponent = '000000';
            if (!empty($invoice->due_date)) {
                $dueDate = \Carbon\Carbon::parse($invoice->due_date);
                $dueDateComponent = $dueDate->format('ymd');
            }

            // Koosta viivakoodidata: versio 4 + IBAN(16) + viite(20) + summa(8) + eräpäivä(6)
            $barcodeData = '4' . $ibanComponent . $referenceComponent . $amountComponent . $dueDateComponent;

            // Barcode generation commented out
            /*
            try {
                $generator = new BarcodeGeneratorPNG();
                $barcodePng = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128, 2, 60);
                $barcodeImage = imagecreatefromstring($barcodePng);
                if ($barcodeImage !== false) {
                    $barcodeImage = $this->flattenPngToWhite($barcodeImage);
                    // Otsikko viivakoodille
                    $barcodeTitleRow = $summaryRow + 11;
                    $sheet->setCellValue('A' . $barcodeTitleRow, 'Viivakoodi');
                    $sheet->mergeCells('A' . $barcodeTitleRow . ':E' . $barcodeTitleRow);
                    $sheet->getStyle('A' . $barcodeTitleRow)->getFont()->setBold(true);

                    $drawing = new MemoryDrawing();
                    $drawing->setName('Pankkiviivakoodi');
                    $drawing->setDescription('Finnish bank payment barcode');
                    $drawing->setImageResource($barcodeImage);
                    $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
                    $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
                    $drawing->setHeight(60);
                    // Sijoitetaan viivakoodi summan alle
                    $barcodeRow = $summaryRow + 12;
                    $drawing->setCoordinates('A' . $barcodeRow);
                    $sheet->mergeCells('A' . $barcodeRow . ':E' . $barcodeRow);
                    $drawing->setWorksheet($sheet);
                }
            } catch (\Throwable $e) {
                // Jos viivakoodia ei voida luoda, jätetään se pois eikä kaadeta laskun generointia
            }
            */

            // QR-code generation commented out
            /*
            // Luo EPC/SEPA QR-koodi maksamista varten
            try {
                $amountEuros = number_format((float) $totalIncludingVat, 2, '.', '');
                $qrReference = $invoice->sepa_reference ?? $invoice->reference_code ?? '';
                // EPC structured reference must start with RF; otherwise leave empty and use unstructured message
                if (!str_starts_with($qrReference, 'RF')) {
                    $qrReference = '';
                }
                $qrRemittance = 'Invoice ' . ($invoice->invoice_number ?? '');

                $qrPayload = implode("\n", [
                    'BCD',      // Header
                    '001',      // Version
                    '1',        // Character set (UTF-8)
                    'SCT',      // Service tag
                    $domainData->bic1 ?? '',
                    mb_substr($domainData->company_name ?? '', 0, 70),
                    $domainData->iban1 ?? '',
                    'EUR' . $amountEuros,
                    $qrReference,
                    $qrRemittance,
                    ''
                ]);

                $qrResult = Builder::create()
                    ->writer(new PngWriter())
                    ->data($qrPayload)
                    ->encoding(new Encoding('UTF-8'))
                    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                    ->size(240)
                    ->margin(4)
                    ->build();

                $qrImage = imagecreatefromstring($qrResult->getString());
                if ($qrImage !== false) {
                    $qrImage = $this->flattenPngToWhite($qrImage);
                    // Otsikko QR-koodille
                    $qrTitleRow = $summaryRow + 16;
                    $sheet->setCellValue('A' . $qrTitleRow, 'QR-koodi');
                    $sheet->mergeCells('A' . $qrTitleRow . ':E' . $qrTitleRow);
                    $sheet->getStyle('A' . $qrTitleRow)->getFont()->setBold(true);

                    $qrDrawing = new MemoryDrawing();
                    $qrDrawing->setName('SEPA QR');
                    $qrDrawing->setDescription('EPC/SEPA payment QR');
                    $qrDrawing->setImageResource($qrImage);
                    $qrDrawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
                    $qrDrawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
                    $qrDrawing->setHeight(120);
                    $qrRow = $summaryRow + 17;
                    $sheet->mergeCells('A' . $qrRow . ':E' . $qrRow);
                    $qrDrawing->setCoordinates('A' . $qrRow);
                    $qrDrawing->setWorksheet($sheet);
                }
            } catch (\Throwable $e) {
                // Jos QR-koodia ei voida luoda, ohitetaan ilman virheen heittoa
            }
            */

            $sheet->setCellValue('A21', $domainData->company_name);
            $sheet->setCellValue('B21', "Y-tunnus: " . $domainData->business_id);
            $sheet->mergeCells('B21:D21');
            $sheet->setCellValue('B22', "ALV-tunnus: " . $domainData->vat_id);
            $sheet->mergeCells('B22:D22');

            // Dynaaminen rivitys yrityksen tiedoille
            $rowLine = 21;
            $currentRow = 22;
            $sheet->getStyle('A' . $rowLine . ':G' . $rowLine)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            if (!empty($domainData->address_line_1)) {
                $sheet->setCellValue('A' . $currentRow, $domainData->address_line_1);
                $currentRow++;
            }

            if (!empty($domainData->address_line_2)) {
                $sheet->setCellValue('A' . $currentRow, $domainData->address_line_2);
                $currentRow++;
            }

            if (!empty($domainData->zip) || !empty($domainData->city)) {
                $sheet->setCellValue('A' . $currentRow, trim($domainData->zip . ' ' . $domainData->city));
                $currentRow++;
            }

            if (!empty($domainData->country)) {
                $sheet->setCellValue('A' . $currentRow, $domainData->country);
                $currentRow++;
            }

            $sheet->setCellValue('B23', $domainData->billing_contact_email);
            $sheet->mergeCells('B23:D23');
            $sheet->setCellValue('B24', $domainData->mobile_no);
            $sheet->mergeCells('B24:D24');

            $sheet->setCellValue('F21', "Pankkitiedot");
            $sheet->mergeCells('F21:G21');


            $sheet->setCellValue('F22', $domainData->iban1 . ' ' . $domainData->bic1);
            $sheet->mergeCells('F22:G22');

            $sheet->setCellValue('F23', $domainData->iban2 . ' ' . $domainData->bic2);
            $sheet->mergeCells('F23:G23');

            $sheet->setCellValue('F24', $domainData->iban3 . ' ' . $domainData->bic3);
            $sheet->mergeCells('F24:G24');

            // Piilota ruudukko/gridlines
            $sheet->setShowGridlines(false);

            // Tallenna PDF väliaikaiseen tiedostoon suoraan Excelistä
            $tempFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.pdf';

            // Käytä Dompdf-writeria PDF:n luomiseen
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf::class);
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
            $writer->save($tempFile);

            // Palauta tiedosto latauslinkkina
            return response()->download(
                $tempFile,
                'invoice_' . $invoice->invoice_number . '.pdf',
                [
                    'Content-Type' => 'application/pdf',
                ]
            )->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the next available invoice number from domain settings
     */
    public function nextInvoiceNumber(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->domain) {
            return response()->json([
                'error' => 'User domain not found'
            ], 400);
        }

        // Hae domain-taulusta invoice_start_number
        $domain = DB::table('domains')
            ->where('domain', $user->domain)
            ->first();

        if (!$domain || !isset($domain->invoice_start_number)) {
            return response()->json([
                'error' => 'Invoice start number not configured'
            ], 400);
        }

        $startNumber = (int) $domain->invoice_start_number;

        // Hae viimeisin laskunumero invoices-taulusta
        $lastInvoice = Invoice::where('domain', $user->domain)
            ->orderBy('invoice_number', 'desc')
            ->first();

        // Jos ei ole aikaisempia laskuja, käytä start_number
        if (!$lastInvoice) {
            $nextNumber = $startNumber;
        } else {
            // Muuten ota viimeinen numero ja lisää 1
            $lastNumber = (int) $lastInvoice->invoice_number;
            $nextNumber = max($lastNumber + 1, $startNumber);
        }

        return response()->json([
            'next_invoice_number' => (string) $nextNumber
        ]);
    }

    /**
     * Flatten a PNG with transparency onto a white background to avoid black boxes in PDF rendering.
     */
    private function flattenPngToWhite($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
        return $canvas;
    }

}
