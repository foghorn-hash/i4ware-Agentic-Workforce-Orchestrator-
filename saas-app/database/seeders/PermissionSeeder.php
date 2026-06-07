<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Permission;
use App\Models\User;
use App\Models\Settings;
use App\Models\Domain;
use App\Models\Role;
use App\Models\InvoicePaymentTerm;
use App\Models\RolePermissions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // default permissions 
        $permissions = [
            "domain.view",
            "domain.edit",
            "domain.add",
            "domain.actions",
            "users.view",
            "users.statusChange",
            "users.changePassword",
            "users.changeRole",
            "users.addUser",
            "users.verifyUser",    
            "roles.view",
            "roles.edit",
            "roles.add",
            "roles.actions",
            "invoices.view",
            "invoices.actions",
            "invoices.add",
            "invoices.edit",
            "invoices.uploadLogo",
            "invoice.uploadTemplates",
            "purchaseorders.view",
            "purchaseorders.actions",
            "purchaseorders.add",
            "purchaseorders.edit",
            "purchaseorders.delete",
            "customers.view",
            "customers.add",
            "customers.edit",
            "customers.delete",
            "customers.actions",
            "settings.manage",
        ];

        if(env('APP_IS_HOUSE_USE') === false){

            $role = Role::updateOrCreate([
                "name" => "super-admin",
                "isActive" => true
            ]);
    
            for ($i=0; $i < count($permissions); $i++) {
                if ($permissions[$i] == "domain.view") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "domain.edit") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "users.view") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "users.statusChange") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "users.changePassword") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "users.changeRole") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "users.addUser") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "roles.view") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "roles.edit") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "roles.add") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "roles.actions") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "invoices.view") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "invoices.actions") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "invoices.add") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "invoices.edit") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "invoices.uploadLogo") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "invoice.uploadTemplates") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "customers.view") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "customers.add") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "customers.edit") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
               } else if ($permissions[$i] == "customers.delete") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "customers.actions") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else if ($permissions[$i] == "settings.manage") {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                        "domain" => NULL,
                    ]);
                } else {
                    $permission = Permission::updateOrCreate([
                        "permission_name" => $permissions[$i],
                        "desc" => $permissions[$i] . " desc",
                    ]);
                }
    
                RolePermissions::updateOrInsert([
                    "role_id" => $role->id,
                    "permission_id" => $permission->id,
                ],[
                    "role_id" => $role->id,
                    "permission_id" => $permission->id,
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);
            }

            $role = Role::updateOrCreate([
                "name" => "admin",
                "isActive" => true
            ]);

            for ($i=0; $i < count($permissions); $i++) {
                
                $permission = Permission::updateOrCreate([
                    "permission_name" => $permissions[$i],
                    "desc" => $permissions[$i] . " desc"
                ]);
                
                if ($permissions[$i] == "domain.view") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "domain.edit") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "users.view") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "users.statusChange") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "users.changePassword") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "users.changeRole") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "users.addUser") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "roles.view") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "roles.edit") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "roles.add") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "roles.actions") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "invoices.view") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "invoices.actions") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "invoices.add") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "invoices.edit") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "invoices.uploadLogo") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "invoice.uploadTemplates") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "customers.view") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "customers.add") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "customers.edit") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "customers.delete") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "customers.actions") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "purchaseorders.view") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "purchaseorders.actions") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "purchaseorders.add") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "purchaseorders.edit") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                } else if ($permissions[$i] == "purchaseorders.delete") {
                    RolePermissions::updateOrInsert([
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                    ],[
                        "role_id" => $role->id,
                        "permission_id" => $permission->id,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]);
                }
            }

            $role = Role::updateOrCreate([
                "name" => "user",
                "isActive" => true
            ]);

            $role = Role::updateOrCreate([
                "name" => "book-keeper",
                "isActive" => true
            ]);

        } else {

            $role = Role::updateOrCreate([
                "name" => "super-admin",
                "isActive" => true
            ]);
    
            for ($i=0; $i < count($permissions); $i++) {
                $permission = Permission::updateOrCreate([
                    "permission_name" => $permissions[$i],
                    "desc" => $permissions[$i] . " desc"
                ]);
    
                RolePermissions::updateOrInsert([
                    "role_id" => $role->id,
                    "permission_id" => $permission->id,
                ],[
                    "role_id" => $role->id,
                    "permission_id" => $permission->id,
                ]);
            }

            $role = Role::updateOrCreate([
                "name" => "user",
                "isActive" => true
            ]);

            $role = Role::updateOrCreate([
                "name" => "book-keeper",
                "isActive" => true
            ]);

        }

        DB::beginTransaction();

        try {

            Domain::updateOrCreate([
                'domain' => env('APP_DOMAIN_ADMIN'),
                'valid_before_at' => now()->addDays(30),
                'vat_id' => '-',
                'technical_contact_email' => env('APP_DOMAIN_ADMIN_EMAIL'),
                "billing_contact_email" => env('APP_DOMAIN_ADMIN_EMAIL'),
                'mobile_no' => '-',
                'company_name' => env('APP_DOMAIN_ADMIN_COMPANY'),
                'address_line_1' => '-',
                'address_line_2' => '-',
                'zip' => '-',
                'city' => '-',
                'country' => '-',
                'is_admin' => 1,
                'type' => 'trial',
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'invoice_template_path' => null,
                'invoice_start_number' => 1,
            ]);

            User::updateOrCreate([
                'name' => env('APP_DOMAIN_ADMIN_FULLNAME'),
                'email' => env('APP_DOMAIN_ADMIN_EMAIL'),
                'email_verified_at' => now(),
                'domain' => env('APP_DOMAIN_ADMIN'),
                "role" => "admin",
                "role_id" => 1,
                'password' => Hash::make(env('APP_DOMAIN_ADMIN_PASSWORD')), // password
                'remember_token' => Str::random(10),
            ]);

            // Luo yleisimmät maksuehdot käännöksineen
				$paymentTerms = [
					[
						"days_to_pay" => 0,
						"translations" => [
							["locale" => "EN", "name" => "Due on receipt"],
							["locale" => "FI", "name" => "HETI"],
							["locale" => "SV", "name" => "Direkt"],
						]
					],
					[
						"days_to_pay" => 7,
						"translations" => [
							["locale" => "EN", "name" => "Net 7"],
							["locale" => "FI", "name" => "7 pv netto"],
							["locale" => "SV", "name" => "Netto 7"],
						]
					],
					[
						"days_to_pay" => 14,
						"translations" => [
							["locale" => "EN", "name" => "Net 14"],
							["locale" => "FI", "name" => "14 pv netto"],
							["locale" => "SV", "name" => "Netto 14"],
						]
					],
					[
						"days_to_pay" => 15,
						"translations" => [
							["locale" => "EN", "name" => "Net 15"],
							["locale" => "FI", "name" => "15 pv netto"],
							["locale" => "SV", "name" => "Netto 15"],
						]
					],
					[
						"days_to_pay" => 30,
						"translations" => [
							["locale" => "EN", "name" => "Net 30"],
							["locale" => "FI", "name" => "30 pv netto"],
							["locale" => "SV", "name" => "Netto 30"],
						]
					],
					[
						"days_to_pay" => 60,
						"translations" => [
							["locale" => "EN", "name" => "Net 60"],
							["locale" => "FI", "name" => "60 pv netto"],
							["locale" => "SV", "name" => "Netto 60"],
						]
					],
					[
						"days_to_pay" => 90,
						"translations" => [
							["locale" => "EN", "name" => "Net 90"],
							["locale" => "FI", "name" => "90 pv netto"],
							["locale" => "SV", "name" => "Netto 90"],
						]
					],
				];

				foreach ($paymentTerms as $term) {
					$paymentTerm = InvoicePaymentTerm::create([
						"days_to_pay" => $term["days_to_pay"],
						"domain" => env('APP_DOMAIN_ADMIN'),
					]);
					
					$paymentTerm->translations()->createMany($term["translations"]);
				}

            if(env('APP_IS_HOUSE_USE') === true){
                Settings::updateOrCreate([
                    'setting_key' => "show_captcha",
                    'setting_value' => 1,
                    'system_var' => 1,
                ]);
                
                Settings::updateOrCreate([
                    'setting_key' => "disable_registeration_from_others",
                    'setting_value' => 1,
                    'system_var' => 1,
                ]);

                Settings::updateOrCreate([
                    'setting_key' => "saas_price_per_month_per_user",
                    'setting_value' => 0,
                    'system_var' => 1,
                ]);

            } else {
                Settings::updateOrCreate([
                    'setting_key' => "show_captcha",
                    'setting_value' => 1,
                    'system_var' => 1,
                ]);
                
                Settings::updateOrCreate([
                    'setting_key' => "disable_registeration_from_others",
                    'setting_value' => 0,
                    'system_var' => 1,
                ]);

                Settings::updateOrCreate([
                    'setting_key' => "saas_price_per_month_per_user",
                    'setting_value' => 5,
                    'system_var' => 1,
                ]);

            }

            DB::commit();
    
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return $e->getMessage();
        }

    }
}
