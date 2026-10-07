<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LoanManagement\Http\Controllers\LoanAbaPaywayController;
use Modules\LoanManagement\Http\Controllers\LoanInstallmentListController;
use Modules\LoanManagement\Http\Controllers\LoanPaymentController;
use Modules\LoanManagement\Http\Controllers\StaffMobileActionController;
use Modules\LoanManagement\Services\LoanPenaltyAccrualService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LoanFinancialControlsTest extends TestCase
{
    private FinancialControlsLoanController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        config(['database.connections.mysql_loan' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('mysql_loan');
        $migration = require dirname(__DIR__, 2).'/database/migrations/2026_05_13_000001_create_core_loan_management_tables.php';
        $migration->up();
        $migration = require dirname(__DIR__, 2).'/database/migrations/2026_10_07_000001_add_daily_penalty_rate_to_loans.php';
        $migration->up();
        Schema::connection('mysql_loan')->table('loans', function (Blueprint $table) {
            $table->string('interest_type')->default('flat');
            $table->decimal('interest_rate', 18, 6)->default(0);
            $table->string('interest_rate_type')->nullable();
            $table->integer('duration_months')->default(1);
        });
        Schema::connection('mysql_loan')->table('loan_payment_schedules', function (Blueprint $table) {
            $table->decimal('balance_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('schedule_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
        });
        Schema::connection('mysql_loan')->table('loan_payments', function (Blueprint $table) {
            $table->decimal('total_paid_base', 18, 2)->default(0);
            $table->decimal('total_paid', 18, 2)->default(0);
            $table->string('payment_type')->nullable();
        });
        $this->authenticate(['*']);
        $this->controller = new FinancialControlsLoanController;
        $this->controller->resetSchemaCaches();
        DB::connection('mysql_loan')->table('loans')->insert([
            'id' => 1, 'loan_number' => 'FIN-001', 'customer_id' => 1, 'status' => 'active',
            'total_amount' => 110, 'principal_amount' => 100, 'balance_amount' => 110,
        ]);
        $this->schedule(1, 100, 10);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('mysql_loan');
        parent::tearDown();
    }

    private function authenticate(array $permissions): void
    {
        auth()->setUser(new class(['id' => 1, 'name' => 'Officer', 'username' => 'officer'], $permissions) extends GenericUser {
            public function __construct(array $attributes, private array $permissions) { parent::__construct($attributes); }
            public function can($ability, $arguments = []): bool
            {
                return in_array('*', $this->permissions, true) || in_array($ability, $this->permissions, true);
            }
        });
    }

    private function schedule(int $id, float $principal, float $interest = 0, float $penalty = 0, string $status = 'pending'): void
    {
        $total = $principal + $interest + $penalty;
        DB::connection('mysql_loan')->table('loan_payment_schedules')->insert([
            'id' => $id, 'loan_id' => 1, 'installment_no' => $id, 'due_date' => '2026-09-01',
            'principal_due' => $principal, 'interest_due' => $interest, 'penalty_due' => $penalty,
            'amount_due' => $total, 'schedule_amount' => $total,
            'amount_balance' => $total, 'balance_amount' => $total, 'status' => $status,
        ]);
    }

    private function request(array $data): Request
    {
        return Request::create('/', 'POST', $data, [], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    private function settle(array $overrides = [])
    {
        return $this->controller->processSettlement($this->request(array_merge([
            'settlement_amount' => 100, 'interest_discount' => 10,
            'payment_method' => 'cash', 'paid_date' => '2026-10-07',
        ], $overrides)), 1);
    }

    private function reschedule(array $overrides = [])
    {
        return $this->controller->processReschedule($this->request(array_merge([
            'reschedule_amount' => 100, 'new_duration_months' => 2,
            'new_interest_rate' => 12, 'interest_type' => 'reducing_balance',
            'first_due_date' => '2026-10-31', 'reason' => 'Approved restructuring',
        ], $overrides)), 1);
    }

    private function rejects(callable $action, int $status): void
    {
        try {
            $action();
            $this->fail('Expected financial action to be rejected.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    public function testViewOnlyStaffCannotPerformFinancialActions(): void
    {
        $this->authenticate(['loan_management.view']);
        $this->rejects(fn () => $this->settle(), 403);
        $this->rejects(fn () => $this->reschedule(), 403);
        $this->rejects(fn () => $this->controller->changeStatus($this->request(['status' => 'completed']), 1), 403);
        $this->rejects(fn () => $this->controller->storePayment($this->request([]), 1), 403);
        $this->rejects(fn () => $this->controller->destroy(1), 403);
        $this->rejects(fn () => (new LoanPaymentController)->destroy($this->request([]), 1), 403);
    }

    public function testSimulationCannotPostEvenForAnAdministrator(): void
    {
        $this->rejects(fn () => (new LoanAbaPaywayController)->simulateSuccess('any-reference'), 403);
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_payments')->count());
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loans')->value('balance_amount'));
    }

    public function testUnconfiguredGatewayCannotCreateFakeTransactionsOrCheckout(): void
    {
        $this->rejects(fn () => (new LoanAbaPaywayController)->create($this->request(['amount' => 100])), 503);
        $this->rejects(fn () => (new LoanAbaPaywayController)->checkout('any-reference'), 503);
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_aba_payway_transactions')->count());
    }

    public function testSettlementRejectsUnderpaymentAndLeavesBalancesUnchanged(): void
    {
        $this->rejects(fn () => $this->settle(['settlement_amount' => 1]), 422);
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loans')->value('balance_amount'));
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_payments')->count());
    }

    public function testSettlementRejectsDiscountOfPrincipal(): void
    {
        $this->rejects(fn () => $this->settle(['interest_discount' => 100, 'settlement_amount' => 10]), 422);
    }

    public function testSettlementIncludesFeeAndKeepsActualCashAndRebateSeparate(): void
    {
        $this->settle(['settlement_amount' => 102, 'prepayment_fee' => 2]);
        $loan = DB::connection('mysql_loan')->table('loans')->first();
        $payment = DB::connection('mysql_loan')->table('loan_payments')->first();
        $schedule = DB::connection('mysql_loan')->table('loan_payment_schedules')->first();
        $this->assertSame('completed', $loan->status);
        $this->assertEquals(102, $loan->paid_amount);
        $this->assertEquals(102, $payment->total_paid_base);
        $this->assertEquals(100, $schedule->amount_paid);
        $this->assertEquals(100, $schedule->paid_amount);
        $this->assertEquals(10, $schedule->discount_amount);
        $this->assertEquals(0, $schedule->balance_amount);
        $this->assertEquals(0, $schedule->amount_balance);
        $this->rejects(fn () => $this->settle(['settlement_amount' => 102, 'prepayment_fee' => 2]), 422);
        $this->assertSame(1, DB::connection('mysql_loan')->table('loan_payments')->count());
    }

    public function testSettlementRejectsMissingFeePayment(): void
    {
        $this->rejects(fn () => $this->settle(['prepayment_fee' => 2]), 422);
    }

    public function testPartiallyPaidLoanSettlementUsesRemainingBalance(): void
    {
        DB::connection('mysql_loan')->table('loan_payment_schedules')->update([
            'amount_paid' => 40, 'paid_amount' => 40, 'amount_balance' => 70, 'balance_amount' => 70, 'status' => 'partial',
        ]);
        DB::connection('mysql_loan')->table('loans')->update(['paid_amount' => 40, 'balance_amount' => 70]);
        $this->settle(['settlement_amount' => 60]);
        $this->assertEquals(100, DB::connection('mysql_loan')->table('loans')->value('paid_amount'));
        $this->assertEquals(100, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_paid'));
    }

    public function testOverduePaymentAndAdvanceCreditUpdateEveryPaidAndBalanceAlias(): void
    {
        DB::connection('mysql_loan')->table('loan_payment_schedules')->update(['status' => 'overdue']);
        $this->schedule(2, 20);
        $this->schedule(3, 30);
        $this->controller->allocate(145);
        $rows = DB::connection('mysql_loan')->table('loan_payment_schedules')->orderBy('id')->get();
        $this->assertEquals([110, 20, 15], $rows->pluck('amount_paid')->all());
        $this->assertEquals([110, 20, 15], $rows->pluck('paid_amount')->all());
        $this->assertEquals([0, 0, 15], $rows->pluck('amount_balance')->all());
        $this->assertEquals([0, 0, 15], $rows->pluck('balance_amount')->all());
        $this->assertEquals(['paid', 'paid', 'partial'], $rows->pluck('status')->all());
    }

    public function testSmallShortfallIsNotInventedAsPayment(): void
    {
        $this->controller->allocate(109.99);
        $this->assertEquals(109.99, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_paid'));
        $this->assertEquals(0.01, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_balance'));
    }

    public function testAlternatePayoffCannotCloseAnUnderpaidLoan(): void
    {
        $this->rejects(fn () => $this->controller->payoff(1, 10), 422);
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_balance'));
        $this->controller->payoff(100, 10);
        $this->assertEquals(0, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_balance'));
        $this->assertNull(DB::connection('mysql_loan')->table('loan_payment_schedules')->value('deleted_at'));
    }

    public function testNormalPaymentRejectsOverpaymentBeforeInsertingReceipt(): void
    {
        $this->rejects(fn () => $this->controller->storePayment($this->request([
            'paid_date' => '2026-10-07', 'payment_lines' => [['amount' => 111, 'method' => 'cash']],
        ]), 1), 422);
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_payments')->count());
    }

    public function testRescheduleCalculatesDecliningInterestAndPreservesPenalties(): void
    {
        DB::connection('mysql_loan')->table('loan_payment_schedules')->update(['penalty_due' => 5, 'amount_balance' => 115, 'balance_amount' => 115]);
        $this->reschedule();
        $rows = DB::connection('mysql_loan')->table('loan_payment_schedules')->whereNull('deleted_at')->orderBy('installment_no')->get();
        $this->assertEquals([1, 0.5], $rows->pluck('interest_due')->all());
        $this->assertEquals([5, 0], $rows->pluck('penalty_due')->all());
        $this->assertEquals(['2026-10-31', '2026-11-30'], $rows->pluck('due_date')->all());
        $this->assertEquals(106.5, DB::connection('mysql_loan')->table('loans')->value('balance_amount'));
        $this->assertEquals(1, DB::connection('mysql_loan')->table('loans')->value('interest_rate'));
        $this->assertSame(1, DB::connection('mysql_loan')->table('loan_payment_schedules')->whereNotNull('deleted_at')->count());
    }

    public function testRescheduleFlatInterestAndExplicitPenaltyWaiver(): void
    {
        DB::connection('mysql_loan')->table('loan_payment_schedules')->update(['penalty_due' => 5]);
        $this->reschedule(['interest_type' => 'flat', 'waive_penalties' => true]);
        $rows = DB::connection('mysql_loan')->table('loan_payment_schedules')->whereNull('deleted_at')->get();
        $this->assertEquals([1, 1], $rows->pluck('interest_due')->all());
        $this->assertEquals(0, $rows->sum('penalty_due'));
        $this->assertEquals(102, DB::connection('mysql_loan')->table('loans')->value('balance_amount'));
    }

    public function testPenaltiesUseFixedRateAndAreAppliedOncePerDay(): void
    {
        DB::connection('mysql_loan')->table('loans')->update(['daily_penalty_rate' => 2, 'penalty_amount' => 99]);
        $service = new LoanPenaltyAccrualService;
        Carbon::setTestNow('2026-10-07');
        $service->accrueDaily();
        $service->accrueDaily();
        Carbon::setTestNow('2026-10-08');
        $service->accrueDaily();
        $this->assertEquals(4, DB::connection('mysql_loan')->table('loans')->value('penalty_amount'));
        $this->assertEquals(2, DB::connection('mysql_loan')->table('loans')->value('daily_penalty_rate'));
        $this->assertEquals(114, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('balance_amount'));
        $this->assertSame(2, DB::connection('mysql_loan')->table('loan_penalties')->count());
    }

    public function testZeroPenaltyRateAndDryRunDoNotPostCharges(): void
    {
        DB::connection('mysql_loan')->table('loans')->update(['daily_penalty_rate' => 0]);
        $service = new LoanPenaltyAccrualService;
        $service->accrueDaily(true);
        $this->assertSame('pending', DB::connection('mysql_loan')->table('loan_payment_schedules')->value('status'));
        $service->accrueDaily();
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_penalties')->count());
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loans')->value('balance_amount'));
    }

    public function testPenaltyHistoryIsRequiredForIdempotentAccrual(): void
    {
        Schema::connection('mysql_loan')->drop('loan_penalties');
        $this->rejects(fn () => (new LoanPenaltyAccrualService)->accrueDaily(), 409);
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_balance'));
    }

    public function testPendingLoansDoNotAccruePenalties(): void
    {
        DB::connection('mysql_loan')->table('loans')->update(['status' => 'pending']);
        (new LoanPenaltyAccrualService)->accrueDaily();
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_penalties')->count());
    }

    public function testStatusCannotCompleteAnOutstandingLoan(): void
    {
        $this->rejects(fn () => $this->controller->changeStatus($this->request(['status' => 'completed']), 1), 422);
        $this->assertSame('active', DB::connection('mysql_loan')->table('loans')->value('status'));
    }

    public function testActiveLoanCannotBeDeletedAndDraftIsOnlyArchived(): void
    {
        $this->rejects(fn () => $this->controller->destroy(1), 409);
        DB::connection('mysql_loan')->table('loans')->update(['status' => 'draft']);
        $this->controller->destroy(1);
        $this->assertSame(1, DB::connection('mysql_loan')->table('loans')->count());
        $this->assertNotNull(DB::connection('mysql_loan')->table('loans')->value('deleted_at'));
    }

    public function testPaymentHistoryPreventsLoanDeletionAndStatusReset(): void
    {
        $this->payment();
        DB::connection('mysql_loan')->table('loans')->update(['status' => 'cancelled']);
        $this->rejects(fn () => $this->controller->destroy(1), 409);
        $this->rejects(fn () => $this->controller->changeStatus($this->request(['status' => 'draft']), 1), 422);
    }

    private function payment(): void
    {
        DB::connection('mysql_loan')->table('loan_payments')->insert([
            'id' => 1, 'loan_id' => 1, 'customer_id' => 1, 'amount' => 10, 'total_paid' => 10,
            'total_paid_base' => 10, 'status' => 'confirmed',
        ]);
    }

    public function testPostedPaymentWithoutScheduleLinkCannotBeEditedOrDeleted(): void
    {
        $this->payment();
        $controller = new LoanPaymentController;
        $this->rejects(fn () => $controller->destroy($this->request([]), 1), 409);
        $this->rejects(fn () => $controller->update($this->request(['amount' => 1]), 1), 409);
        $this->assertSame(1, DB::connection('mysql_loan')->table('loan_payments')->count());
        $this->assertEquals(10, DB::connection('mysql_loan')->table('loan_payments')->value('amount'));
    }

    public function testMobileCannotBypassPostedPaymentProtection(): void
    {
        $this->payment();
        $mobile = new StaffMobileActionController;
        $this->rejects(fn () => $mobile->deletePayment($this->request([]), 1), 409);
        $this->rejects(fn () => $mobile->updatePayment($this->request(['amount' => 1]), 1), 409);
        $this->assertSame(1, DB::connection('mysql_loan')->table('loan_payments')->count());
    }

    public function testCorruptZeroBaseAmountDoesNotPermitPaymentDeletion(): void
    {
        $this->payment();
        DB::connection('mysql_loan')->table('loan_payments')->update(['total_paid_base' => 0, 'total_paid' => 0]);
        $this->rejects(fn () => (new LoanPaymentController)->destroy($this->request([]), 1), 409);
        $this->rejects(fn () => (new StaffMobileActionController)->deletePayment($this->request([]), 1), 409);
    }

    private function mobilePayment(array $overrides = [])
    {
        return (new StaffMobileActionController)->receivePayment($this->request(array_merge([
            'loan_id' => 1, 'customer_id' => 1, 'currency' => 'USD', 'amount' => 10,
            'details' => [['method' => 'cash', 'amount' => 10]],
        ], $overrides)));
    }

    public function testMobileRejectsWrongCustomerAndUnbalancedPaymentDetails(): void
    {
        $this->rejects(fn () => $this->mobilePayment(['customer_id' => 2]), 422);
        $this->rejects(fn () => $this->mobilePayment(['details' => [['method' => 'cash', 'amount' => 1]]]), 422);
        $this->rejects(fn () => $this->mobilePayment(['currency' => 'KHR']), 422);
        $this->rejects(fn () => $this->mobilePayment(['amount' => 111, 'details' => [['method' => 'cash', 'amount' => 111]]]), 422);
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_payments')->count());
    }

    public function testMobilePaysOverdueScheduleAndIgnoresArchivedSchedules(): void
    {
        DB::connection('mysql_loan')->table('loan_payment_schedules')->update(['status' => 'overdue']);
        $this->schedule(2, 200);
        DB::connection('mysql_loan')->table('loan_payment_schedules')->where('id', 2)->update(['deleted_at' => now()]);
        $this->mobilePayment(['amount' => 110, 'details' => [['method' => 'cash', 'amount' => 110]]]);
        $loan = DB::connection('mysql_loan')->table('loans')->first();
        $this->assertSame('completed', $loan->status);
        $this->assertEquals(0, $loan->balance_amount);
        $this->assertEquals(110, $loan->paid_amount);
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loan_payment_schedules')->where('id', 1)->value('amount_paid'));
        $this->assertEquals(0, DB::connection('mysql_loan')->table('loan_payment_schedules')->where('id', 2)->value('amount_paid'));
    }

    public function testMobileChecksConvertedDetailTotalAndScheduleOwnership(): void
    {
        $this->rejects(fn () => $this->mobilePayment(['schedule_ids' => [1, 99]]), 422);
        $this->rejects(fn () => $this->mobilePayment(['details' => [['method' => 'cash', 'amount' => 40000, 'currency' => 'KHR']]]), 422);
        $this->mobilePayment(['details' => [['method' => 'cash', 'amount' => 40000, 'currency' => 'KHR', 'exchange_rate' => 4000]]]);
        $this->assertEquals(10, DB::connection('mysql_loan')->table('loans')->value('paid_amount'));
        $this->assertEquals(100, DB::connection('mysql_loan')->table('loans')->value('balance_amount'));
    }

    public function testMobileViewOnlyStaffCannotCollectOrChangePayments(): void
    {
        $this->authenticate(['loan_management.view']);
        $this->rejects(fn () => $this->mobilePayment(), 403);
        $this->rejects(fn () => (new StaffMobileActionController)->updatePayment($this->request([]), 1), 403);
        $this->rejects(fn () => (new StaffMobileActionController)->deletePayment($this->request([]), 1), 403);
    }

    public function testWebPaymentRecordsActualCashAndClosesOverdueLoan(): void
    {
        DB::connection('mysql_loan')->table('loan_payment_schedules')->update(['status' => 'overdue']);
        $response = $this->controller->storePayment($this->request([
            'paid_date' => '2026-10-07', 'payment_lines' => [['amount' => 110, 'method' => 'cash']],
        ]), 1);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('completed', DB::connection('mysql_loan')->table('loans')->value('status'));
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loan_payments')->value('amount'));
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_paid'));
    }

    public function testWebPayoffRejectsUnderpaymentAndThenPostsConsistentRebate(): void
    {
        $data = ['paid_date' => '2026-10-07', 'pay_off' => true, 'pay_off_discount_amount' => 10,
            'payment_lines' => [['amount' => 1, 'method' => 'cash']]];
        $this->rejects(fn () => $this->controller->storePayment($this->request($data), 1), 422);
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_payments')->count());
        $data['payment_lines'][0]['amount'] = 100;
        $response = $this->controller->storePayment($this->request($data), 1);
        $this->assertSame(200, $response->getStatusCode());
        $loan = DB::connection('mysql_loan')->table('loans')->first();
        $this->assertSame('completed', $loan->status);
        $this->assertEquals(100, $loan->paid_amount);
        $this->assertEquals(100, $loan->total_amount);
        $this->assertEquals(10, $loan->discount_amount);
    }

    public function testMobilePayoffCannotBePartial(): void
    {
        $this->rejects(fn () => $this->mobilePayment(['pay_off' => true]), 422);
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_payments')->count());
    }

    public function testCompletionChecksSchedulesEvenWhenLoanSummaryIsStale(): void
    {
        DB::connection('mysql_loan')->table('loans')->update(['balance_amount' => 0]);
        $this->rejects(fn () => $this->controller->changeStatus($this->request(['status' => 'completed']), 1), 422);
    }

    public function testRestructureKeepsHistoricalPartialCashInTotalAmount(): void
    {
        DB::connection('mysql_loan')->table('loans')->update(['paid_amount' => 40]);
        DB::connection('mysql_loan')->table('loan_payment_schedules')->update([
            'amount_paid' => 40, 'paid_amount' => 40, 'balance_amount' => 70, 'amount_balance' => 70, 'status' => 'partial',
        ]);
        $this->reschedule();
        $loan = DB::connection('mysql_loan')->table('loans')->first();
        $this->assertEquals(40, $loan->paid_amount);
        $this->assertEquals(141.5, $loan->total_amount);
        $this->assertEquals(101.5, $loan->balance_amount);
    }

    public function testScheduleEditorsCannotEraseOrRewritePaymentHistory(): void
    {
        $this->payment();
        $this->rejects(fn () => $this->controller->destroySchedule($this->request([]), 1, 1), 409);
        $this->rejects(fn () => $this->controller->updateSchedule($this->request([]), 1, 1), 409);
        $this->rejects(fn () => $this->controller->updateSchedulesFromEdit($this->request([]), 1), 409);
        $this->rejects(fn () => $this->controller->refreshSchedules($this->request([]), 1), 409);
        $this->assertSame(1, DB::connection('mysql_loan')->table('loan_payments')->count());
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_balance'));
    }

    public function testScheduleEditorCannotCreatePaymentsInsteadOfUsingCollection(): void
    {
        $this->rejects(fn () => $this->controller->updateSchedule($this->request(['paid_amount' => 110, 'status' => 'paid']), 1, 1), 422);
        $this->assertSame(0, DB::connection('mysql_loan')->table('loan_payments')->count());
        $this->assertEquals(110, DB::connection('mysql_loan')->table('loan_payment_schedules')->value('amount_balance'));
    }

    public function testScheduleEditorCannotMarkUnpaidBalanceAsZeroWithoutCash(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->updateSchedule($this->request(['balance_amount' => 0, 'status' => 'auto']), 1, 1);
    }

    public function testRescheduleFormConvertsStoredMonthlyInterestToAnnualRate(): void
    {
        DB::connection('mysql_loan')->table('loans')->update(['interest_rate' => 1.5, 'interest_rate_type' => 'monthly']);
        $view = $this->controller->rescheduleModal(1);
        $this->assertEquals(18, $view->getData()['annualInterestRate']);
    }
}

class FinancialControlsLoanController extends LoanInstallmentListController
{
    public function resetSchemaCaches(): void
    {
        self::$loanTableExistsCache = [];
        self::$loanColumnCache = [];
    }

    protected function ultimatePosPaymentTypes(object $loanRow): array
    {
        return ['cash' => 'Cash'];
    }

    public function allocate(float $amount): void
    {
        DB::connection('mysql_loan')->transaction(fn () => $this->applyLoanPaymentToSchedules(1, $amount, '2026-10-07 10:00:00'));
    }

    public function payoff(float $amount, float $discount): void
    {
        DB::connection('mysql_loan')->transaction(fn () => $this->applyLoanPayOffToSchedules(1, $amount, $discount, '2026-10-07 10:00:00'));
    }
}
