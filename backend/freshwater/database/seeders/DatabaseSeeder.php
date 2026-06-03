<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🔥 Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [
            // Settings
            'manage settings',
            // Orders
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',

            // Order Items
            'view order items',
            'create order items',
            'edit order items',
            'delete order items',

            // Shipments
            'view shipments',
            'create shipments',
            'edit shipments',

            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',

            // Related Products
            'view related products',
            'attach related products',
            'detach related products',

            // Product images
            'view product images',
            'create product images',
            'delete product images',

            // Categories
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /*
        |--------------------------------------------------------------------------
        | Assign permissions to roles
        |--------------------------------------------------------------------------
        */

        // Superadmin → everything
        $superadminRole->givePermissionTo(Permission::all());

        // Admin → operational permissions only
        $adminRole->givePermissionTo([
            'view orders',
            'edit orders',
            'delete orders',
            'view order items',
            'delete order items',
            'view shipments',
            'create shipments',
            'view products',
            'view related products',
            'view product images',
            'view categories',
            'view users',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        if (app()->environment(['local', 'testing']) || (bool) env('SEED_DEMO_USERS', false)) {
            $seedPassword = (string) env('SEED_DEMO_USERS_PASSWORD', 'ChangeMe123!');

            $superAdmin = User::firstOrCreate(
                ['email' => 'demir@abv.bg'],
                [
                    'name' => 'Demir Demirev',
                    'phone' => '0888123456',
                    'password' => Hash::make($seedPassword),
                ]
            );
            $superAdmin->syncRoles(['superadmin']);

            $admin = User::firstOrCreate(
                ['email' => 'miglen@abv.bg'],
                [
                    'name' => 'Miglen Demirev',
                    'phone' => '0888123456',
                    'password' => Hash::make($seedPassword),
                ]
            );
            $admin->syncRoles(['admin']);

            $customer = User::firstOrCreate(
                ['email' => 'customer@example.com'],
                [
                    'name' => 'John Doe',
                    'phone' => '0888123456',
                    'password' => Hash::make($seedPassword),
                ]
            );
            $customer->syncRoles(['customer']);
        }
        /*
        |--------------------------------------------------------------------------
        | Other seeders
        |--------------------------------------------------------------------------
        */
        $this->call([
            CategorySeeder::class,
        ]);

        Product::create([
            'name' => 'Система за обратна осмоза INFINITY',
            'price' => 13.59,
        ]);
        Setting::create([
            'delivery_price' => 7.67,
            'delivery_enabled' => true,
            'free_delivery_over' => 100,
        ]);

    }
}
