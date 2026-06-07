<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\User;
use Auth;

class CustomersController extends Controller
{
    protected $user;

    public function __construct()
    {
        //$this->apiToken = uniqid(base64_encode(Str::random(40)));
        $this->middleware('auth:api');
        $this->user = new User;
    }
    /**
     * Listaa kaikki asiakkaat.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $domain = $user->domain;
        // Otetaan query parametri page ja per_page (default 15)
        $perPage = $request->query('per_page', 15);
        $searchTerm = $request->query('search');

        $query = Customer::where('domain', $domain)->whereNull('deleted_at');

        // Add search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('business_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('contact_person_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'LIKE', "%{$searchTerm}%");
            });
        }

        $customers = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total()
            ]
        ]);
    }

    /**
     * Lisää uusi asiakas.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $domain = $user->domain;
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'contact_person_name'    => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone_number'    => 'nullable|string|max:30',
            'business_id'     => 'nullable|string|max:50',
            'vat_id'          => 'nullable|string|max:50',
            'address_line_1'  => 'nullable|string|max:255',
            'address_line_2'  => 'nullable|string|max:255',
            'zip'             => 'nullable|string|max:20',
            'city'            => 'nullable|string|max:100',
        ]);

        $id = DB::table('customers')->insertGetId([
            'name'            => $validated['name'],
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'email'           => $validated['email'],
            'phone_number'    => $validated['phone_number'] ?? null,
            'business_id'     => $validated['business_id'] ?? null,
            'vat_id'          => $validated['vat_id'] ?? null,
            'address_line_1'  => $validated['address_line_1'] ?? null,
            'address_line_2'  => $validated['address_line_2'] ?? null,
            'zip'             => $validated['zip'] ?? null,
            'city'            => $validated['city'] ?? null,
            'domain'          => $domain,
            'created_at'      => now(),
            'updated_at'      => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer created.',
            'id' => $id
        ]);
    }

    /**
     * Hae asiakkaita Y-tunnuksella tai yrityksen nimellä
     */
    public function search(Request $request)
    {
        $searchTerm = $request->query('q');
        
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return response()->json([
                'data' => [],
                'message' => 'Search term must be at least 2 characters'
            ]);
        }
        
        $customers = Customer::where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('business_id', 'LIKE', "%{$searchTerm}%")
            ->orWhere('email', 'LIKE', "%{$searchTerm}%")
            ->select('id', 'name', 'business_id', 'email')
            ->limit(50)
            ->get();
        
        return response()->json([
            'data' => $customers,
            'count' => $customers->count()
        ]);
    }

    /**
     * Näytä yksittäinen asiakas.
     */
    public function show($id)
    {
        $user = Auth::user();
        $domain = $user->domain;
        $customer = DB::table('customers')->where('id', $id)->where('domain', $domain)->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Päivitä asiakas.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $domain = $user->domain;
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'contact_person_name'    => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone_number'    => 'nullable|string|max:30',
            'business_id'     => 'nullable|string|max:50',
            'vat_id'          => 'nullable|string|max:50',
            'address_line_1'  => 'nullable|string|max:255',
            'address_line_2'  => 'nullable|string|max:255',
            'zip'             => 'nullable|string|max:20',
            'city'            => 'nullable|string|max:100',
        ]);

        DB::table('customers')->where('id', $id)->where('domain', $domain)->update(array_merge(
            $validated,
            ['updated_at' => now()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Customer updated.'
        ]);
    }

    /**
     * Poista asiakas.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $domain = $user->domain;
        $customer = Customer::where('domain', $domain)
                    ->where('id', $id)
                    ->firstOrFail();
        $customer->deleted_at = now();
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted.'
        ]);
    }
}
