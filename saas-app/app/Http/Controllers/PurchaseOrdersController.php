<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Base purchase orders (global visibility)
        $poQuery = PurchaseOrder::query();
        if ($search) {
            $poQuery->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                ->orWhere('status', 'like', '%' . $search . '%');
            });
        }
        $pos = $poQuery->orderBy('created_at', 'desc')->get();

        // Include admin-domain generated invoices that belong to customers of this user's domain
        $adminDomain = env('APP_DOMAIN_ADMIN');
        $invoiceItems = collect();
        if ($adminDomain) {
            $invoicesQuery = Invoice::where('domain', $adminDomain)
                ->whereHas('customer', function ($q) use ($user) {
                    $q->where('domain', $user->domain);
                });

            if ($search) {
                $invoicesQuery->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', '%' . $search . '%')
                    ->orWhere('reference_code', 'like', '%' . $search . '%');
                });
            }

            $invoices = $invoicesQuery->orderBy('created_at', 'desc')->get();

            // Map invoices into unified shape for the purchase orders list
            $invoiceItems = $invoices->map(function ($inv) {
                return (object) [
                    'id' => 'inv-' . $inv->id,
                    'source' => 'invoice',
                    'order_number' => $inv->invoice_number,
                    'order_date' => $inv->invoice_date,
                    'total_amount' => $inv->total_including_vat ?? 0,
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
}
