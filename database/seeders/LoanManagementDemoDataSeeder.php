<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class LoanManagementDemoDataSeeder extends Seeder
{
    private string $connection = 'mysql_loan';

    public function run(): void
    {
        $adminId = $this->ensureUsers();
        $locationIds = $this->seedLocations();
        $customerIds = $this->seedCustomers($locationIds, $adminId);
        $this->seedProducts($locationIds);
        $loanIds = $this->seedLoans($customerIds, $locationIds, $adminId);
        $this->seedCustomerRelations($loanIds, $customerIds, $adminId);
        $this->seedChats($loanIds, $customerIds, $adminId);
        $this->seedTracking($loanIds, $customerIds);
        $this->seedImportExportHistory($loanIds, $adminId);
        $this->seedActivity($loanIds, $adminId);
    }

    private function ensureUsers(): int
    {
        if (! Schema::hasTable('users')) {
            return 1;
        }

        $role = (class_exists(Role::class) && Schema::hasTable('roles'))
            ? Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            : null;

        $users = [
            ['email' => 'admin@example.com', 'username' => 'admin', 'first_name' => 'Loan', 'last_name' => 'Admin', 'name' => 'Loan Admin'],
            ['email' => 'collector@example.com', 'username' => 'collector', 'first_name' => 'Dara', 'last_name' => 'Collector', 'name' => 'Dara Collector'],
            ['email' => 'manager@example.com', 'username' => 'manager', 'first_name' => 'Sophea', 'last_name' => 'Manager', 'name' => 'Sophea Manager'],
        ];

        $adminId = 1;
        foreach ($users as $userData) {
            $user = User::query()->firstOrCreate(
                ['email' => $userData['email']],
                $userData + [
                    'password' => Hash::make('password'),
                    'business_id' => 1,
                    'allow_login' => true,
                    'status' => 'active',
                ]
            );

            if ($role && method_exists($user, 'assignRole')) {
                $user->assignRole($role);
            }

            if ($userData['username'] === 'admin') {
                $adminId = (int) $user->id;
            }
        }

        return $adminId;
    }

    private function seedLocations(): array
    {
        if (! $this->loanTableExists('loan_business_locations')) {
            return [1];
        }

        $rows = [
            ['location_code' => 'MAIN', 'name' => 'Main Branch', 'address' => 'Phnom Penh', 'phone' => '023 900 100'],
            ['location_code' => 'TK', 'name' => 'Toul Kork Branch', 'address' => 'Toul Kork, Phnom Penh', 'phone' => '023 900 200'],
            ['location_code' => 'SR', 'name' => 'Siem Reap Branch', 'address' => 'Krong Siem Reap', 'phone' => '063 900 300'],
        ];

        $ids = [];
        foreach ($rows as $row) {
            $payload = $this->loanColumns('loan_business_locations', $row + [
                'loan_invoice_prefix' => 'KY-',
                'status' => 'active',
                'telegram_notify_payment' => false,
                'telegram_notify_installment' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::connection($this->connection)->table('loan_business_locations')->updateOrInsert(
                ['location_code' => $row['location_code']],
                $payload
            );

            $ids[] = (int) DB::connection($this->connection)->table('loan_business_locations')
                ->where('location_code', $row['location_code'])
                ->value('id');
        }

        return array_values(array_filter($ids));
    }

    private function seedCustomers(array $locationIds, int $adminId): array
    {
        if (! $this->loanTableExists('loan_customers')) {
            return [];
        }

        $customers = [
            ['customer_code' => 'CUST-0001', 'name' => 'Sok Dara', 'khmer_name' => 'សុខ ដារ៉ា', 'phone' => '010111001', 'occupation' => 'Store Owner', 'workplace' => 'Central Market', 'province' => 'Phnom Penh', 'district' => 'Toul Kork', 'commune' => 'Boeng Kak 2', 'village' => 'Village 3'],
            ['customer_code' => 'CUST-0002', 'name' => 'Chan Sophea', 'khmer_name' => 'ចាន់ សុភា', 'phone' => '010111002', 'occupation' => 'Accountant', 'workplace' => 'Vattanac Tower', 'province' => 'Phnom Penh', 'district' => 'Mean Chey', 'commune' => 'Stung Meanchey', 'village' => 'Village 7'],
            ['customer_code' => 'CUST-0003', 'name' => 'Mao Vicheka', 'khmer_name' => 'ម៉ៅ វិច្ឆិកា', 'phone' => '010111003', 'occupation' => 'Pharmacist', 'workplace' => 'Takhmao Pharmacy', 'province' => 'Kandal', 'district' => 'Takhmao', 'commune' => 'Doeum Mien', 'village' => 'Village 1'],
            ['customer_code' => 'CUST-0004', 'name' => 'Kim Sreypov', 'khmer_name' => 'គឹម ស្រីពៅ', 'phone' => '010111004', 'occupation' => 'Hotel Supervisor', 'workplace' => 'Angkor Paradise Resort', 'province' => 'Siem Reap', 'district' => 'Krong Siem Reap', 'commune' => 'Svay Dangkum', 'village' => 'Village 4'],
            ['customer_code' => 'CUST-0005', 'name' => 'Ly Ratha', 'khmer_name' => 'លី រដ្ឋា', 'phone' => '010111005', 'occupation' => 'Civil Engineer', 'workplace' => 'Battambang Construction Co.', 'province' => 'Battambang', 'district' => 'Battambang', 'commune' => 'Svay Por', 'village' => 'Village 2'],
            ['customer_code' => 'CUST-0006', 'name' => 'Touch Sopheak', 'khmer_name' => 'ទូច សុភ័ក្ត្រ', 'phone' => '010111006', 'occupation' => 'Delivery Driver', 'workplace' => 'Express Logistics', 'province' => 'Phnom Penh', 'district' => 'Sen Sok', 'commune' => 'Teuk Thla', 'village' => 'Village 9'],
            ['customer_code' => 'CUST-0007', 'name' => 'Heng Pisey', 'khmer_name' => 'ហេង ពិសី', 'phone' => '010111007', 'occupation' => 'High School Teacher', 'workplace' => 'Hun Sen High School', 'province' => 'Kampong Cham', 'district' => 'Kampong Cham', 'commune' => 'Veal Vong', 'village' => 'Village 6'],
            ['customer_code' => 'CUST-0008', 'name' => 'Pov Kimsan', 'khmer_name' => 'ពៅ គីមសាន', 'phone' => '010111008', 'occupation' => 'Restaurant Manager', 'workplace' => 'Riverfront Dining', 'province' => 'Phnom Penh', 'district' => 'Chbar Ampov', 'commune' => 'Nirouth', 'village' => 'Village 5'],
        ];

        $ids = [];
        foreach ($customers as $index => $customer) {
            $locationId = $locationIds[$index % max(1, count($locationIds))] ?? 1;
            $payload = $this->loanColumns('loan_customers', $customer + [
                'business_location_id' => $locationId,
                'business_location_name_snapshot' => $this->locationName($locationId),
                'username' => strtolower(str_replace(' ', '.', $customer['name'])),
                'login_phone' => $customer['phone'],
                'password' => Hash::make('password'),
                'email' => strtolower(str_replace(' ', '.', $customer['name'])).'@example.com',
                'gender' => $index % 2 === 0 ? 'male' : 'female',
                'date_of_birth' => now()->subYears(25 + $index)->toDateString(),
                'id_card_number' => 'ID'.str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT),
                'address' => $customer['village'].', '.$customer['commune'].', '.$customer['district'],
                'monthly_income' => 350 + ($index * 45),
                'customer_type' => 'retail',
                'can_login' => true,
                'allow_gps_tracking' => true,
                'created_by' => $adminId,
                'created_by_name_snapshot' => 'Loan Admin',
                'status' => 'active',
                'latitude' => 11.5564 + ($index * 0.01),
                'longitude' => 104.9282 + ($index * 0.01),
                'created_at' => now()->subDays(45 - $index),
                'updated_at' => now()->subDays($index),
            ]);

            DB::connection($this->connection)->table('loan_customers')->updateOrInsert(
                ['customer_code' => $customer['customer_code']],
                $payload
            );

            $ids[] = (int) DB::connection($this->connection)->table('loan_customers')
                ->where('customer_code', $customer['customer_code'])
                ->value('id');
        }

        return array_values(array_filter($ids));
    }

    private function seedProducts(array $locationIds): void
    {
        if (! $this->loanTableExists('loan_products')) {
            return;
        }

        $products = [
            ['sku' => 'PHONE-A15', 'name' => 'Samsung Galaxy A15', 'selling_price' => 245],
            ['sku' => 'PHONE-R13', 'name' => 'Redmi Note 13', 'selling_price' => 219],
            ['sku' => 'BIKE-HONDA', 'name' => 'Honda Dream 125', 'selling_price' => 1850],
            ['sku' => 'LAP-ASUS', 'name' => 'ASUS Vivobook 15', 'selling_price' => 520],
        ];

        foreach ($products as $index => $product) {
            DB::connection($this->connection)->table('loan_products')->updateOrInsert(
                ['sku' => $product['sku']],
                $this->loanColumns('loan_products', $product + [
                    'loan_business_location_id' => $locationIds[$index % max(1, count($locationIds))] ?? 1,
                    'cost_price' => round($product['selling_price'] * 0.82, 2),
                    'qty_available' => 12 + $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedLoans(array $customerIds, array $locationIds, int $adminId): array
    {
        if (! $this->loanTableExists('loans') || empty($customerIds)) {
            return [];
        }

        $loanIds = [];
        foreach ($customerIds as $index => $customerId) {
            $customer = DB::connection($this->connection)->table('loan_customers')->where('id', $customerId)->first();
            $locationId = $locationIds[$index % max(1, count($locationIds))] ?? ($customer->business_location_id ?? 1);
            $principal = [320, 480, 760, 1250, 2100, 540, 890, 1500][$index] ?? 500;
            $interest = round($principal * 0.12, 2);
            $total = $principal + $interest;
            $paid = round($total * ([0.2, 0.45, 0.7, 0.1, 0.85, 0.3, 0, 0.55][$index] ?? 0.25), 2);
            $status = ['active', 'active', 'late', 'pending', 'completed', 'active', 'approved', 'late'][$index] ?? 'active';
            $loanNumber = 'LN-DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            DB::connection($this->connection)->table('loans')->updateOrInsert(
                ['loan_number' => $loanNumber],
                $this->loanColumns('loans', [
                    'customer_id' => $customerId,
                    'business_location_id' => $locationId,
                    'business_location_name_snapshot' => $this->locationName($locationId),
                    'staff_id' => $adminId,
                    'staff_name_snapshot' => 'Loan Admin',
                    'collector_id' => $adminId,
                    'collector_name_snapshot' => 'Loan Admin',
                    'source_type' => 'demo',
                    'customer_name_snapshot' => $customer->name ?? 'Demo Customer',
                    'customer_phone_snapshot' => $customer->phone ?? null,
                    'product_name_snapshot' => ['Samsung Galaxy A15', 'Redmi Note 13', 'Honda Dream 125', 'ASUS Vivobook 15'][$index % 4],
                    'principal_amount' => $principal,
                    'interest_amount' => $interest,
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'balance_amount' => max(0, $total - $paid),
                    'down_payment' => round($principal * 0.1, 2),
                    'installment_count' => 6,
                    'payment_frequency' => 'monthly',
                    'loan_date' => now()->subMonths(2)->addDays($index)->toDateString(),
                    'first_due_date' => now()->subMonth()->addDays($index)->toDateString(),
                    'maturity_date' => now()->addMonths(4)->addDays($index)->toDateString(),
                    'status' => $status,
                    'approved_at' => now()->subMonths(2)->addDays($index),
                    'approved_by' => $adminId,
                    'note' => 'Demo loan for testing dashboard, reports, payments, and customer app.',
                    'created_at' => now()->subMonths(2)->addDays($index),
                    'updated_at' => now()->subDays($index),
                ])
            );

            $loanId = (int) DB::connection($this->connection)->table('loans')->where('loan_number', $loanNumber)->value('id');
            $loanIds[] = $loanId;
            $this->seedLoanChildren($loanId, $customerId, $principal, $interest, $paid, $adminId, $index);
        }

        return array_values(array_filter($loanIds));
    }

    private function seedLoanChildren(int $loanId, int $customerId, float $principal, float $interest, float $paid, int $adminId, int $index): void
    {
        if ($this->loanTableExists('loan_items')) {
            DB::connection($this->connection)->table('loan_items')->updateOrInsert(
                ['loan_id' => $loanId],
                $this->loanColumns('loan_items', [
                    'product_name_snapshot' => ['Samsung Galaxy A15', 'Redmi Note 13', 'Honda Dream 125', 'ASUS Vivobook 15'][$index % 4],
                    'sku_snapshot' => ['PHONE-A15', 'PHONE-R13', 'BIKE-HONDA', 'LAP-ASUS'][$index % 4],
                    'imei_snapshot' => 'DEMO-IMEI-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                    'qty' => 1,
                    'unit_price' => $principal,
                    'line_total' => $principal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        if ($this->loanTableExists('loan_payment_schedules')) {
            $monthlyPrincipal = round($principal / 6, 2);
            $monthlyInterest = round($interest / 6, 2);
            $remainingPaid = $paid;
            for ($i = 1; $i <= 6; $i++) {
                $due = $monthlyPrincipal + $monthlyInterest;
                $amountPaid = min($due, max(0, $remainingPaid));
                $remainingPaid -= $amountPaid;
                $balance = max(0, $due - $amountPaid);
                $dueDate = now()->subMonth()->addMonths($i - 1)->addDays($index);

                DB::connection($this->connection)->table('loan_payment_schedules')->updateOrInsert(
                    ['loan_id' => $loanId, 'installment_no' => $i],
                    $this->loanColumns('loan_payment_schedules', [
                        'due_date' => $dueDate->toDateString(),
                        'principal_due' => $monthlyPrincipal,
                        'interest_due' => $monthlyInterest,
                        'penalty_due' => $balance > 0 && $dueDate->isPast() ? 2.5 : 0,
                        'amount_due' => $due,
                        'amount_paid' => $amountPaid,
                        'amount_balance' => $balance,
                        'balance_amount' => $balance,
                        'status' => $balance <= 0 ? 'paid' : ($dueDate->isPast() ? 'late' : 'pending'),
                        'paid_at' => $amountPaid > 0 ? $dueDate->copy()->addDays(1) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        if ($paid > 0 && $this->loanTableExists('loan_payments')) {
            $ref = 'PAY-DEMO-'.str_pad((string) $loanId, 5, '0', STR_PAD_LEFT);
            DB::connection($this->connection)->table('loan_payments')->updateOrInsert(
                ['payment_ref_no' => $ref],
                $this->loanColumns('loan_payments', [
                    'loan_id' => $loanId,
                    'customer_id' => $customerId,
                    'received_by' => $adminId,
                    'received_by_name_snapshot' => 'Loan Admin',
                    'channel' => $index % 2 === 0 ? 'cash' : 'bank_transfer',
                    'payment_type' => 'monthly',
                    'amount' => $paid,
                    'total_paid' => $paid,
                    'total_paid_base' => $paid,
                    'paid_at' => now()->subDays(12 - min($index, 10)),
                    'status' => 'confirmed',
                    'note' => 'Demo payment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            $paymentId = (int) DB::connection($this->connection)->table('loan_payments')
                ->where('payment_ref_no', $ref)
                ->value('id');

            $this->seedPaymentDetails($paymentId, $paid, $index);
        }

        if ($this->loanTableExists('loan_status_logs')) {
            DB::connection($this->connection)->table('loan_status_logs')->updateOrInsert(
                ['loan_id' => $loanId, 'to_status' => $index % 3 === 0 ? 'approved' : 'active'],
                $this->loanColumns('loan_status_logs', [
                    'from_status' => 'draft',
                    'changed_by' => $adminId,
                    'changed_by_name_snapshot' => 'Loan Admin',
                    'note' => 'Demo status history',
                    'changed_at' => now()->subMonths(2)->addDays($index),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    private function seedPaymentDetails(int $paymentId, float $paid, int $index): void
    {
        if ($paymentId <= 0 || ! $this->loanTableExists('loan_payment_details')) {
            return;
        }

        $method = $index % 2 === 0 ? 'Cash' : 'ABA';
        DB::connection($this->connection)->table('loan_payment_details')->updateOrInsert(
            ['payment_id' => $paymentId, 'payment_method_snapshot' => $method],
            $this->loanColumns('loan_payment_details', [
                'payment_method_id' => null,
                'method' => strtolower($method),
                'payment_method_snapshot' => $method,
                'currency' => 'USD',
                'amount' => $paid,
                'exchange_rate' => 1,
                'amount_base' => $paid,
                'transaction_no' => 'TXN-DEMO-'.str_pad((string) $paymentId, 5, '0', STR_PAD_LEFT),
                'reference_number' => 'REF-DEMO-'.str_pad((string) $paymentId, 5, '0', STR_PAD_LEFT),
                'note' => 'Demo payment detail',
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );
    }

    private function seedCustomerRelations(array $loanIds, array $customerIds, int $adminId): void
    {
        foreach ($customerIds as $index => $customerId) {
            $loanId = $loanIds[$index] ?? null;

            if ($this->loanTableExists('loan_guarantors')) {
                DB::connection($this->connection)->table('loan_guarantors')->updateOrInsert(
                    ['customer_id' => $customerId, 'loan_id' => $loanId, 'phone' => '011222'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                    $this->loanColumns('loan_guarantors', [
                        'name' => ['Sokha', 'Vanna', 'Rithy', 'Sreymom', 'Piseth', 'Nary', 'Bopha', 'Ratanak'][$index] ?? 'Demo Guarantor',
                        'relationship' => ['Brother', 'Sister', 'Spouse', 'Friend'][$index % 4],
                        'address' => 'Same village as customer',
                        'workplace' => 'Local market',
                        'id_card_number' => 'GID'.str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT),
                        'note' => 'Demo guarantor record',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            if ($this->loanTableExists('loan_customer_notes')) {
                DB::connection($this->connection)->table('loan_customer_notes')->updateOrInsert(
                    ['customer_id' => $customerId, 'loan_id' => $loanId, 'note_type' => 'demo'],
                    $this->loanColumns('loan_customer_notes', [
                        'created_by' => $adminId,
                        'note' => 'Customer is demo-ready with verified phone and address.',
                        'created_at' => now()->subDays($index + 3),
                        'updated_at' => now()->subDays($index + 3),
                    ])
                );
            }

            if ($this->loanTableExists('loan_customer_followups')) {
                DB::connection($this->connection)->table('loan_customer_followups')->updateOrInsert(
                    ['customer_id' => $customerId, 'loan_id' => $loanId, 'follow_up_type' => 'payment_reminder'],
                    $this->loanColumns('loan_customer_followups', [
                        'follow_up_date' => now()->addDays($index + 1)->toDateString(),
                        'status' => $index % 3 === 0 ? 'completed' : 'pending',
                        'assigned_staff_id' => $adminId,
                        'assigned_staff_name_snapshot' => 'Loan Admin',
                        'note' => 'Demo follow-up for upcoming installment.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            if ($loanId && $this->loanTableExists('loan_collection_visits')) {
                DB::connection($this->connection)->table('loan_collection_visits')->updateOrInsert(
                    ['loan_id' => $loanId, 'customer_id' => $customerId],
                    $this->loanColumns('loan_collection_visits', [
                        'collector_id' => $adminId,
                        'collector_name_snapshot' => 'Loan Admin',
                        'latitude' => 11.5564 + ($index * 0.012),
                        'longitude' => 104.9282 + ($index * 0.011),
                        'address_snapshot' => 'Demo customer location',
                        'visited_at' => now()->subDays($index + 1),
                        'result' => $index % 2 === 0 ? 'promise_to_pay' : 'visited',
                        'note' => 'Demo collection visit note.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }

    private function seedChats(array $loanIds, array $customerIds, int $adminId): void
    {
        if (! $this->loanTableExists('loan_chat_threads') || empty($loanIds)) {
            return;
        }

        foreach (array_slice($loanIds, 0, 4) as $index => $loanId) {
            $customerId = $customerIds[$index] ?? null;
            $threadNumber = 'CHAT-DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            DB::connection($this->connection)->table('loan_chat_threads')->updateOrInsert(
                ['thread_number' => $threadNumber],
                $this->loanColumns('loan_chat_threads', [
                    'customer_id' => $customerId,
                    'staff_id' => $adminId,
                    'loan_id' => $loanId,
                    'subject' => ['Payment question', 'Need receipt', 'Change due date', 'Telegram support'][$index],
                    'type' => 'customer_staff',
                    'status' => $index === 2 ? 'pending' : 'open',
                    'priority' => $index === 0 ? 'normal' : 'high',
                    'last_message' => 'Thank you, we will check and update you shortly.',
                    'last_message_type' => 'text',
                    'last_message_at' => now()->subHours($index + 1),
                    'unread_customer_count' => $index,
                    'unread_staff_count' => 0,
                    'created_by_type' => 'customer',
                    'created_by_id' => $customerId ?: 1,
                    'created_at' => now()->subDays($index + 1),
                    'updated_at' => now()->subHours($index + 1),
                ])
            );

            $threadId = (int) DB::connection($this->connection)->table('loan_chat_threads')
                ->where('thread_number', $threadNumber)
                ->value('id');

            $this->seedChatMessages($threadId, $customerId, $adminId, $index);
        }
    }

    private function seedChatMessages(int $threadId, ?int $customerId, int $adminId, int $index): void
    {
        if ($threadId <= 0 || ! $this->loanTableExists('loan_chat_messages')) {
            return;
        }

        $messages = [
            ['sender_type' => 'customer', 'sender_id' => $customerId ?: 1, 'sender_name_snapshot' => 'Demo Customer', 'message' => 'Hello, I want to check my next payment date.'],
            ['sender_type' => 'staff', 'sender_id' => $adminId, 'sender_name_snapshot' => 'Loan Admin', 'message' => 'Your next payment is visible in the schedule. We can help if you need details.'],
            ['sender_type' => 'customer', 'sender_id' => $customerId ?: 1, 'sender_name_snapshot' => 'Demo Customer', 'message' => 'Thank you, please send receipt after payment.'],
        ];

        foreach ($messages as $messageIndex => $message) {
            DB::connection($this->connection)->table('loan_chat_messages')->updateOrInsert(
                ['thread_id' => $threadId, 'local_uuid' => 'demo-'.$threadId.'-'.$messageIndex],
                $this->loanColumns('loan_chat_messages', $message + [
                    'message_type' => 'text',
                    'is_read' => $messageIndex < 2,
                    'read_at' => $messageIndex < 2 ? now()->subHours($index + 1) : null,
                    'created_at' => now()->subHours(($index + 1) * 3 - $messageIndex),
                    'updated_at' => now()->subHours(($index + 1) * 3 - $messageIndex),
                ])
            );
        }

        if ($this->loanTableExists('loan_chat_participants')) {
            foreach ([['customer', $customerId ?: 1, 'Demo Customer'], ['staff', $adminId, 'Loan Admin']] as $participant) {
                DB::connection($this->connection)->table('loan_chat_participants')->updateOrInsert(
                    ['thread_id' => $threadId, 'participant_type' => $participant[0], 'participant_id' => $participant[1]],
                    $this->loanColumns('loan_chat_participants', [
                        'participant_name_snapshot' => $participant[2],
                        'last_read_at' => now()->subHours($index + 1),
                        'joined_at' => now()->subDays($index + 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }

    private function seedTracking(array $loanIds, array $customerIds): void
    {
        if (! $this->loanTableExists('loan_customer_location_latest')) {
            return;
        }

        foreach (array_slice($customerIds, 0, 5) as $index => $customerId) {
            DB::connection($this->connection)->table('loan_customer_location_latest')->updateOrInsert(
                ['customer_id' => $customerId],
                $this->loanColumns('loan_customer_location_latest', [
                    'loan_id' => $loanIds[$index] ?? null,
                    'latitude' => 11.5564 + ($index * 0.012),
                    'longitude' => 104.9282 + ($index * 0.011),
                    'accuracy' => 12.5,
                    'battery_level' => 80 - ($index * 5),
                    'device_id' => 'demo-device-'.$index,
                    'app_version' => '1.0.0',
                    'recorded_at' => now()->subMinutes($index * 12),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            if ($this->loanTableExists('loan_customer_locations_realtime')) {
                DB::connection($this->connection)->table('loan_customer_locations_realtime')->updateOrInsert(
                    ['customer_id' => $customerId, 'recorded_at' => now()->subHours($index + 1)->toDateTimeString()],
                    $this->loanColumns('loan_customer_locations_realtime', [
                        'loan_id' => $loanIds[$index] ?? null,
                        'latitude' => 11.5564 + ($index * 0.012),
                        'longitude' => 104.9282 + ($index * 0.011),
                        'accuracy' => 15.25,
                        'speed' => 0,
                        'heading' => 0,
                        'battery_level' => 75 - ($index * 4),
                        'device_id' => 'demo-device-'.$index,
                        'app_version' => '1.0.0',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }

    private function seedImportExportHistory(array $loanIds, int $adminId): void
    {
        if ($this->loanTableExists('loan_import_batches')) {
            DB::connection($this->connection)->table('loan_import_batches')->updateOrInsert(
                ['batch_code' => 'IMPORT-DEMO-0001'],
                $this->loanColumns('loan_import_batches', [
                    'file_name' => 'demo-loans.csv',
                    'file_path' => 'storage/imports/demo-loans.csv',
                    'file_type' => 'csv',
                    'uploaded_by' => $adminId,
                    'status' => 'completed',
                    'total_rows' => 8,
                    'valid_rows' => 8,
                    'invalid_rows' => 0,
                    'imported_rows' => 8,
                    'note' => 'Demo import batch',
                    'created_at' => now()->subDays(10),
                    'updated_at' => now()->subDays(10),
                ])
            );

            $batchId = (int) DB::connection($this->connection)->table('loan_import_batches')
                ->where('batch_code', 'IMPORT-DEMO-0001')
                ->value('id');

            if ($batchId && $this->loanTableExists('loan_import_rows')) {
                foreach (array_slice($loanIds, 0, 5) as $index => $loanId) {
                    DB::connection($this->connection)->table('loan_import_rows')->updateOrInsert(
                        ['batch_id' => $batchId, 'row_no' => $index + 1],
                        $this->loanColumns('loan_import_rows', [
                            'raw_row_json' => json_encode(['loan_number' => 'LN-DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)]),
                            'normalized_json' => json_encode(['loan_id' => $loanId]),
                            'status' => 'imported',
                            'loan_id' => $loanId,
                            'created_at' => now()->subDays(10),
                            'updated_at' => now()->subDays(10),
                        ])
                    );
                }
            }
        }

        if ($this->loanTableExists('loan_export_logs')) {
            DB::connection($this->connection)->table('loan_export_logs')->updateOrInsert(
                ['export_type' => 'demo_loans', 'file_path' => 'storage/exports/demo-loans.csv'],
                $this->loanColumns('loan_export_logs', [
                    'format' => 'csv',
                    'status' => 'completed',
                    'requested_by' => $adminId,
                    'requested_by_name_snapshot' => 'Loan Admin',
                    'filters_json' => json_encode(['source' => 'demo']),
                    'rows_count' => count($loanIds),
                    'started_at' => now()->subDays(2),
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ])
            );
        }
    }

    private function seedActivity(array $loanIds, int $adminId): void
    {
        if (! $this->loanTableExists('loan_activity_logs')) {
            return;
        }

        foreach (array_slice($loanIds, 0, 6) as $index => $loanId) {
            DB::connection($this->connection)->table('loan_activity_logs')->updateOrInsert(
                ['action' => 'demo.loan.viewed.'.$loanId],
                $this->loanColumns('loan_activity_logs', [
                    'user_id' => $adminId,
                    'user_name_snapshot' => 'Loan Admin',
                    'method' => 'GET',
                    'route_name' => 'loan-management.loans.show',
                    'url' => '/loan-management/loans/'.$loanId,
                    'source' => 'demo',
                    'subject_type' => 'loan',
                    'subject_id' => $loanId,
                    'response_status' => 200,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'LoanManagement Demo Seeder',
                    'created_at' => now()->subHours($index + 2),
                    'updated_at' => now()->subHours($index + 2),
                ])
            );
        }
    }

    private function locationName(int $locationId): ?string
    {
        if (! $this->loanTableExists('loan_business_locations')) {
            return null;
        }

        return DB::connection($this->connection)->table('loan_business_locations')->where('id', $locationId)->value('name');
    }

    private function loanTableExists(string $table): bool
    {
        return Schema::connection($this->connection)->hasTable($table);
    }

    private function loanColumns(string $table, array $payload): array
    {
        if (! $this->loanTableExists($table)) {
            return [];
        }

        return array_intersect_key($payload, array_flip(Schema::connection($this->connection)->getColumnListing($table)));
    }
}
