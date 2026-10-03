<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Full first-setup seed for the loan module.
 *
 * Order matters:
 *  1. system properties (module installed flag)
 *  2. permissions, then roles (roles are synced against registered permissions)
 *  3. business settings + CMS content (writes storage/app settings file)
 *  4. reference data (locations, currencies, payment methods)
 *  5. customer groups and catalog products (depend on the tables above)
 *  6. admin user (assigned the Admin role created in step 2)
 *  7. demo portal accounts
 *
 * Every seeder below is idempotent, so this is safe to re-run on an existing
 * install without duplicating rows or overwriting operator edits.
 */
class LoanManagementDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FirstSetupSystemPropertiesSeeder::class);
        $this->call(LoanManagementPermissionSeeder::class);
        $this->call(LoanManagementRoleSeeder::class);
        $this->call(CmsHomeSeeder::class);
        $this->call(LoanManagementSystemDataSeeder::class);
        $this->call(LoanManagementReferenceSeeder::class);
        $this->call(LoanManagementCustomerGroupSeeder::class);
        $this->call(LoanManagementProductSeeder::class);
        $this->call(LoanAdminUserSeeder::class);
        $this->call(PortalDemoAccountSeeder::class);
    }
}