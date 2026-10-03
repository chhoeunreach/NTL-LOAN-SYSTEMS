<?php

namespace Database\Seeders;

/**
 * Alias of AdminUserSeeder kept so existing installs that call this class
 * directly (`db:seed --class=...LoanAdminUserSeeder`) keep working.
 *
 * The admin bootstrap now lives in AdminUserSeeder, which also sources its
 * permission list from LoanManagementPermissionSeeder so every permission the
 * UI checks for is registered before the role is synced.
 */
class LoanAdminUserSeeder extends AdminUserSeeder
{
    protected string $successMessage = 'Loan admin user seeded successfully!';
}