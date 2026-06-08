<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\InvoicesController;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PurchaseOrdersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $perPage = (int) $request->query('per_page', 15);
        $search = $request->query('q', null);

        // Base purchase orders: customers only see their own orders, admin sees all
        $poQuery = PurchaseOrder::query();
        if (empty($user->role) || $user->role !== 'admin') {
            $poQuery->where('domain', $user->domain);
        }

        if ($search) {
            $poQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%');
            });
        }
        $pos = $poQuery->orderBy('created_at', 'desc')->get();

        // Include admin-domain generated invoices in the purchase orders list
        $invoiceItems = collect();
        $adminDomains = $this->resolveAdminInvoiceDomains();
        if (!empty($adminDomains)) {
            $invoicesQuery = Invoice::whereIn('domain', $adminDomains)
                ->withSum('rows', 'total_including_vat');

            if (!empty($user->role) && $user->role === 'admin') {
                // Admin sees all provider invoices
            } else {
                $customerDomains = $this->resolveCustomerDomains($user->domain);
                $invoicesQuery->whereHas('customer', function ($q) use ($customerDomains) {
                    $q->whereIn('domain', $customerDomains);
                });
            }

            if ($search) {
                $invoicesQuery->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', '%' . $search . '%')
                    ->orWhere('reference_code', 'like', '%' . $search . '%');
                });
            }

            $invoices = $invoicesQuery->orderBy('created_at', 'desc')->get();

            // Map invoices into unified shape for the purchase orders list
            $invoiceItems = $invoices->map(function ($inv) {
                $totalAmount = (float) ($inv->rows_sum_total_including_vat ?? $inv->total_including_vat ?? 0);

                return (object) [
                    'id' => 'inv-' . $inv->id,
                    'source' => 'invoice',
                    'order_number' => $inv->invoice_number,
                    'order_date' => $inv->invoice_date,
                    'total_amount' => $totalAmount,
                    'status' => $inv->status,
                    'created_at' => $inv->created_at,
                    'original_id' => $inv->id,
                ];
            });
        }

        // Map purchase orders into same shape
        $poItems = $pos->map(function ($po) {
            return (object) [
                'id' => 'po-' . $po->id,
                'source' => 'purchase_order',
                'order_number' => $po->order_number,
                'order_date' => $po->order_date,
                'total_amount' => $po->total_amount,
                'status' => $po->status,
                'created_at' => $po->created_at,
                'original_id' => $po->id,
            ];
        });

        // Merge and sort by created_at desc
        $merged = $poItems->concat($invoiceItems)->sortByDesc(function ($i) {
            return $i->created_at;
        })->values();

        // Manual pagination
        $page = (int) max(1, $request->query('page', 1));
        $total = $merged->count();
        $items = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->domain) {
            return response()->json(['success' => false, 'message' => 'User domain not found'], 400);
        }

        $validated = $request->validate([
            'order_number' => 'required|string|max:255',
            'vendor_id' => 'nullable|integer',
            'order_date' => 'nullable|date',
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|string'
        ]);

        // Ensure unique order_number per domain
        $exists = PurchaseOrder::where('domain', $user->domain)
            ->where('order_number', $validated['order_number'])
            ->first();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Order number already exists'], 400);
        }

        $po = PurchaseOrder::create([
            // keep origin domain for auditing, but POs are visible to all domains
            'domain' => $user->domain,
            'order_number' => $validated['order_number'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'order_date' => $validated['order_date'] ?? null,
            'total_amount' => $validated['total_amount'] ?? 0,
            'status' => $validated['status'] ?? 'draft'
        ]);

        return response()->json(['success' => true, 'data' => $po], 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        // Allow viewing any purchase order (shared across domains)
        $po = PurchaseOrder::where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $po]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $domain = $user->domain;

        $po = PurchaseOrder::where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        // Only allow update if owner (same domain) or admin role
        if ($po->domain !== $user->domain && ($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'vendor_id' => 'nullable|integer',
            'order_date' => 'nullable|date',
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|string'
        ]);

        $po->vendor_id = $validated['vendor_id'] ?? $po->vendor_id;
        $po->order_date = $validated['order_date'] ?? $po->order_date;
        $po->total_amount = $validated['total_amount'] ?? $po->total_amount;
        $po->status = $validated['status'] ?? $po->status;
        $po->save();

        return response()->json(['success' => true, 'data' => $po]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $domain = $user->domain;

        $po = PurchaseOrder::where('id', $id)->first();
        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        if ($po->domain !== $user->domain && ($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $po->delete();
        return response()->json(['success' => true]);
    }

    public function download(Request $request, $id)
    {
        return app(InvoicesController::class)->downloadInvoice($request, $id);
    }

    private function getRootDomain(string $domain): string
    {
        $parts = explode('.', strtolower(trim($domain)));
        if (count($parts) > 2 && $parts[0] === 'www') {
            array_shift($parts);
        }

        if (count($parts) > 2) {
            return implode('.', array_slice($parts, -2));
        }

        return implode('.', $parts);
    }

    private function resolveCustomerDomains(string $userDomain): array
    {
        $domain = strtolower(trim($userDomain));
        $candidates = array_filter([
            $domain,
            $this->getRootDomain($domain),
            'www.' . $this->getRootDomain($domain),
        ]);

        return collect($candidates)
            ->map(fn ($domain) => strtolower(trim($domain)))
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    private function resolveAdminInvoiceDomains(): array
    {
        $adminDomain = strtolower(trim((string) env('APP_DOMAIN_ADMIN', '')));
        $candidates = [];

        if (!empty($adminDomain)) {
            $candidates[] = $adminDomain;
            if (str_starts_with($adminDomain, 'www.')) {
                $candidates[] = substr($adminDomain, 4);
            } else {
                $candidates[] = 'www.' . $adminDomain;
            }
        }

        return collect($candidates)
            ->map(fn ($domain) => strtolower(trim($domain)))
            ->unique()
            ->filter()
            ->values()
            ->all();
    }
}
