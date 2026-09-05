<?php

namespace Modules\LoanManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Exports\ArrayExport;
use Maatwebsite\Excel\Facades\Excel;
use Modules\LoanManagement\Services\BusinessSettingsService;

class DashboardController extends Controller
{
    protected function allow(string $permission): void
    {
        abort_unless(
            auth()->user()->can($permission) || auth()->user()->can('loan_management.view'),
            403,
            'Unauthorized action.'
        );
    }

    public function index()
    {
        $this->allow('loan_management.dashboard.view');

        return view('loanmanagement::dashboard.index');
    }

    public function placeholder(Request $request, string $page)
    {
        $this->allow('loan_management.view');

        if ($page === 'Blacklist') {
            return $this->blacklistIndex($request);
        }

        $payload = $this->buildPagePayload($page);
        return view('loanmanagement::dashboard.placeholder', [
            'page' => $page,
            'payload' => $payload,
        ]);
    }

    public function blacklistIndex(Request $request)
    {
        $this->allow('loan_management.view');

        $isKhmer = session('user.language', config('app.locale')) === 'km';
        $filters = $this->blacklistFilters($request);
        $conn = DB::connection('mysql_loan');

        $customers = collect();
        $summary = [
            'total_blacklisted' => 0,
            'total_debt_at_risk' => 0,
            'linked_loans_count' => 0,
            'flagged_this_month' => 0,
        ];

        if (Schema::connection('mysql_loan')->hasTable('loan_customers')) {
            $hasLoans = Schema::connection('mysql_loan')->hasTable('loans');
            $query = $conn->table('loan_customers as c')
                ->where('c.blacklist_status', 1)
                ->whereNull('c.deleted_at');
            $this->applyBlacklistFilters($query, $filters);

            if ($hasLoans) {
                $query->leftJoin('loans as l', function ($join) {
                    $join->on('l.customer_id', '=', 'c.id')->whereNull('l.deleted_at');
                });
                $query->selectRaw('
                    c.id,
                    c.customer_code,
                    c.name,
                    c.khmer_name,
                    c.phone,
                    c.id_card_number,
                    c.address,
                    c.blacklist_status,
                    c.blacklist_reason,
                    c.blacklist_date,
                    c.blacklist_by,
                    COUNT(DISTINCT l.id) as total_loans,
                    COALESCE(SUM(l.balance_amount), 0) as total_debt
                ')->groupBy('c.id');
            } else {
                $query->selectRaw('
                    c.id,
                    c.customer_code,
                    c.name,
                    c.khmer_name,
                    c.phone,
                    c.id_card_number,
                    c.address,
                    c.blacklist_status,
                    c.blacklist_reason,
                    c.blacklist_date,
                    c.blacklist_by,
                    0 as total_loans,
                    0 as total_debt
                ');
            }

            $customers = $query->orderByDesc('c.blacklist_date')->orderByDesc('c.id')->get();

            // Summary calculations
            $summary['total_blacklisted'] = (int) $customers->count();
            $summary['total_debt_at_risk'] = (float) $customers->sum('total_debt');
            $summary['linked_loans_count'] = (int) $customers->sum('total_loans');

            $startOfMonth = now()->startOfMonth();
            $summary['flagged_this_month'] = (int) $customers->filter(function ($c) use ($startOfMonth) {
                return ! empty($c->blacklist_date) && \Carbon\Carbon::parse($c->blacklist_date)->greaterThanOrEqualTo($startOfMonth);
            })->count();
        }

        // Active customers who can be flagged
        $eligibleCustomers = collect();
        if (Schema::connection('mysql_loan')->hasTable('loan_customers')) {
            $eligibleCustomers = $conn->table('loan_customers')
                ->where('blacklist_status', 0)
                ->whereNull('deleted_at')
                ->select('id', 'customer_code', 'name', 'phone')
                ->orderBy('name')
                ->limit(300)
                ->get();
        }

        // Staff names
        $staffIds = $customers->pluck('blacklist_by')->filter()->unique()->values();
        $staffNames = [];
        if ($staffIds->isNotEmpty() && Schema::hasTable('users')) {
            $staffNames = DB::table('users')
                ->whereIn('id', $staffIds)
                ->selectRaw("id, COALESCE(NULLIF(TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))), ''), username) as display_name")
                ->pluck('display_name', 'id')
                ->all();
        }

        return view('loanmanagement::blacklist.index', compact(
            'customers',
            'summary',
            'eligibleCustomers',
            'staffNames',
            'isKhmer',
            'filters'
        ));
    }

    protected function blacklistFilters(Request $request): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $dateRange = trim((string) $request->input('date_range', ''));

        if ($dateRange !== '' && (! $request->filled('date_from') || ! $request->filled('date_to')) && ($parsedRange = $this->parseSummaryDateRange($dateRange))) {
            [$dateFrom, $dateTo] = $parsedRange;
        }

        try {
            $dateFrom = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->toDateString() : '';
        } catch (\Throwable $e) {
            $dateFrom = '';
        }

        try {
            $dateTo = $dateTo ? \Carbon\Carbon::parse($dateTo)->toDateString() : '';
        } catch (\Throwable $e) {
            $dateTo = '';
        }

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'search' => trim((string) $request->input('search', '')),
        ];
    }

    protected function applyBlacklistFilters($query, array $filters): void
    {
        $columns = Schema::connection('mysql_loan')->hasTable('loan_customers')
            ? Schema::connection('mysql_loan')->getColumnListing('loan_customers')
            : [];

        if (in_array('blacklist_date', $columns, true)) {
            if (! empty($filters['date_from'])) {
                $query->whereDate('c.blacklist_date', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('c.blacklist_date', '<=', $filters['date_to']);
            }
        }

        if (($filters['search'] ?? '') !== '') {
            $like = '%'.$filters['search'].'%';
            $searchColumns = array_values(array_filter([
                'customer_code',
                'name',
                'khmer_name',
                'phone',
                'id_card_number',
                'blacklist_reason',
            ], fn ($column) => in_array($column, $columns, true)));

            if ($searchColumns) {
                $query->where(function ($where) use ($searchColumns, $like) {
                    foreach ($searchColumns as $column) {
                        $where->orWhere('c.'.$column, 'like', $like);
                    }
                });
            }
        }
    }

    public function collectionVisits(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'collector' => trim((string) $request->input('collector', '')),
            'result' => trim((string) $request->input('result', '')),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        if (! Schema::connection('mysql_loan')->hasTable('loan_collection_visits')) {
            return view('loanmanagement::collection_visits.index', [
                'filters' => $filters,
                'summary' => ['total' => 0, 'today' => 0, 'pending' => 0, 'completed' => 0],
                'visits' => collect(),
                'collectors' => [],
                'results' => [],
            ]);
        }

        $query = $this->collectionVisitsQuery();
        $this->applyCollectionVisitFilters($query, $filters);

        $summaryQuery = clone $query;
        $dateColumn = $this->collectionVisitDateColumn();
        $resultColumn = $this->collectionVisitResultColumn();

        $summary = [
            'total' => (int) (clone $summaryQuery)->count(),
            'today' => $dateColumn ? (int) (clone $summaryQuery)->whereDate('v.'.$dateColumn, now()->toDateString())->count() : 0,
            'pending' => $resultColumn ? (int) (clone $summaryQuery)->whereIn('v.'.$resultColumn, ['pending', 'scheduled', 'open'])->count() : 0,
            'completed' => $resultColumn ? (int) (clone $summaryQuery)->whereIn('v.'.$resultColumn, ['visited', 'completed', 'success', 'paid', 'promise_to_pay'])->count() : 0,
        ];

        $visits = $query
            ->orderByDesc($dateColumn ? 'v.'.$dateColumn : 'v.id')
            ->orderByDesc('v.id')
            ->paginate(25)
            ->appends($request->query());

        return view('loanmanagement::collection_visits.index', [
            'filters' => $filters,
            'summary' => $summary,
            'visits' => $visits,
            'collectors' => $this->collectionVisitCollectors(),
            'results' => $this->collectionVisitResults(),
        ]);
    }

    public function overdue()
    {
        $this->allow('loan_management.overdue.view');

        return view('loanmanagement::overdue.index');
    }

    public function yearlyLoanSummary(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->yearlySummaryFilters($request);
        $payload = $this->buildYearlyLoanSummary($filters);

        if ($request->input('export') === 'csv') {
            return $this->downloadYearlyLoanSummaryCsv($payload, $filters);
        }

        return view('loanmanagement::reports.yearly_loan_summary', [
            'filters' => $filters,
            'payload' => $payload,
            'locations' => $this->loanReportLocationOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function dailyLoanSummary(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->dailySummaryFilters($request);
        $payload = $this->buildPeriodicLoanSummary($filters, 'daily');

        if ($request->input('export') === 'csv') {
            return $this->downloadPeriodicLoanSummaryCsv($payload, $filters, 'daily');
        }

        return view('loanmanagement::reports.periodic_loan_summary', [
            'period' => 'daily',
            'title' => 'Daily Loan Summary',
            'filters' => $filters,
            'payload' => $payload,
            'locations' => $this->loanReportLocationOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function monthlyLoanSummary(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->monthlySummaryFilters($request);
        $payload = $this->buildPeriodicLoanSummary($filters, 'monthly');

        if ($request->input('export') === 'csv') {
            return $this->downloadPeriodicLoanSummaryCsv($payload, $filters, 'monthly');
        }

        return view('loanmanagement::reports.periodic_loan_summary', [
            'period' => 'monthly',
            'title' => 'Monthly Loan Summary',
            'filters' => $filters,
            'payload' => $payload,
            'locations' => $this->loanReportLocationOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function dashboardReports(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->dashboardReportFilters($request);
        $recentActivityFilters = $this->recentActivityFilters($request);

        return view('loanmanagement::reports.dashboard_reports', [
            'filters' => $filters,
            'recentActivityFilters' => $recentActivityFilters,
            'payload' => $this->buildDashboardReports($filters, $recentActivityFilters),
            'locations' => $this->loanReportLocationOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function paymentSummaryByType(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->paymentSummaryFilters($request);

        return view('loanmanagement::reports.payment_summary_by_type', [
            'filters' => $filters,
            'rows' => $this->dashboardPaymentMethodRows($filters),
            'locations' => $this->loanReportLocationOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function installmentReports(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->installmentReportFilters($request);
        $emptySummary = ['count' => 0, 'principal' => 0, 'paid' => 0, 'balance' => 0, 'overdue' => 0];

        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return view('loanmanagement::reports.installment_index', [
                'filters' => $filters,
                'rows' => collect(),
                'summary' => $emptySummary,
                'locations' => $this->loanReportLocationOptions(),
                'statusOptions' => [],
                'paymentStatusOptions' => $this->installmentPaymentStatusOptions(),
                'isKhmer' => $this->loanReportIsKhmer(),
            ]);
        }

        $query = $this->installmentReportQuery();
        $this->applyInstallmentReportFilters($query, $filters);

        $summaryQuery = clone $query;
        $amountExpressions = $this->installmentReportAmountExpressions();
        $overdueExpression = $this->installmentScheduleExists('DATE(s.due_date) < CURDATE() AND '.$this->installmentScheduleOpenCondition('s'), $this->installmentScheduleBaseWhere());
        $summary = [
            'count' => (int) (clone $summaryQuery)->count(),
            'principal' => (float) (clone $summaryQuery)->sum(DB::raw($amountExpressions['principal'])),
            'paid' => (float) (clone $summaryQuery)->sum(DB::raw($amountExpressions['paid'])),
            'balance' => (float) (clone $summaryQuery)->sum(DB::raw($amountExpressions['balance'])),
            'overdue' => (int) (clone $summaryQuery)->whereRaw($overdueExpression.' = 1')->count(),
        ];

        $rows = $query
            ->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->get();

        return view('loanmanagement::reports.installment_index', [
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'locations' => $this->loanReportLocationOptions(),
            'statusOptions' => $this->installmentStatusOptions(),
            'paymentStatusOptions' => $this->installmentPaymentStatusOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function loanSchedules(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->loanScheduleFilters($request);
        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 25;
        }
        $emptySummary = [
            'count' => 0,
            'due_today' => 0,
            'open' => 0,
            'paid' => 0,
            'overdue' => 0,
            'due_total' => 0,
            'paid_total' => 0,
            'balance_total' => 0,
        ];

        if (! Schema::connection('mysql_loan')->hasTable('loan_payment_schedules')
            || ! Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'loan_id')
            || ! Schema::connection('mysql_loan')->hasTable('loans')
            || ! Schema::connection('mysql_loan')->hasColumn('loans', 'id')) {
            return view('loanmanagement::schedules.index', [
                'filters' => $filters,
                'rows' => collect(),
                'summary' => $emptySummary,
                'locations' => $this->loanReportLocationOptions(),
                'statusOptions' => $this->loanScheduleStatusOptions(),
                'loanStatusOptions' => $this->installmentStatusOptions(),
                'isKhmer' => $this->loanReportIsKhmer(),
                'perPage' => $perPage,
            ]);
        }

        $query = $this->loanScheduleQuery();
        $this->applyLoanScheduleFilters($query, $filters);

        $summaryQuery = clone $query;
        $scheduleExpressions = $this->loanScheduleAmountExpressions();
        $balanceExpr = $scheduleExpressions['balance'];
        $statusExpr = Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'status')
            ? 'LOWER(COALESCE(s.status, ""))'
            : '""';
        $dueDateColumn = $this->firstLoanReportColumn('loan_payment_schedules', ['due_date', 'payment_date', 'date', 'created_at']);
        $openCondition = '('.$balanceExpr.' > 0 AND '.$statusExpr.' NOT IN ("paid", "confirmed", "completed", "cancelled", "canceled", "void"))';
        $overdueCondition = $dueDateColumn
            ? '('.$openCondition.' AND DATE(s.'.$dueDateColumn.') < CURDATE())'
            : '0';

        $summary = [
            'count' => (int) (clone $summaryQuery)->count(),
            'due_today' => $dueDateColumn ? (int) (clone $summaryQuery)->whereDate('s.'.$dueDateColumn, now()->toDateString())->count() : 0,
            'open' => (int) (clone $summaryQuery)->whereRaw($openCondition)->count(),
            'paid' => (int) (clone $summaryQuery)->whereRaw('('.$balanceExpr.' <= 0 OR '.$statusExpr.' IN ("paid", "confirmed", "completed"))')->count(),
            'overdue' => (int) (clone $summaryQuery)->whereRaw($overdueCondition)->count(),
            'due_total' => (float) (clone $summaryQuery)->sum(DB::raw($scheduleExpressions['due'])),
            'paid_total' => (float) (clone $summaryQuery)->sum(DB::raw($scheduleExpressions['paid'])),
            'balance_total' => (float) (clone $summaryQuery)->sum(DB::raw($balanceExpr)),
        ];

        $rows = $query
            ->orderByRaw($dueDateColumn ? 's.'.$dueDateColumn.' IS NULL, s.'.$dueDateColumn.' ASC' : 's.id ASC')
            ->orderBy('s.id')
            ->paginate($perPage)
            ->appends($request->query());

        return view('loanmanagement::schedules.index', [
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'locations' => $this->loanReportLocationOptions(),
            'statusOptions' => $this->loanScheduleStatusOptions(),
            'loanStatusOptions' => $this->installmentStatusOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
            'perPage' => $perPage,
        ]);
    }

    public function adminLoan(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->yearlySummaryFilters($request);
        $payload = $this->buildYearlyLoanSummary($filters);
        $payload['adminRows'] = $this->adminLoanRows($payload['rows']);
        $payload['adminMonthlyRows'] = $this->adminLoanMonthlyRows($filters);
        $payload['adminTotals'] = $this->adminLoanTotals($payload['adminRows']);

        return view('loanmanagement::admin_loan.index', [
            'filters' => $filters,
            'payload' => $payload,
            'locations' => $this->loanReportLocationOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function adminLoanExport(Request $request)
    {
        $this->allow('loan_management.view');

        $filters = $this->yearlySummaryFilters($request);
        $payload = $this->buildYearlyLoanSummary($filters);
        $rows = $this->adminLoanExportRows($this->adminLoanRows($payload['rows']));
        $filename = 'khnar_yeung_installment_report_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ArrayExport($rows), $filename);
    }

    public function adminLoanDetails(Request $request)
    {
        $this->allow('loan_management.view');

        $year = (int) $request->input('year', now()->format('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->format('Y');
        }

        $filters = $this->yearlySummaryFilters($request);
        $yearStart = \Carbon\Carbon::create($year, 1, 1)->toDateString();
        $yearEnd = \Carbon\Carbon::create($year, 12, 31)->toDateString();
        $filters['start_year'] = $year;
        $filters['end_year'] = $year;
        $filters['date_from'] = max($filters['date_from'], $yearStart);
        $filters['date_to'] = min($filters['date_to'], $yearEnd);
        $month = $request->filled('month') ? (int) $request->input('month') : null;
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }
        $filters['month'] = $month;
        $group = (string) $request->input('group', 'all');

        return view('loanmanagement::admin_loan.details', [
            'year' => $year,
            'group' => $group,
            'filters' => $filters,
            'loans' => $this->adminLoanDetailRows($filters, $group),
            'locations' => $this->loanReportLocationOptions(),
            'isKhmer' => $this->loanReportIsKhmer(),
        ]);
    }

    public function adminLoanInlineUpdate(Request $request, $loan)
    {
        abort_if(! ctype_digit((string) $loan), 404);

        $loan = (int) $loan;

        abort_unless(auth()->user()->can('loan_management.edit'), 403);
        abort_unless(Schema::connection('mysql_loan')->hasTable('loans'), 404);

        $columns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $loanRow = DB::connection('mysql_loan')->table('loans')->where('id', $loan)->first();
        abort_unless($loanRow, 404);

        $data = $request->validate([
            'expected_loan_id' => ['nullable', 'integer', 'min:1'],
            'expected_loan_number' => ['nullable', 'string', 'max:191'],
            'expected_customer_id' => ['nullable', 'integer', 'min:0'],
            'loan_date' => ['nullable', 'date'],
            'source_invoice_no' => ['nullable', 'string', 'max:191'],
            'source_type' => ['nullable', 'string', 'max:30'],
            'customer_id' => ['nullable', 'integer', 'min:0'],
            'customer_name_snapshot' => ['nullable', 'string', 'max:191'],
            'customer_phone_snapshot' => ['nullable', 'string', 'max:50'],
            'customer_address_snapshot' => ['nullable', 'string', 'max:1000'],
            'id_card_number' => ['nullable', 'string', 'max:100'],
            'location_name_snapshot' => ['nullable', 'string', 'max:191'],
            'business_location_name_snapshot' => ['nullable', 'string', 'max:191'],
            'principal_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'balance_amount' => ['nullable', 'numeric', 'min:0'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'installment_count' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'duration_months' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'interest_type' => ['nullable', 'string', 'max:30'],
            'payment_frequency' => ['nullable', 'string', 'max:30'],
            'first_due_date' => ['nullable', 'date'],
            'maturity_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'collector_name_snapshot' => ['nullable', 'string', 'max:191'],
            'collection_status' => ['nullable', 'string', 'max:50'],
            'risk_level' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer', 'min:1'],
            'items.*.product_name_snapshot' => ['nullable', 'string', 'max:191'],
            'items.*.sku_snapshot' => ['nullable', 'string', 'max:191'],
            'items.*.imei_snapshot' => ['nullable', 'string', 'max:191'],
            'items.*.serial_number_snapshot' => ['nullable', 'string', 'max:191'],
            'items.*.brand' => ['nullable', 'string', 'max:191'],
            'items.*.category' => ['nullable', 'string', 'max:191'],
            'items.*.color' => ['nullable', 'string', 'max:191'],
            'items.*.storage' => ['nullable', 'string', 'max:191'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! empty($data['expected_loan_id']) && (int) $data['expected_loan_id'] !== (int) $loan) {
            abort(409, 'Loan form does not match the loan being updated. Please reload and try again.');
        }

        $expectedLoanNumber = trim((string) ($data['expected_loan_number'] ?? ''));
        $currentLoanNumber = trim((string) ($loanRow->loan_number ?? ''));
        if ($expectedLoanNumber !== '' && $currentLoanNumber !== '' && $expectedLoanNumber !== $currentLoanNumber) {
            abort(409, 'Loan number changed or request target is wrong. Please reload and try again.');
        }

        if (isset($data['expected_customer_id'])
            && (int) $data['expected_customer_id'] !== (int) ($loanRow->customer_id ?? 0)) {
            abort(409, 'Loan customer link changed or request target is wrong. Please reload and try again.');
        }

        $submittedCustomerId = (int) ($data['customer_id'] ?? 0);
        $customerChanged = $submittedCustomerId > 0 && $submittedCustomerId !== (int) ($loanRow->customer_id ?? 0);
        $targetCustomerRow = null;
        if ($submittedCustomerId > 0 && Schema::connection('mysql_loan')->hasTable('loan_customers')) {
            $targetCustomerRow = DB::connection('mysql_loan')
                ->table('loan_customers')
                ->where('id', $submittedCustomerId)
                ->first();
            abort_unless($targetCustomerRow, 422, 'Selected loan customer does not exist.');
        }

        if ($customerChanged && $targetCustomerRow) {
            $data['customer_name_snapshot'] = $targetCustomerRow->khmer_name
                ?? $targetCustomerRow->name
                ?? $data['customer_name_snapshot']
                ?? null;
            $data['customer_phone_snapshot'] = $targetCustomerRow->phone
                ?? $targetCustomerRow->login_phone
                ?? $targetCustomerRow->mobile
                ?? $data['customer_phone_snapshot']
                ?? null;
            $data['customer_address_snapshot'] = $targetCustomerRow->address ?? $data['customer_address_snapshot'] ?? null;
            $data['id_card_number'] = $targetCustomerRow->id_card_number
                ?? $targetCustomerRow->national_id
                ?? $data['id_card_number']
                ?? null;
        }

        unset($data['expected_loan_id'], $data['expected_loan_number'], $data['expected_customer_id']);

        $updates = [];
        foreach ($data as $column => $value) {
            if ($column === 'items') {
                continue;
            }
            if (! in_array($column, $columns, true)) {
                continue;
            }
            if (in_array($column, ['principal_amount', 'interest_amount', 'total_amount', 'paid_amount', 'balance_amount', 'down_payment', 'interest_rate'], true)) {
                $value = round((float) $value, 2);
            }
            if ($column === 'currency') {
                $value = strtoupper(trim((string) $value));
            }
            $updates[$column] = $value;
        }
        if (in_array('updated_at', $columns, true)) {
            $updates['updated_at'] = now();
        }

        if (! empty($updates)) {
            DB::connection('mysql_loan')->table('loans')->where('id', $loan)->update($updates);
        }
        $this->updateAdminLoanCustomerSnapshot($loan, $data);
        $this->updateAdminLoanItems($loan, (array) ($data['items'] ?? []));

        return response()->json([
            'success' => true,
            'message' => 'Loan updated successfully.',
            'data' => DB::connection('mysql_loan')->table('loans')->where('id', $loan)->first(),
        ]);
    }

    protected function buildPagePayload(string $page): array
    {
        $conn = DB::connection('mysql_loan');
        $data = ['summary' => [], 'columns' => [], 'rows' => []];

        switch ($page) {
            case 'Guarantors':
                $table = 'loan_guarantors';
                $data['columns'] = ['id', 'name', 'phone', 'relationship', 'workplace', 'customer_id', 'loan_id', 'created_at'];
                break;
            case 'Blacklist':
                $table = 'loan_customers';
                $data['columns'] = ['id', 'customer_code', 'name', 'phone', 'blacklist_status', 'blacklist_reason', 'updated_at'];
                break;
            case 'Installment Schedules':
                $table = 'loan_payment_schedules';
                $data['columns'] = ['id', 'loan_id', 'installment_no', 'due_date', 'amount_due', 'amount_paid', 'amount_balance', 'status'];
                break;
            case 'Monthly Payments':
            case 'Payments':
            case 'Payment History':
                $table = 'loan_payments';
                $data['columns'] = ['id', 'payment_ref_no', 'loan_id', 'customer_id', 'channel', 'amount', 'status', 'paid_at'];
                break;
            case 'Collection Visits':
                $table = 'loan_collection_visits';
                $data['columns'] = ['id', 'loan_id', 'customer_id', 'collector_name_snapshot', 'result', 'status', 'visited_at'];
                break;
            case 'ABA Transactions':
                $table = 'loan_aba_payway_transactions';
                $data['columns'] = ['id', 'merchant_ref_no', 'loan_id', 'customer_id', 'amount', 'currency', 'status', 'created_at'];
                break;
            case 'Reports':
                $table = 'loans';
                $data['columns'] = ['id', 'loan_number', 'customer_name_snapshot', 'status', 'principal_amount', 'paid_amount', 'balance_amount', 'loan_date'];
                break;
            case 'Import Excel':
                $table = 'loan_import_batches';
                $data['columns'] = ['id', 'batch_code', 'file_name', 'status', 'total_rows', 'valid_rows', 'invalid_rows', 'created_at'];
                break;
            default:
                $table = null;
                break;
        }

        if (empty($table) || ! Schema::connection('mysql_loan')->hasTable($table)) {
            $data['summary'] = ['table' => $table, 'total' => 0];
            return $data;
        }

        $available = Schema::connection('mysql_loan')->getColumnListing($table);
        $select = array_values(array_intersect($data['columns'], $available));
        if (empty($select)) {
            $select = ['id'];
        }

        $q = $conn->table($table);
        if ($page === 'Blacklist' && in_array('blacklist_status', $available, true)) {
            $q->where('blacklist_status', 1);
        }
        if ($page === 'Monthly Payments') {
            if (in_array('payment_type', $available, true)) {
                $q->where('payment_type', 'monthly');
            } else {
                if (in_array('schedule_id', $available, true)) {
                    $q->whereNotNull('schedule_id');
                }
                foreach (['receipt_number', 'payment_ref_no', 'reference_number', 'payment_number'] as $column) {
                    if (in_array($column, $available, true)) {
                        $q->where($column, 'not like', 'IMP-DOWN-%');
                    }
                }
            }
        }

        $data['summary'] = ['table' => $table, 'total' => (int) (clone $q)->count()];
        $data['rows'] = $q->select($select)->orderByDesc('id')->limit(100)->get()->map(fn ($r) => (array) $r)->all();
        $data['columns'] = $select;

        return $data;
    }

    protected function collectionVisitsQuery()
    {
        $query = DB::connection('mysql_loan')->table('loan_collection_visits as v');

        if ($this->dashboardHasLoanColumn('id') && $this->dashboardHasVisitColumn('loan_id')) {
            $query->leftJoin('loans as l', 'l.id', '=', 'v.loan_id');
        }

        if (Schema::connection('mysql_loan')->hasTable('loan_customers') && $this->dashboardHasVisitColumn('customer_id')) {
            $query->leftJoin('loan_customers as c', 'c.id', '=', 'v.customer_id');
        }

        if ($this->dashboardHasVisitColumn('deleted_at')) {
            $query->whereNull('v.deleted_at');
        }

        $dateColumn = $this->collectionVisitDateColumn();
        $resultColumn = $this->collectionVisitResultColumn();
        $collectorExpression = $this->collectionVisitCollectorExpression();
        $loanNumberExpression = $this->dashboardHasLoanColumn('loan_number') ? 'l.loan_number' : 'CONCAT("Loan #", v.loan_id)';
        $customerExpression = $this->dashboardHasCustomerColumn('name')
            ? 'c.name'
            : ($this->dashboardHasLoanColumn('customer_name_snapshot') ? 'l.customer_name_snapshot' : 'CONCAT("Customer #", v.customer_id)');
        $phoneExpression = $this->dashboardHasCustomerColumn('phone')
            ? 'c.phone'
            : ($this->dashboardHasLoanColumn('customer_phone_snapshot') ? 'l.customer_phone_snapshot' : 'NULL');

        return $query->selectRaw(
            'v.id, v.loan_id, v.customer_id, '.
            $loanNumberExpression.' as loan_number, '.
            $customerExpression.' as customer_name, '.
            $phoneExpression.' as customer_phone, '.
            $collectorExpression.' as collector_name, '.
            ($resultColumn ? 'v.'.$resultColumn : '"pending"').' as result, '.
            ($this->dashboardHasVisitColumn('address_snapshot') ? 'v.address_snapshot' : 'NULL').' as address_snapshot, '.
            ($this->dashboardHasVisitColumn('latitude') ? 'v.latitude' : 'NULL').' as latitude, '.
            ($this->dashboardHasVisitColumn('longitude') ? 'v.longitude' : 'NULL').' as longitude, '.
            ($this->dashboardHasVisitColumn('note') ? 'v.note' : 'NULL').' as note, '.
            ($dateColumn ? 'v.'.$dateColumn : 'v.created_at').' as visited_at'
        );
    }

    protected function applyCollectionVisitFilters($query, array $filters): void
    {
        $dateColumn = $this->collectionVisitDateColumn();
        $resultColumn = $this->collectionVisitResultColumn();

        if ($dateColumn && ! empty($filters['date_from'])) {
            $query->whereDate('v.'.$dateColumn, '>=', $filters['date_from']);
        }

        if ($dateColumn && ! empty($filters['date_to'])) {
            $query->whereDate('v.'.$dateColumn, '<=', $filters['date_to']);
        }

        if ($resultColumn && ! empty($filters['result'])) {
            $query->where('v.'.$resultColumn, $filters['result']);
        }

        if (! empty($filters['collector'])) {
            $collector = '%'.$filters['collector'].'%';
            $query->whereRaw($this->collectionVisitCollectorExpression().' LIKE ?', [$collector]);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $hasCondition = false;
                $addSearch = function (string $column) use ($q, $search, &$hasCondition) {
                    $hasCondition ? $q->orWhere($column, 'like', $search) : $q->where($column, 'like', $search);
                    $hasCondition = true;
                };

                if ($this->dashboardHasVisitColumn('loan_id')) {
                    $addSearch('v.loan_id');
                }
                if ($this->dashboardHasVisitColumn('customer_id')) {
                    $addSearch('v.customer_id');
                }
                if ($this->dashboardHasLoanColumn('loan_number')) {
                    $addSearch('l.loan_number');
                }
                if ($this->dashboardHasLoanColumn('customer_name_snapshot')) {
                    $addSearch('l.customer_name_snapshot');
                }
                if ($this->dashboardHasLoanColumn('customer_phone_snapshot')) {
                    $addSearch('l.customer_phone_snapshot');
                }
                if ($this->dashboardHasCustomerColumn('name')) {
                    $addSearch('c.name');
                }
                if ($this->dashboardHasCustomerColumn('phone')) {
                    $addSearch('c.phone');
                }
                if ($this->dashboardHasVisitColumn('address_snapshot')) {
                    $addSearch('v.address_snapshot');
                }
                if ($this->dashboardHasVisitColumn('note')) {
                    $addSearch('v.note');
                }
            });
        }
    }

    protected function collectionVisitCollectors(): array
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_collection_visits')) {
            return [];
        }

        return DB::connection('mysql_loan')
            ->table('loan_collection_visits as v')
            ->selectRaw($this->collectionVisitCollectorExpression().' as collector_name')
            ->whereRaw($this->collectionVisitCollectorExpression().' IS NOT NULL')
            ->distinct()
            ->orderBy('collector_name')
            ->pluck('collector_name', 'collector_name')
            ->filter()
            ->all();
    }

    protected function collectionVisitResults(): array
    {
        $column = $this->collectionVisitResultColumn();
        if (! $column) {
            return [];
        }

        return DB::connection('mysql_loan')
            ->table('loan_collection_visits')
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->mapWithKeys(fn ($value) => [$value => ucwords(str_replace('_', ' ', (string) $value))])
            ->all();
    }

    protected function collectionVisitDateColumn(): ?string
    {
        if ($this->dashboardHasVisitColumn('visited_at')) {
            return 'visited_at';
        }

        return $this->dashboardHasVisitColumn('created_at') ? 'created_at' : null;
    }

    protected function collectionVisitResultColumn(): ?string
    {
        if ($this->dashboardHasVisitColumn('status')) {
            return 'status';
        }

        return $this->dashboardHasVisitColumn('result') ? 'result' : null;
    }

    protected function collectionVisitCollectorExpression(): string
    {
        if ($this->dashboardHasVisitColumn('collector_name_snapshot')) {
            return 'v.collector_name_snapshot';
        }

        if ($this->dashboardHasVisitColumn('staff_name_snapshot')) {
            return 'v.staff_name_snapshot';
        }

        if ($this->dashboardHasVisitColumn('collector_id')) {
            return 'CONCAT("Collector #", v.collector_id)';
        }

        if ($this->dashboardHasVisitColumn('staff_id')) {
            return 'CONCAT("Staff #", v.staff_id)';
        }

        return '"Unassigned"';
    }

    protected function dashboardHasVisitColumn(string $column): bool
    {
        return Schema::connection('mysql_loan')->hasColumn('loan_collection_visits', $column);
    }

    protected function dashboardHasLoanColumn(string $column): bool
    {
        return Schema::connection('mysql_loan')->hasTable('loans')
            && Schema::connection('mysql_loan')->hasColumn('loans', $column);
    }

    protected function dashboardHasCustomerColumn(string $column): bool
    {
        return Schema::connection('mysql_loan')->hasTable('loan_customers')
            && Schema::connection('mysql_loan')->hasColumn('loan_customers', $column);
    }

    protected function yearlySummaryFilters(Request $request): array
    {
        $currentYear = (int) now()->format('Y');
        $businessStartDate = BusinessSettingsService::get()['start_date'] ?? null;
        $defaultDateFrom = $businessStartDate ?: ($currentYear - 4).'-01-01';
        $dateFrom = $request->input('date_from', $request->filled('start_year') ? ((int) $request->input('start_year')).'-01-01' : $defaultDateFrom);
        $dateTo = $request->input('date_to', $request->filled('end_year') ? ((int) $request->input('end_year')).'-12-31' : $currentYear.'-12-31');
        $dateRange = trim((string) $request->input('date_range', ''));
        if ($dateRange !== '' && (! $request->filled('date_from') || ! $request->filled('date_to')) && ($parsedRange = $this->parseSummaryDateRange($dateRange))) {
            [$rangeFrom, $rangeTo] = $parsedRange;
            $dateFrom = $rangeFrom;
            $dateTo = $rangeTo;
        }

        try {
            $dateFrom = \Carbon\Carbon::parse($dateFrom)->toDateString();
        } catch (\Throwable $e) {
            $dateFrom = $defaultDateFrom;
        }

        try {
            $dateTo = \Carbon\Carbon::parse($dateTo)->toDateString();
        } catch (\Throwable $e) {
            $dateTo = $currentYear.'-12-31';
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $startYear = (int) \Carbon\Carbon::parse($dateFrom)->format('Y');
        $endYear = (int) \Carbon\Carbon::parse($dateTo)->format('Y');
        if ($startYear < 2000 || $startYear > 2100 || $endYear < 2000 || $endYear > 2100) {
            $startYear = $currentYear - 4;
            $endYear = $currentYear;
            $dateFrom = $startYear.'-01-01';
            $dateTo = $endYear.'-12-31';
        }
        if (($endYear - $startYear) > 25) {
            $endYear = $startYear + 25;
            $dateTo = $endYear.'-12-31';
        }

        return [
            'start_year' => $startYear,
            'end_year' => $endYear,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'location_id' => $request->filled('location_id') ? trim((string) $request->input('location_id')) : null,
            'search' => trim((string) $request->input('search', '')),
        ];
    }

    protected function dailySummaryFilters(Request $request): array
    {
        $filters = $this->dashboardReportFilters($request);
        $filters['period'] = 'daily';

        try {
            $from = \Carbon\Carbon::parse($filters['date_from']);
            $to = \Carbon\Carbon::parse($filters['date_to']);
            if ($from->diffInDays($to) > 369) {
                $filters['date_to'] = $from->copy()->addDays(369)->toDateString();
            }
        } catch (\Throwable $e) {
            $filters['date_from'] = now()->toDateString();
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }

    protected function monthlySummaryFilters(Request $request): array
    {
        if (
            ! $request->filled('date_from')
            && ! $request->filled('date_to')
            && ! $request->filled('date_range')
            && ! $request->filled('start_year')
            && ! $request->filled('end_year')
        ) {
            $request->merge([
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]);
        }

        $filters = $this->yearlySummaryFilters($request);
        $filters['period'] = 'monthly';

        return $filters;
    }

    protected function dashboardReportFilters(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $dateRange = trim((string) $request->input('date_range', ''));
        if ($dateRange !== '' && (! $request->filled('date_from') || ! $request->filled('date_to')) && ($parsedRange = $this->parseSummaryDateRange($dateRange))) {
            [$rangeFrom, $rangeTo] = $parsedRange;
            $dateFrom = $rangeFrom;
            $dateTo = $rangeTo;
        }

        $period = strtolower((string) $request->input('period', 'daily'));
        if (! in_array($period, ['daily', 'monthly', 'yearly'], true)) {
            $period = 'daily';
        }

        try {
            $dateFrom = \Carbon\Carbon::parse($dateFrom)->toDateString();
        } catch (\Throwable $e) {
            $dateFrom = now()->toDateString();
        }

        try {
            $dateTo = \Carbon\Carbon::parse($dateTo)->toDateString();
        } catch (\Throwable $e) {
            $dateTo = now()->toDateString();
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period' => $period,
            'location_id' => $request->filled('location_id') ? trim((string) $request->input('location_id')) : null,
            'search' => trim((string) $request->input('search', '')),
        ];
    }

    protected function parseSummaryDateRange(string $dateRange): ?array
    {
        $normalized = trim(str_replace(['–', '—', ' to '], ['-', '-', ' - '], $dateRange));

        if ($normalized === '') {
            return null;
        }

        foreach ([' ~ ', '~', ' - '] as $separator) {
            if (! str_contains($normalized, $separator)) {
                continue;
            }

            [$from, $to] = array_map('trim', explode($separator, $normalized, 2));

            return $from !== '' && $to !== '' ? [$from, $to] : null;
        }

        return null;
    }

    protected function paymentSummaryFilters(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        try {
            $dateFrom = \Carbon\Carbon::parse($dateFrom)->toDateString();
        } catch (\Throwable $e) {
            $dateFrom = now()->toDateString();
        }

        try {
            $dateTo = \Carbon\Carbon::parse($dateTo)->toDateString();
        } catch (\Throwable $e) {
            $dateTo = now()->toDateString();
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period' => 'daily',
            'location_id' => $request->filled('location_id') ? trim((string) $request->input('location_id')) : null,
            'search' => trim((string) $request->input('search', '')),
        ];
    }

    protected function installmentReportFilters(Request $request): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $dateRange = trim((string) $request->input('date_range', ''));

        if ($dateRange !== '' && (! $request->filled('date_from') || ! $request->filled('date_to')) && ($parsedRange = $this->parseSummaryDateRange($dateRange))) {
            [$dateFrom, $dateTo] = $parsedRange;
        }

        try {
            $dateFrom = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->toDateString() : '';
        } catch (\Throwable $e) {
            $dateFrom = '';
        }

        try {
            $dateTo = $dateTo ? \Carbon\Carbon::parse($dateTo)->toDateString() : '';
        } catch (\Throwable $e) {
            $dateTo = '';
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'search' => trim((string) $request->input('search', '')),
            'location_id' => trim((string) $request->input('location_id', '')),
            'status' => trim((string) $request->input('status', '')),
            'payment_status' => trim((string) $request->input('payment_status', '')),
            'collector' => trim((string) $request->input('collector', '')),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    protected function installmentReportQuery()
    {
        $columns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'sale_date', 'created_at'], $columns) ?: 'created_at';
        $amountExpressions = $this->installmentReportAmountExpressions();
        $principalExpr = $amountExpressions['principal'];
        $paidExpr = $amountExpressions['paid'];
        $balanceExpr = $amountExpressions['balance'];
        $totalExpr = $amountExpressions['total'];
        $scheduleWhere = $this->installmentScheduleBaseWhere();
        $paymentWhere = $this->installmentPaymentBaseWhere();
        $scheduleOpenCondition = $this->installmentScheduleOpenCondition('s');

        return DB::connection('mysql_loan')->table('loans as l')
            ->selectRaw(
                'l.id, '.
                (in_array('loan_number', $columns, true) ? 'l.loan_number' : 'CONCAT("Loan #", l.id)').' as loan_number, '.
                'l.'.$dateColumn.' as loan_date, '.
                (in_array('source_invoice_no', $columns, true) ? 'l.source_invoice_no' : (in_array('invoice_number_snapshot', $columns, true) ? 'l.invoice_number_snapshot' : 'NULL')).' as invoice_no, '.
                (in_array('customer_name_snapshot', $columns, true) ? 'l.customer_name_snapshot' : 'NULL').' as customer_name, '.
                (in_array('customer_phone_snapshot', $columns, true) ? 'l.customer_phone_snapshot' : 'NULL').' as customer_phone, '.
                (in_array('location_name_snapshot', $columns, true) ? 'l.location_name_snapshot' : (in_array('business_location_name_snapshot', $columns, true) ? 'l.business_location_name_snapshot' : 'NULL')).' as location_name, '.
                (in_array('collector_name_snapshot', $columns, true) ? 'l.collector_name_snapshot' : 'NULL').' as collector_name, '.
                (in_array('status', $columns, true) ? 'l.status' : '"pending"').' as status, '.
                (in_array('payment_status', $columns, true) ? 'l.payment_status' : 'NULL').' as payment_status, '.
                $principalExpr.' as principal_amount, '.
                $paidExpr.' as paid_amount, '.
                $balanceExpr.' as balance_amount, '.
                $totalExpr.' as total_amount, '.
                (in_array('currency', $columns, true) ? 'l.currency' : '"USD"').' as currency, '.
                (in_array('duration_months', $columns, true) ? 'l.duration_months' : (in_array('installment_count', $columns, true) ? 'l.installment_count' : '0')).' as term_count, '.
                (in_array('payment_frequency', $columns, true) ? 'l.payment_frequency' : 'NULL').' as payment_frequency, '.
                (in_array('note', $columns, true) ? 'l.note' : 'NULL').' as note, '.
                $this->installmentScheduleSubquery('COUNT(*)', $scheduleWhere).' as schedule_count, '.
                $this->installmentScheduleSubquery($this->installmentPaidScheduleCountExpression(), $scheduleWhere).' as paid_schedule_count, '.
                $this->installmentNextDueSubquery($scheduleWhere).' as next_due_date, '.
                $this->installmentScheduleExists('DATE(s.due_date) < CURDATE() AND '.$scheduleOpenCondition, $scheduleWhere).' as is_overdue, '.
                $this->installmentPaymentSubquery('MAX('.$this->installmentPaymentDateExpression().')', $paymentWhere).' as last_payment_at'
            )
            ->when(in_array('deleted_at', $columns, true), fn ($query) => $query->whereNull('l.deleted_at'));
    }

    protected function installmentReportAmountExpressions(): array
    {
        return [
            'principal' => $this->coalesceSql('loans', 'l', ['principal_amount', 'financed_amount'], '0'),
            'paid' => $this->coalesceSql('loans', 'l', ['paid_amount', 'total_paid', 'down_payment'], '0'),
            'balance' => $this->coalesceSql('loans', 'l', ['balance_amount', 'amount_balance'], '0'),
            'total' => $this->coalesceSql('loans', 'l', ['total_amount', 'total_payable_amount', 'principal_amount'], '0'),
        ];
    }

    protected function applyInstallmentReportFilters($query, array $filters): void
    {
        $columns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'sale_date', 'created_at'], $columns) ?: 'created_at';

        if (! empty($filters['date_from'])) {
            $query->whereDate('l.'.$dateColumn, '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('l.'.$dateColumn, '<=', $filters['date_to']);
        }
        if (! empty($filters['status']) && in_array('status', $columns, true)) {
            $query->where('l.status', $filters['status']);
        }
        if (! empty($filters['payment_status'])) {
            $this->applyInstallmentPaymentStatusFilter($query, $filters['payment_status'], $columns);
        }
        if (! empty($filters['collector'])) {
            foreach (['collector_name_snapshot', 'assigned_collector_id'] as $column) {
                if (in_array($column, $columns, true)) {
                    $query->where('l.'.$column, 'like', '%'.$filters['collector'].'%');
                    break;
                }
            }
        }
        if (! empty($filters['location_id'])) {
            $locationFilter = $this->parseYearlyLocationFilter((string) $filters['location_id']);
            if (! empty($locationFilter)) {
                $query->where(function ($where) use ($locationFilter, $columns) {
                    if (! empty($locationFilter['loan_location_id']) && in_array('business_location_id', $columns, true)) {
                        $where->orWhere('l.business_location_id', (int) $locationFilter['loan_location_id']);
                    }
                    if (! empty($locationFilter['main_location_id']) && in_array('main_location_id', $columns, true)) {
                        $where->orWhere('l.main_location_id', (int) $locationFilter['main_location_id']);
                    }
                    if (! empty($locationFilter['legacy_id'])) {
                        if (in_array('business_location_id', $columns, true)) {
                            $where->orWhere('l.business_location_id', (int) $locationFilter['legacy_id']);
                        }
                        if (in_array('main_location_id', $columns, true)) {
                            $where->orWhere('l.main_location_id', (int) $locationFilter['legacy_id']);
                        }
                    }
                    if (! empty($locationFilter['name'])) {
                        foreach (['location_name_snapshot', 'business_location_name_snapshot'] as $column) {
                            if (in_array($column, $columns, true)) {
                                $where->orWhere('l.'.$column, $locationFilter['name']);
                            }
                        }
                    }
                });
            }
        }
        if (($filters['search'] ?? '') !== '') {
            $like = '%'.$filters['search'].'%';
            $searchColumns = array_values(array_filter(['loan_number', 'source_invoice_no', 'invoice_number_snapshot', 'customer_name_snapshot', 'customer_phone_snapshot', 'collector_name_snapshot', 'note'], fn ($column) => in_array($column, $columns, true)));
            if (! empty($searchColumns)) {
                $query->where(function ($where) use ($searchColumns, $like) {
                    foreach ($searchColumns as $column) {
                        $where->orWhere('l.'.$column, 'like', $like);
                    }
                });
            }
        }
    }

    protected function applyInstallmentPaymentStatusFilter($query, string $status, array $columns): void
    {
        if (in_array('payment_status', $columns, true)) {
            $query->where('l.payment_status', $status);
            return;
        }

        $paidExpr = $this->coalesceSql('loans', 'l', ['paid_amount', 'total_paid', 'down_payment'], '0');
        $balanceExpr = $this->coalesceSql('loans', 'l', ['balance_amount', 'amount_balance'], '0');

        match ($status) {
            'paid', 'completed' => $query->whereRaw('('.$balanceExpr.') <= 0'),
            'partial' => $query->whereRaw('('.$paidExpr.') > 0 AND ('.$balanceExpr.') > 0'),
            'unpaid' => $query->whereRaw('('.$paidExpr.') <= 0 AND ('.$balanceExpr.') > 0'),
            'overdue' => $query->whereRaw($this->installmentScheduleExists('DATE(s.due_date) < CURDATE() AND '.$this->installmentScheduleOpenCondition('s'), $this->installmentScheduleBaseWhere()).' = 1'),
            default => null,
        };
    }

    protected function installmentStatusOptions(): array
    {
        if (! Schema::connection('mysql_loan')->hasTable('loans') || ! Schema::connection('mysql_loan')->hasColumn('loans', 'status')) {
            return [];
        }

        return DB::connection('mysql_loan')->table('loans')
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status', 'status')
            ->mapWithKeys(fn ($value) => [$value => ucwords(str_replace('_', ' ', (string) $value))])
            ->all();
    }

    protected function installmentPaymentStatusOptions(): array
    {
        return ['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid', 'completed' => 'Completed', 'overdue' => 'Overdue'];
    }

    protected function installmentScheduleBaseWhere(): string
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payment_schedules') || ! Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'loan_id')) {
            return '1 = 0';
        }

        return Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'deleted_at') ? 's.deleted_at IS NULL' : '1 = 1';
    }

    protected function installmentPaymentBaseWhere(): string
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payments') || ! Schema::connection('mysql_loan')->hasColumn('loan_payments', 'loan_id')) {
            return '1 = 0';
        }

        return Schema::connection('mysql_loan')->hasColumn('loan_payments', 'deleted_at') ? 'p.deleted_at IS NULL' : '1 = 1';
    }

    protected function installmentScheduleSubquery(string $select, string $where): string
    {
        if ($where === '1 = 0') {
            return '0';
        }

        return '(SELECT COALESCE('.$select.', 0) FROM loan_payment_schedules s WHERE s.loan_id = l.id AND '.$where.')';
    }

    protected function installmentNextDueSubquery(string $where): string
    {
        if ($where === '1 = 0' || ! Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'due_date')) {
            return 'NULL';
        }

        return '(SELECT MIN(CASE WHEN DATE(s.due_date) >= CURDATE() AND '.$this->installmentScheduleOpenCondition('s').' THEN s.due_date ELSE NULL END) FROM loan_payment_schedules s WHERE s.loan_id = l.id AND '.$where.')';
    }

    protected function installmentScheduleExists(string $condition, string $where): string
    {
        if ($where === '1 = 0' || ! Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'due_date')) {
            return '0';
        }

        return 'EXISTS(SELECT 1 FROM loan_payment_schedules s WHERE s.loan_id = l.id AND '.$where.' AND '.$condition.')';
    }

    protected function installmentPaymentSubquery(string $select, string $where): string
    {
        if ($where === '1 = 0') {
            return 'NULL';
        }

        return '(SELECT '.$select.' FROM loan_payments p WHERE p.loan_id = l.id AND '.$where.')';
    }

    protected function installmentPaidScheduleCountExpression(): string
    {
        if (Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'status')) {
            return 'SUM(CASE WHEN COALESCE(s.status, "") IN ("paid", "confirmed", "completed") THEN 1 ELSE 0 END)';
        }

        if (Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'paid_amount')) {
            return 'SUM(CASE WHEN COALESCE(s.paid_amount, 0) > 0 THEN 1 ELSE 0 END)';
        }

        return '0';
    }

    protected function installmentScheduleOpenCondition(string $alias): string
    {
        if (Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'status')) {
            return 'COALESCE('.$alias.'.status, "") NOT IN ("paid", "confirmed", "completed", "cancelled")';
        }

        return '1 = 1';
    }

    protected function installmentPaymentDateExpression(): string
    {
        foreach (['paid_date', 'paid_at', 'created_at'] as $column) {
            if (Schema::connection('mysql_loan')->hasColumn('loan_payments', $column)) {
                return 'p.'.$column;
            }
        }

        return 'NULL';
    }

    protected function loanScheduleFilters(Request $request): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $dateRange = trim((string) $request->input('date_range', ''));

        if ($dateRange !== '' && (! $request->filled('date_from') || ! $request->filled('date_to')) && ($parsedRange = $this->parseSummaryDateRange($dateRange))) {
            [$dateFrom, $dateTo] = $parsedRange;
        }

        try {
            $dateFrom = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->toDateString() : '';
        } catch (\Throwable $e) {
            $dateFrom = '';
        }

        try {
            $dateTo = $dateTo ? \Carbon\Carbon::parse($dateTo)->toDateString() : '';
        } catch (\Throwable $e) {
            $dateTo = '';
        }

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'search' => trim((string) $request->input('search', '')),
            'location_id' => trim((string) $request->input('location_id', '')),
            'status' => trim((string) $request->input('status', '')),
            'loan_status' => trim((string) $request->input('loan_status', '')),
            'collector' => trim((string) $request->input('collector', '')),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    protected function loanScheduleQuery()
    {
        $scheduleColumns = Schema::connection('mysql_loan')->getColumnListing('loan_payment_schedules');
        $loanColumns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dueDateColumn = $this->firstLoanReportColumn('loan_payment_schedules', ['due_date', 'payment_date', 'date', 'created_at'], $scheduleColumns);
        $paidAtColumn = $this->firstLoanReportColumn('loan_payment_schedules', ['paid_at', 'paid_date', 'payment_date', 'updated_at'], $scheduleColumns);
        $amountExpressions = $this->loanScheduleAmountExpressions();
        $balanceExpr = $amountExpressions['balance'];
        $statusExpr = in_array('status', $scheduleColumns, true) ? 'LOWER(COALESCE(s.status, ""))' : '""';
        $loanNumberExpr = in_array('loan_number', $loanColumns, true) ? 'l.loan_number' : 'CONCAT("LN-", l.id)';
        $invoiceExpr = in_array('source_invoice_no', $loanColumns, true)
            ? 'l.source_invoice_no'
            : (in_array('invoice_number_snapshot', $loanColumns, true) ? 'l.invoice_number_snapshot' : 'NULL');
        $locationExpr = in_array('location_name_snapshot', $loanColumns, true)
            ? 'l.location_name_snapshot'
            : (in_array('business_location_name_snapshot', $loanColumns, true) ? 'l.business_location_name_snapshot' : 'NULL');
        $dueDateExpr = $dueDateColumn ? 's.'.$dueDateColumn : 'NULL';
        $dpdExpr = $dueDateColumn
            ? 'CASE WHEN '.$balanceExpr.' > 0 AND DATE(s.'.$dueDateColumn.') < CURDATE() AND '.$statusExpr.' NOT IN ("paid", "confirmed", "completed", "cancelled", "canceled", "void") THEN DATEDIFF(CURDATE(), DATE(s.'.$dueDateColumn.')) ELSE 0 END'
            : '0';

        return DB::connection('mysql_loan')->table('loan_payment_schedules as s')
            ->join('loans as l', 'l.id', '=', 's.loan_id')
            ->selectRaw(
                's.id, s.loan_id, '.
                (in_array('installment_no', $scheduleColumns, true) ? 's.installment_no' : 's.id').' as installment_no, '.
                $dueDateExpr.' as due_date, '.
                ($paidAtColumn ? 's.'.$paidAtColumn : 'NULL').' as paid_at, '.
                (in_array('status', $scheduleColumns, true) ? 's.status' : 'NULL').' as schedule_status, '.
                $amountExpressions['principal'].' as principal_amount, '.
                $amountExpressions['interest'].' as interest_amount, '.
                $amountExpressions['due'].' as amount_due, '.
                $amountExpressions['paid'].' as paid_amount, '.
                $balanceExpr.' as balance_amount, '.
                $dpdExpr.' as overdue_days, '.
                $loanNumberExpr.' as loan_number, '.
                $invoiceExpr.' as invoice_no, '.
                (in_array('customer_name_snapshot', $loanColumns, true) ? 'l.customer_name_snapshot' : 'NULL').' as customer_name, '.
                (in_array('customer_phone_snapshot', $loanColumns, true) ? 'l.customer_phone_snapshot' : 'NULL').' as customer_phone, '.
                $locationExpr.' as location_name, '.
                (in_array('collector_name_snapshot', $loanColumns, true) ? 'l.collector_name_snapshot' : 'NULL').' as collector_name, '.
                (in_array('status', $loanColumns, true) ? 'l.status' : 'NULL').' as loan_status, '.
                (in_array('payment_frequency', $loanColumns, true) ? 'l.payment_frequency' : 'NULL').' as payment_frequency, '.
                (in_array('currency', $loanColumns, true) ? 'l.currency' : '"USD"').' as currency'
            )
            ->when(in_array('deleted_at', $scheduleColumns, true), fn ($query) => $query->whereNull('s.deleted_at'))
            ->when(in_array('deleted_at', $loanColumns, true), fn ($query) => $query->whereNull('l.deleted_at'));
    }

    protected function loanScheduleAmountExpressions(): array
    {
        return [
            'principal' => $this->coalesceSql('loan_payment_schedules', 's', ['principal_amount', 'principal_due', 'principal', 'installment_value'], '0'),
            'interest' => $this->coalesceSql('loan_payment_schedules', 's', ['interest_amount', 'interest_due', 'interest', 'benefit_value'], '0'),
            'due' => $this->coalesceSql('loan_payment_schedules', 's', ['schedule_amount', 'amount_due', 'total'], '0'),
            'paid' => $this->coalesceSql('loan_payment_schedules', 's', ['paid_amount', 'amount_paid', 'paid_value'], '0'),
            'balance' => $this->coalesceSql('loan_payment_schedules', 's', ['balance_amount', 'amount_balance'], '0'),
        ];
    }

    protected function applyLoanScheduleFilters($query, array $filters): void
    {
        $scheduleColumns = Schema::connection('mysql_loan')->getColumnListing('loan_payment_schedules');
        $loanColumns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dueDateColumn = $this->firstLoanReportColumn('loan_payment_schedules', ['due_date', 'payment_date', 'date', 'created_at'], $scheduleColumns);
        $amountExpressions = $this->loanScheduleAmountExpressions();
        $balanceExpr = $amountExpressions['balance'];
        $paidExpr = $amountExpressions['paid'];

        if ($dueDateColumn && ! empty($filters['date_from'])) {
            $query->whereDate('s.'.$dueDateColumn, '>=', $filters['date_from']);
        }
        if ($dueDateColumn && ! empty($filters['date_to'])) {
            $query->whereDate('s.'.$dueDateColumn, '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $status = strtolower((string) $filters['status']);
            if (in_array('status', $scheduleColumns, true)) {
                $query->whereRaw('LOWER(COALESCE(s.status, "")) = ?', [$status]);
            } elseif (in_array($status, ['paid', 'confirmed', 'completed'], true)) {
                $query->whereRaw('('.$balanceExpr.') <= 0');
            } elseif ($status === 'partial') {
                $query->whereRaw('('.$paidExpr.') > 0 AND ('.$balanceExpr.') > 0');
            } elseif (in_array($status, ['unpaid', 'pending', 'late', 'overdue'], true)) {
                $query->whereRaw('('.$balanceExpr.') > 0');
            }
        }
        if (! empty($filters['loan_status']) && in_array('status', $loanColumns, true)) {
            $query->where('l.status', $filters['loan_status']);
        }
        if (! empty($filters['collector'])) {
            foreach (['collector_name_snapshot', 'assigned_collector_id'] as $column) {
                if (in_array($column, $loanColumns, true)) {
                    $query->where('l.'.$column, 'like', '%'.$filters['collector'].'%');
                    break;
                }
            }
        }
        if (! empty($filters['location_id'])) {
            $locationFilter = $this->parseYearlyLocationFilter((string) $filters['location_id']);
            if (! empty($locationFilter)) {
                $query->where(function ($where) use ($locationFilter, $loanColumns) {
                    if (! empty($locationFilter['loan_location_id']) && in_array('business_location_id', $loanColumns, true)) {
                        $where->orWhere('l.business_location_id', (int) $locationFilter['loan_location_id']);
                    }
                    if (! empty($locationFilter['main_location_id']) && in_array('main_location_id', $loanColumns, true)) {
                        $where->orWhere('l.main_location_id', (int) $locationFilter['main_location_id']);
                    }
                    if (! empty($locationFilter['legacy_id'])) {
                        if (in_array('business_location_id', $loanColumns, true)) {
                            $where->orWhere('l.business_location_id', (int) $locationFilter['legacy_id']);
                        }
                        if (in_array('main_location_id', $loanColumns, true)) {
                            $where->orWhere('l.main_location_id', (int) $locationFilter['legacy_id']);
                        }
                    }
                    if (! empty($locationFilter['name'])) {
                        foreach (['location_name_snapshot', 'business_location_name_snapshot'] as $column) {
                            if (in_array($column, $loanColumns, true)) {
                                $where->orWhere('l.'.$column, $locationFilter['name']);
                            }
                        }
                    }
                });
            }
        }
        if (($filters['search'] ?? '') !== '') {
            $like = '%'.$filters['search'].'%';
            $query->where(function ($where) use ($scheduleColumns, $loanColumns, $like) {
                foreach (['id', 'installment_no'] as $column) {
                    if (in_array($column, $scheduleColumns, true)) {
                        $where->orWhere('s.'.$column, 'like', $like);
                    }
                }
                foreach (['loan_number', 'source_invoice_no', 'invoice_number_snapshot', 'customer_name_snapshot', 'customer_phone_snapshot', 'collector_name_snapshot'] as $column) {
                    if (in_array($column, $loanColumns, true)) {
                        $where->orWhere('l.'.$column, 'like', $like);
                    }
                }
            });
        }
    }

    protected function loanScheduleStatusOptions(): array
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payment_schedules') || ! Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'status')) {
            return ['pending' => 'Pending', 'partial' => 'Partial', 'paid' => 'Paid', 'overdue' => 'Overdue'];
        }

        $options = DB::connection('mysql_loan')->table('loan_payment_schedules')
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status', 'status')
            ->mapWithKeys(fn ($value) => [$value => ucwords(str_replace('_', ' ', (string) $value))])
            ->all();

        return $options ?: ['pending' => 'Pending', 'partial' => 'Partial', 'paid' => 'Paid', 'overdue' => 'Overdue'];
    }

    protected function recentActivityFilters(Request $request): array
    {
        $dateFrom = $request->input('recent_date_from', now()->toDateString());
        $dateTo = $request->input('recent_date_to', now()->toDateString());
        $dateRange = trim((string) $request->input('recent_date_range', ''));
        if ($dateRange !== '' && ($parsedRange = $this->parseSummaryDateRange($dateRange))) {
            [$rangeFrom, $rangeTo] = $parsedRange;
            $dateFrom = $rangeFrom;
            $dateTo = $rangeTo;
        }

        try {
            $dateFrom = \Carbon\Carbon::parse($dateFrom)->toDateString();
        } catch (\Throwable $e) {
            $dateFrom = now()->toDateString();
        }

        try {
            $dateTo = \Carbon\Carbon::parse($dateTo)->toDateString();
        } catch (\Throwable $e) {
            $dateTo = now()->toDateString();
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period' => 'daily',
            'location_id' => $request->filled('recent_location_id') ? trim((string) $request->input('recent_location_id')) : null,
            'search' => trim((string) $request->input('recent_search', '')),
        ];
    }

    protected function buildDashboardReports(array $filters, ?array $recentActivityFilters = null): array
    {
        $recentActivityFilters = $recentActivityFilters ?: $filters;
        $recentLoans = $this->dashboardRecentLoans($recentActivityFilters);
        $recentPayments = $this->dashboardRecentPayments($recentActivityFilters);

        return [
            'paymentMethodRows' => $this->dashboardPaymentMethodRowsFromActivity($recentPayments, $recentLoans),
            'recentLoans' => $recentLoans,
            'recentPayments' => $recentPayments,
        ];
    }

    protected function dashboardLoanCards(array $filters): array
    {
        $empty = [
            'loan_count' => 0,
            'principal_total' => 0.0,
            'loan_total' => 0.0,
            'paid_total' => 0.0,
            'balance_total' => 0.0,
        ];

        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return $empty;
        }

        $loanDateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'sale_date', 'created_at']);
        if (! $loanDateColumn) {
            return $empty;
        }

        $query = DB::connection('mysql_loan')->table('loans as l');
        $this->applyDashboardLoanFilters($query, $filters, 'l', $loanDateColumn);

        $row = $query
            ->selectRaw('COUNT(*) as loan_count')
            ->selectRaw($this->sumSql('loans', 'l', ['principal_amount', 'financed_amount']).' as principal_total')
            ->selectRaw($this->sumSql('loans', 'l', ['total_amount', 'total_payable_amount', 'principal_amount']).' as loan_total')
            ->selectRaw($this->sumSql('loans', 'l', ['paid_amount', 'amount_paid']).' as paid_total')
            ->selectRaw($this->sumSql('loans', 'l', ['balance_amount', 'amount_balance']).' as balance_total')
            ->first();

        return [
            'loan_count' => (int) ($row->loan_count ?? 0),
            'principal_total' => (float) ($row->principal_total ?? 0),
            'loan_total' => (float) ($row->loan_total ?? 0),
            'paid_total' => (float) ($row->paid_total ?? 0),
            'balance_total' => (float) ($row->balance_total ?? 0),
        ];
    }

    protected function dashboardPaymentCards(array $filters): array
    {
        $empty = [
            'payment_count' => 0,
            'collection_count' => 0,
            'deposit_count' => 0,
            'payment_total' => 0.0,
            'collection_total' => 0.0,
            'deposit_total' => 0.0,
        ];

        $query = $this->dashboardPaymentBaseQuery($filters);
        if (! $query) {
            return $empty;
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $typeExpr = $this->dashboardPaymentTypeExpression('p');
        $collectionCase = 'CASE WHEN ('.$typeExpr.') = "monthly" THEN '.$amountExpr.' ELSE 0 END';
        $depositCase = 'CASE WHEN ('.$typeExpr.') = "loan" THEN '.$amountExpr.' ELSE 0 END';

        $row = $query
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('SUM(CASE WHEN ('.$typeExpr.') = "monthly" THEN 1 ELSE 0 END) as collection_count')
            ->selectRaw('SUM(CASE WHEN ('.$typeExpr.') = "loan" THEN 1 ELSE 0 END) as deposit_count')
            ->selectRaw('COALESCE(SUM('.$amountExpr.'), 0) as payment_total')
            ->selectRaw('COALESCE(SUM('.$collectionCase.'), 0) as collection_total')
            ->selectRaw('COALESCE(SUM('.$depositCase.'), 0) as deposit_total')
            ->first();

        return [
            'payment_count' => (int) ($row->payment_count ?? 0),
            'collection_count' => (int) ($row->collection_count ?? 0),
            'deposit_count' => (int) ($row->deposit_count ?? 0),
            'payment_total' => (float) ($row->payment_total ?? 0),
            'collection_total' => (float) ($row->collection_total ?? 0),
            'deposit_total' => (float) ($row->deposit_total ?? 0),
        ];
    }

    protected function dashboardPaymentCardsFromActivity($recentPayments, $recentLoans): array
    {
        $collectionCount = 0;
        $collectionTotal = 0.0;
        foreach ($recentPayments as $payment) {
            $collectionCount++;
            $collectionTotal += (float) ($payment->amount ?? 0);
        }

        $depositCount = 0;
        $depositTotal = 0.0;
        foreach ($recentLoans as $loan) {
            $depositCount++;
            $depositTotal += (float) ($loan->payment_amount ?? 0);
        }

        return [
            'payment_count' => $collectionCount + $depositCount,
            'collection_count' => $collectionCount,
            'deposit_count' => $depositCount,
            'payment_total' => $collectionTotal + $depositTotal,
            'collection_total' => $collectionTotal,
            'deposit_total' => $depositTotal,
        ];
    }

    protected function dashboardLoanStatusRows(array $filters)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return collect();
        }

        $loanDateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'sale_date', 'created_at']);
        if (! $loanDateColumn) {
            return collect();
        }

        $query = DB::connection('mysql_loan')->table('loans as l');
        $this->applyDashboardLoanFilters($query, $filters, 'l', $loanDateColumn);

        return $query
            ->selectRaw('COALESCE(NULLIF(l.status, ""), "unknown") as status')
            ->selectRaw('COUNT(*) as loan_count')
            ->selectRaw($this->sumSql('loans', 'l', ['principal_amount', 'financed_amount']).' as principal_total')
            ->selectRaw($this->sumSql('loans', 'l', ['paid_amount', 'amount_paid']).' as paid_total')
            ->selectRaw($this->sumSql('loans', 'l', ['balance_amount', 'amount_balance']).' as balance_total')
            ->groupBy('status')
            ->orderByDesc('loan_count')
            ->get();
    }

    protected function dashboardCollectionRows(array $filters)
    {
        $query = $this->dashboardPaymentBaseQuery($filters, true);
        if (! $query) {
            return collect();
        }

        $dateColumn = $this->firstLoanReportColumn('loan_payments', ['paid_date', 'paid_on', 'payment_date', 'paid_at', 'created_at']);
        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $period = $this->dashboardCollectionPeriodExpression($filters['period'] ?? 'daily', 'p', $dateColumn);

        return $query
            ->selectRaw($period['select'].' as period_key')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('COALESCE(SUM('.$amountExpr.'), 0) as payment_total')
            ->groupBy(DB::raw($period['group_by']))
            ->orderByDesc('period_key')
            ->limit($period['limit'])
            ->get();
    }

    protected function dashboardCollectionPeriodExpression(string $period, string $alias, string $dateColumn): array
    {
        $qualifiedDate = $alias.'.'.$dateColumn;

        if ($period === 'yearly') {
            return [
                'select' => 'YEAR('.$qualifiedDate.')',
                'group_by' => 'YEAR('.$qualifiedDate.')',
                'limit' => 50,
            ];
        }

        if ($period === 'monthly') {
            return [
                'select' => 'DATE_FORMAT('.$qualifiedDate.', "%Y-%m")',
                'group_by' => 'DATE_FORMAT('.$qualifiedDate.', "%Y-%m")',
                'limit' => 120,
            ];
        }

        return [
            'select' => 'DATE('.$qualifiedDate.')',
            'group_by' => 'DATE('.$qualifiedDate.')',
            'limit' => 366,
        ];
    }

    protected function dashboardCollectionPeriodLabel(string $period): string
    {
        return [
            'monthly' => $this->loanReportText('Month', 'ខែ'),
            'yearly' => $this->loanReportText('Year', 'ឆ្នាំ'),
        ][$period] ?? $this->loanReportText('Date', 'ថ្ងៃ');
    }

    protected function dashboardPaymentMethodRows(array $filters): array
    {
        $query = $this->dashboardPaymentBaseQuery($filters);
        if (! $query) {
            return [];
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $typeExpr = $this->dashboardPaymentTypeExpression('p');
        $methodExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'payment_method_snapshot')
            ? 'p.payment_method_snapshot'
            : (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'channel') ? 'p.channel' : '"Cash"');

        $rows = $query
            ->selectRaw('p.id')
            ->selectRaw($typeExpr.' as payment_type')
            ->selectRaw('COALESCE(NULLIF('.$methodExpr.', ""), "Cash") as payment_method')
            ->selectRaw($amountExpr.' as amount')
            ->get();

        $rows = $this->appendRecentPaymentMethodDetails($rows);

        $summary = [];
        foreach ($rows as $row) {
            $type = $this->dashboardPaymentTypeKey((string) ($row->payment_type ?? 'loan'));
            if (! isset($summary[$type])) {
                $summary[$type] = [
                    'label' => $this->dashboardPaymentTypeLabel($type),
                    'count' => 0,
                    'cash' => 0.0,
                    'aba' => 0.0,
                    'acleda' => 0.0,
                    'wing' => 0.0,
                    'et' => 0.0,
                    'card' => 0.0,
                    'other' => 0.0,
                    'total' => 0.0,
                ];
            }

            $summary[$type]['count']++;
            $this->addDashboardPaymentSummaryAmounts($summary[$type], $row, (float) ($row->amount ?? 0), (string) ($row->payment_method ?? ''));
        }

        uksort($summary, function ($left, $right) {
            $order = ['monthly' => 0, 'loan' => 1];

            return ($order[$left] ?? 99) <=> ($order[$right] ?? 99) ?: strcmp($left, $right);
        });

        return array_values($summary);
    }

    protected function dashboardPaymentMethodRowsFromActivity($recentPayments, $recentLoans): array
    {
        $summary = [];

        foreach ($recentPayments as $payment) {
            $type = 'monthly';
            if (! isset($summary[$type])) {
                $summary[$type] = $this->emptyDashboardPaymentSummaryRow($type);
            }

            $summary[$type]['count']++;
            $this->addDashboardPaymentSummaryAmounts($summary[$type], $payment, (float) ($payment->amount ?? 0), (string) ($payment->payment_method ?? ''));
        }

        foreach ($recentLoans as $loan) {
            $type = 'loan';
            if (! isset($summary[$type])) {
                $summary[$type] = $this->emptyDashboardPaymentSummaryRow($type);
            }

            $summary[$type]['count']++;
            $this->addDashboardPaymentSummaryAmounts($summary[$type], $loan, (float) ($loan->payment_amount ?? 0), (string) ($loan->payment_method ?? ''));
        }

        uksort($summary, function ($left, $right) {
            $order = ['monthly' => 0, 'loan' => 1];

            return ($order[$left] ?? 99) <=> ($order[$right] ?? 99) ?: strcmp($left, $right);
        });

        return array_values($summary);
    }

    protected function addDashboardPaymentSummaryAmounts(array &$summaryRow, $source, float $fallbackAmount, string $fallbackMethod): void
    {
        $hasSplitAmounts = false;
        foreach (['cash', 'aba', 'acleda', 'wing', 'et', 'card', 'other'] as $bucket) {
            $property = $bucket.'_amount';
            if (isset($source->{$property})) {
                $amount = (float) ($source->{$property} ?? 0);
                $summaryRow[$bucket] += $amount;
                $hasSplitAmounts = $hasSplitAmounts || abs($amount) > 0.0001;
            }
        }

        if (! $hasSplitAmounts) {
            $bucket = $this->dashboardPaymentMethodBucket($fallbackMethod);
            $summaryRow[$bucket] += $fallbackAmount;
        }

        $summaryRow['total'] += $fallbackAmount;
    }

    protected function emptyDashboardPaymentSummaryRow(string $type): array
    {
        return [
            'label' => $this->dashboardPaymentTypeLabel($type),
            'count' => 0,
            'cash' => 0.0,
            'aba' => 0.0,
            'acleda' => 0.0,
            'wing' => 0.0,
            'et' => 0.0,
            'card' => 0.0,
            'other' => 0.0,
            'total' => 0.0,
        ];
    }

    protected function dashboardPaymentTypeKey(string $type): string
    {
        $type = strtolower(trim($type));

        if (in_array($type, ['monthly', 'collection', 'installment'], true)) {
            return 'monthly';
        }

        if (in_array($type, ['loan', 'initial', 'down_payment', 'downpayment', 'deposit'], true)) {
            return 'loan';
        }

        return $type !== '' ? $type : 'monthly';
    }

    protected function dashboardPaymentTypeLabel(string $type): string
    {
        if ($type === 'monthly') {
            return $this->loanReportText('Recent Collected Payments Reports', 'របាយការណ៍ការបង់ប្រាក់ថ្មីៗ');
        }

        if ($type === 'loan') {
            return $this->loanReportText('Loans Reports', 'របាយការណ៍កម្ចី');
        }

        return ucwords(str_replace('_', ' ', $type));
    }

    protected function dashboardPaymentMethodBucket(string $method): string
    {
        $method = strtoupper(trim($method));

        if ($method === '' || str_contains($method, 'CASH')) {
            return 'cash';
        }
        if (str_contains($method, 'ABA')) {
            return 'aba';
        }
        if (str_contains($method, 'ACLEDA')) {
            return 'acleda';
        }
        if (str_contains($method, 'WING')) {
            return 'wing';
        }
        if (str_contains($method, 'E&T') || str_contains($method, 'E T') || str_contains($method, 'ET')) {
            return 'et';
        }
        if (str_contains($method, 'CARD') || str_contains($method, 'VISA') || str_contains($method, 'MASTER')) {
            return 'card';
        }

        return 'other';
    }

    protected function dashboardRecentLoans(array $filters)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return collect();
        }

        $loanDateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'sale_date', 'created_at']);
        if (! $loanDateColumn) {
            return collect();
        }

        $query = DB::connection('mysql_loan')->table('loans as l');
        $this->applyDashboardLoanFilters($query, $filters, 'l', $loanDateColumn);

        $loans = $query
            ->selectRaw('l.id')
            ->selectRaw((Schema::connection('mysql_loan')->hasColumn('loans', 'loan_number') ? 'l.loan_number' : 'CONCAT("Loan #", l.id)').' as loan_number')
            ->selectRaw($this->coalesceSql('loans', 'l', ['customer_name_snapshot'], '""').' as customer_name')
            ->selectRaw($this->coalesceSql('loans', 'l', ['product_name_snapshot'], '""').' as product_name')
            ->selectRaw('l.'.$loanDateColumn.' as loan_date')
            ->selectRaw((Schema::connection('mysql_loan')->hasColumn('loans', 'status') ? 'l.status' : '"unknown"').' as status')
            ->selectRaw($this->coalesceSql('loans', 'l', ['principal_amount', 'financed_amount']).' as principal_amount')
            ->selectRaw($this->coalesceSql('loans', 'l', ['down_payment'], '0').' as payment_amount')
            ->orderByDesc('l.'.$loanDateColumn)
            ->orderByDesc('l.id')
            ->get();

        return $this->appendRecentLoanPaymentInfo($this->appendRecentLoanDepositAmounts($this->appendRecentLoanProducts($loans)));
    }

    protected function appendRecentLoanDepositAmounts($loans)
    {
        if ($loans->isEmpty()
            || ! Schema::connection('mysql_loan')->hasTable('loan_payments')
            || ! Schema::connection('mysql_loan')->hasColumn('loan_payments', 'loan_id')) {
            return $loans;
        }

        $loanIds = $loans->pluck('id')->filter()->values()->all();
        if (empty($loanIds)) {
            return $loans;
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $query = DB::connection('mysql_loan')
            ->table('loan_payments as p')
            ->whereIn('p.loan_id', $loanIds);

        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }
        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'payment_type')) {
            $query->whereIn('p.payment_type', ['loan', 'initial', 'down_payment', 'downpayment', 'deposit']);
        } elseif (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'schedule_id')) {
            $query->whereNull('p.schedule_id');
        }

        $depositAmounts = $query
            ->selectRaw('p.loan_id')
            ->selectRaw('COALESCE(SUM('.$amountExpr.'), 0) as deposit_amount')
            ->groupBy('p.loan_id')
            ->pluck('deposit_amount', 'loan_id');

        foreach ($loans as $loan) {
            $depositAmount = (float) ($depositAmounts->get($loan->id) ?? 0);
            if ($depositAmount > 0) {
                $loan->payment_amount = $depositAmount;
            }
        }

        return $loans;
    }

    protected function appendRecentLoanProducts($loans)
    {
        foreach ($loans as $loan) {
            $loan->product_name = trim((string) ($loan->product_name ?? '')) ?: '-';
        }

        if ($loans->isEmpty()
            || ! Schema::connection('mysql_loan')->hasTable('loan_items')
            || ! Schema::connection('mysql_loan')->hasColumn('loan_items', 'loan_id')) {
            return $loans;
        }

        $loanIds = $loans->pluck('id')->filter()->values()->all();
        if (empty($loanIds)) {
            return $loans;
        }

        $productExpr = $this->coalesceSql('loan_items', 'li', ['product_name_snapshot', 'product_name'], '""');
        $query = DB::connection('mysql_loan')
            ->table('loan_items as li')
            ->whereIn('li.loan_id', $loanIds);

        if (Schema::connection('mysql_loan')->hasColumn('loan_items', 'deleted_at')) {
            $query->whereNull('li.deleted_at');
        }

        $products = $query
            ->selectRaw('li.loan_id')
            ->selectRaw('GROUP_CONCAT(DISTINCT NULLIF(TRIM('.$productExpr.'), "") ORDER BY li.id SEPARATOR " | ") as product_name')
            ->groupBy('li.loan_id')
            ->pluck('product_name', 'loan_id');

        foreach ($loans as $loan) {
            $productName = trim((string) ($products->get($loan->id) ?? ''));
            if ($productName !== '') {
                $loan->product_name = $productName;
            }
        }

        return $loans;
    }

    protected function appendRecentLoanPaymentInfo($loans)
    {
        foreach ($loans as $loan) {
            $loan->payment_method = '-';
            $loan->payment_note = '-';
            foreach (['cash', 'aba', 'acleda', 'wing', 'et', 'card', 'other'] as $methodBucket) {
                $loan->{$methodBucket.'_amount'} = 0.0;
            }
            $loan->bank_amount = 0.0;
        }

        if ($loans->isEmpty()
            || ! Schema::connection('mysql_loan')->hasTable('loan_payments')
            || ! Schema::connection('mysql_loan')->hasColumn('loan_payments', 'loan_id')) {
            return $loans;
        }

        $loanIds = $loans->pluck('id')->filter()->values()->all();
        if (empty($loanIds)) {
            return $loans;
        }

        $dateColumn = $this->firstLoanReportColumn('loan_payments', ['paid_date', 'paid_on', 'payment_date', 'paid_at', 'created_at']);
        if (! $dateColumn) {
            return $loans;
        }

        $methodExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'payment_method_snapshot')
            ? 'p.payment_method_snapshot'
            : (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'channel') ? 'p.channel' : '""');
        $noteExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'note') ? 'p.note' : '""';
        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');

        $paymentsQuery = DB::connection('mysql_loan')
            ->table('loan_payments as p')
            ->whereIn('p.loan_id', $loanIds)
            ->when(Schema::connection('mysql_loan')->hasColumn('loan_payments', 'deleted_at'), fn ($query) => $query->whereNull('p.deleted_at'));

        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'payment_type')) {
            $paymentsQuery->whereIn('p.payment_type', ['loan', 'initial', 'down_payment', 'downpayment', 'deposit']);
        } elseif (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'schedule_id')) {
            $paymentsQuery->whereNull('p.schedule_id');
        }

        $payments = $paymentsQuery
            ->selectRaw('p.id, p.loan_id')
            ->selectRaw($methodExpr.' as payment_method')
            ->selectRaw($noteExpr.' as note')
            ->selectRaw($amountExpr.' as amount')
            ->orderByDesc('p.'.$dateColumn)
            ->orderByDesc('p.id')
            ->get();

        $paymentsByLoan = $this->appendRecentPaymentMethodDetails($payments)->groupBy('loan_id');

        foreach ($loans as $loan) {
            $loanPayments = $paymentsByLoan->get($loan->id, collect());
            if ($loanPayments->isEmpty()) {
                continue;
            }

            $loan->payment_amount = (float) $loanPayments->sum('amount');
            $loan->payment_method = $loanPayments->pluck('payment_method')->filter()->unique()->implode(', ') ?: '-';
            $loan->payment_note = $loanPayments->pluck('note')->filter()->unique()->implode(', ') ?: '-';
            foreach (['cash', 'aba', 'acleda', 'wing', 'et', 'card', 'other'] as $methodBucket) {
                $property = $methodBucket.'_amount';
                $loan->{$property} = (float) $loanPayments->sum($property);
            }
            $loan->bank_amount = (float) $loanPayments->sum('bank_amount');
        }

        return $loans;
    }

    protected function dashboardRecentPayments(array $filters)
    {
        $query = $this->dashboardPaymentBaseQuery($filters, true);
        if (! $query) {
            return collect();
        }

        $dateColumn = $this->firstLoanReportColumn('loan_payments', ['paid_date', 'paid_on', 'payment_date', 'paid_at', 'created_at']);
        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $canJoinLoans = Schema::connection('mysql_loan')->hasTable('loans')
            && Schema::connection('mysql_loan')->hasColumn('loan_payments', 'loan_id');
        $loanNumberExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'loan_number_snapshot')
            ? 'p.loan_number_snapshot'
            : ($canJoinLoans && Schema::connection('mysql_loan')->hasColumn('loans', 'loan_number') ? 'l.loan_number' : 'CONCAT("Payment #", p.id)');
        $customerExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'customer_name_snapshot')
            ? 'p.customer_name_snapshot'
            : ($canJoinLoans && Schema::connection('mysql_loan')->hasColumn('loans', 'customer_name_snapshot') ? 'l.customer_name_snapshot' : '""');
        $receiptExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'receipt_number')
            ? 'p.receipt_number'
            : (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'payment_ref_no') ? 'p.payment_ref_no' : 'CONCAT("#", p.id)');
        $methodExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'payment_method_snapshot')
            ? 'p.payment_method_snapshot'
            : (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'channel') ? 'p.channel' : '""');
        $noteExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'note') ? 'p.note' : '""';
        $receivedByExpr = Schema::connection('mysql_loan')->hasColumn('loan_payments', 'received_by_name_snapshot')
            ? 'p.received_by_name_snapshot'
            : (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'collected_by_name_snapshot') ? 'p.collected_by_name_snapshot' : '""');
        $paymentTypeExpr = $this->dashboardPaymentTypeExpression('p');
        $paymentPrincipalExpr = $this->coalesceSql('loan_payments', 'p', ['principal_paid'], '0');
        $paymentInterestExpr = $this->coalesceSql('loan_payments', 'p', ['interest_paid'], '0');
        $penaltyExpr = $this->coalesceSql('loan_payments', 'p', ['penalty_amount'], '0');
        $customerPhoneExpr = Schema::connection('mysql_loan')->hasColumn('loans', 'customer_phone_snapshot') ? 'l.customer_phone_snapshot' : '""';
        $loanMonthCountExpr = Schema::connection('mysql_loan')->hasColumn('loans', 'duration_months')
            ? 'l.duration_months'
            : (Schema::connection('mysql_loan')->hasColumn('loans', 'installment_count') ? 'l.installment_count' : '0');
        $customerEmailExpr = '""';
        $customerNameExprForExport = $customerExpr;
        if (Schema::connection('mysql_loan')->hasTable('loan_customers') && Schema::connection('mysql_loan')->hasColumn('loans', 'customer_id')) {
            $query->leftJoin('loan_customers as c', 'c.id', '=', 'l.customer_id');
            $customerPhoneExpr = Schema::connection('mysql_loan')->hasColumn('loan_customers', 'phone')
                ? 'COALESCE(NULLIF(c.phone, ""), '.$customerPhoneExpr.')'
                : $customerPhoneExpr;
            $customerEmailExpr = Schema::connection('mysql_loan')->hasColumn('loan_customers', 'email') ? 'c.email' : '""';
            $customerNameExprForExport = Schema::connection('mysql_loan')->hasColumn('loan_customers', 'name')
                ? 'COALESCE(NULLIF(c.name, ""), '.$customerExpr.')'
                : $customerExpr;
        }
        $hasScheduleJoin = Schema::connection('mysql_loan')->hasTable('loan_payment_schedules') && Schema::connection('mysql_loan')->hasColumn('loan_payments', 'schedule_id');
        if ($hasScheduleJoin) {
            $query->leftJoin('loan_payment_schedules as s', 's.id', '=', 'p.schedule_id');
            $scheduleNumberExpr = Schema::connection('mysql_loan')->hasColumn('loan_payment_schedules', 'installment_no') ? 's.installment_no' : 'NULL';
        } else {
            $scheduleNumberExpr = 'NULL';
        }
        $schedulePrincipalExpr = $hasScheduleJoin ? $this->coalesceSql('loan_payment_schedules', 's', ['principal_amount', 'principal_due', 'principal'], '0') : '0';
        $scheduleInterestExpr = $hasScheduleJoin ? $this->coalesceSql('loan_payment_schedules', 's', ['interest_amount', 'interest_due', 'interest'], '0') : '0';
        $scheduleLinkedExpr = $hasScheduleJoin ? 'p.schedule_id IS NOT NULL' : '0';
        $schedulePaymentBaseExpr = 'GREATEST(('.$amountExpr.' - '.$penaltyExpr.'), 0)';
        $scheduleFallbackPrincipalExpr = 'LEAST('.$schedulePrincipalExpr.', '.$schedulePaymentBaseExpr.')';
        $scheduleFallbackInterestExpr = 'LEAST('.$scheduleInterestExpr.', GREATEST(('.$schedulePaymentBaseExpr.' - '.$scheduleFallbackPrincipalExpr.'), 0))';
        $loanPrincipalExpr = $this->coalesceSql('loans', 'l', ['principal_amount', 'financed_amount'], '0');
        $loanInterestExpr = $this->coalesceSql('loans', 'l', ['interest_amount'], '0');
        $loanMonthDivisorExpr = 'NULLIF('.$loanMonthCountExpr.', 0)';
        $loanMonthlyPrincipalExpr = 'LEAST(ROUND(('.$loanPrincipalExpr.' / '.$loanMonthDivisorExpr.'), 2), '.$schedulePaymentBaseExpr.')';
        $loanMonthlyInterestExpr = 'LEAST(ROUND(('.$loanInterestExpr.' / '.$loanMonthDivisorExpr.'), 2), GREATEST(('.$schedulePaymentBaseExpr.' - '.$loanMonthlyPrincipalExpr.'), 0))';
        $principalExpr = 'CASE WHEN '.$scheduleLinkedExpr.' THEN '.$scheduleFallbackPrincipalExpr.' WHEN ('.$paymentPrincipalExpr.' + '.$paymentInterestExpr.') > 0 THEN '.$paymentPrincipalExpr.' WHEN '.$loanMonthCountExpr.' > 0 THEN '.$loanMonthlyPrincipalExpr.' ELSE '.$scheduleFallbackPrincipalExpr.' END';
        $interestExpr = 'CASE WHEN '.$scheduleLinkedExpr.' THEN '.$scheduleFallbackInterestExpr.' WHEN ('.$paymentPrincipalExpr.' + '.$paymentInterestExpr.') > 0 THEN '.$paymentInterestExpr.' WHEN '.$loanMonthCountExpr.' > 0 THEN '.$loanMonthlyInterestExpr.' ELSE '.$scheduleFallbackInterestExpr.' END';

        $payments = $query
            ->selectRaw('p.id')
            ->selectRaw($receiptExpr.' as receipt_number')
            ->selectRaw('p.'.$dateColumn.' as paid_date')
            ->selectRaw($loanNumberExpr.' as loan_number')
            ->selectRaw($customerExpr.' as customer_name')
            ->selectRaw($customerPhoneExpr.' as customer_phone')
            ->selectRaw($loanMonthCountExpr.' as month_count')
            ->selectRaw($paymentTypeExpr.' as payment_type')
            ->selectRaw($methodExpr.' as payment_method')
            ->selectRaw($noteExpr.' as note')
            ->selectRaw($receivedByExpr.' as received_by_name')
            ->selectRaw($amountExpr.' as amount')
            ->selectRaw($principalExpr.' as principal_amount')
            ->selectRaw($interestExpr.' as interest_amount')
            ->selectRaw($penaltyExpr.' as penalty_amount')
            ->selectRaw($customerEmailExpr.' as customer_email')
            ->selectRaw($customerNameExprForExport.' as export_customer_name')
            ->selectRaw($scheduleNumberExpr.' as number_of_month')
            ->selectRaw((Schema::connection('mysql_loan')->hasColumn('loans', 'status') ? 'l.status' : '""').' as loan_status')
            ->selectRaw((Schema::connection('mysql_loan')->hasColumn('loan_payments', 'status') ? 'p.status' : '"confirmed"').' as status')
            ->when(Schema::connection('mysql_loan')->hasColumn('loan_payments', 'proof_file_id'), fn ($query) => $query->addSelect('p.proof_file_id'))
            ->orderByDesc('p.'.$dateColumn)
            ->orderByDesc('p.id')
            ->get();

        return $this->appendRecentPaymentDocUrls($this->appendRecentPaymentMethodDetails($payments));
    }

    protected function appendRecentPaymentMethodDetails($payments)
    {
        if ($payments->isEmpty()
            || ! Schema::connection('mysql_loan')->hasTable('loan_payment_details')
            || ! Schema::connection('mysql_loan')->hasColumn('loan_payment_details', 'payment_id')) {
            foreach ($payments as $payment) {
                $bucket = $this->dashboardPaymentMethodBucket((string) ($payment->payment_method ?? ''));
                foreach (['cash', 'aba', 'acleda', 'wing', 'et', 'card', 'other'] as $methodBucket) {
                    $payment->{$methodBucket.'_amount'} = $bucket === $methodBucket ? (float) ($payment->amount ?? 0) : 0.0;
                }
                $payment->bank_amount = $bucket !== 'cash' ? (float) ($payment->amount ?? 0) : 0.0;
                $payment->payment_channel = $payment->payment_method ?: '-';
                $payment->transaction_no = null;
            }

            return $payments;
        }

        $paymentIds = $payments->pluck('id')->filter()->values()->all();
        if (empty($paymentIds)) {
            return $payments;
        }

        $methodExpr = Schema::connection('mysql_loan')->hasColumn('loan_payment_details', 'payment_method_snapshot')
            ? 'd.payment_method_snapshot'
            : (Schema::connection('mysql_loan')->hasColumn('loan_payment_details', 'method') ? 'd.method' : '"Cash"');
        $amountExpr = $this->coalesceSql('loan_payment_details', 'd', ['amount_base', 'amount'], '0');
        $transactionExpr = Schema::connection('mysql_loan')->hasColumn('loan_payment_details', 'transaction_no')
            ? 'd.transaction_no'
            : (Schema::connection('mysql_loan')->hasColumn('loan_payment_details', 'reference_number') ? 'd.reference_number' : 'NULL');

        $details = DB::connection('mysql_loan')
            ->table('loan_payment_details as d')
            ->whereIn('d.payment_id', $paymentIds)
            ->selectRaw('d.payment_id')
            ->selectRaw('COALESCE(NULLIF('.$methodExpr.', ""), "Cash") as method_name')
            ->selectRaw($amountExpr.' as amount')
            ->selectRaw($transactionExpr.' as transaction_no')
            ->orderBy('d.id')
            ->get()
            ->groupBy('payment_id');

        foreach ($payments as $payment) {
            $rows = $details->get($payment->id, collect());
            if ($rows->isEmpty()) {
                $bucket = $this->dashboardPaymentMethodBucket((string) ($payment->payment_method ?? ''));
                foreach (['cash', 'aba', 'acleda', 'wing', 'et', 'card', 'other'] as $methodBucket) {
                    $payment->{$methodBucket.'_amount'} = $bucket === $methodBucket ? (float) ($payment->amount ?? 0) : 0.0;
                }
                $payment->bank_amount = $bucket !== 'cash' ? (float) ($payment->amount ?? 0) : 0.0;
                $payment->payment_channel = $payment->payment_method ?: '-';
                $payment->transaction_no = null;
                continue;
            }

            $payment->payment_method = $rows
                ->map(fn ($row) => trim((string) $row->method_name).' $'.number_format((float) ($row->amount ?? 0), 2))
                ->implode(', ');
            foreach (['cash', 'aba', 'acleda', 'wing', 'et', 'card', 'other'] as $methodBucket) {
                $payment->{$methodBucket.'_amount'} = (float) $rows
                    ->filter(fn ($row) => $this->dashboardPaymentMethodBucket((string) ($row->method_name ?? '')) === $methodBucket)
                    ->sum('amount');
            }
            $payment->bank_amount = (float) $rows
                ->reject(fn ($row) => $this->dashboardPaymentMethodBucket((string) ($row->method_name ?? '')) === 'cash')
                ->sum('amount');
            $payment->payment_channel = $rows->pluck('method_name')->filter()->unique()->implode(', ');
            $payment->transaction_no = $rows->pluck('transaction_no')->filter()->unique()->implode(', ');
        }

        return $payments;
    }

    protected function appendRecentPaymentDocUrls($payments)
    {
        if ($payments->isEmpty() || ! Schema::connection('mysql_loan')->hasTable('loan_files')) {
            return $payments;
        }

        $paymentIds = $payments->pluck('id')->filter()->values()->all();
        if (empty($paymentIds)) {
            return $payments;
        }

        $files = collect();

        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'proof_file_id')) {
            $proofFileIds = $payments->pluck('proof_file_id')->filter()->unique()->values()->all();
            if (! empty($proofFileIds)) {
                $proofFiles = DB::connection('mysql_loan')
                    ->table('loan_files')
                    ->whereIn('id', $proofFileIds)
                    ->when(Schema::connection('mysql_loan')->hasColumn('loan_files', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                    ->get()
                    ->keyBy('id');

                foreach ($payments as $payment) {
                    $file = $proofFiles->get($payment->proof_file_id ?? null);
                    if ($file) {
                        $files->put((int) $payment->id, $file);
                    }
                }
            }
        }

        $missingPaymentIds = collect($paymentIds)
            ->reject(fn ($paymentId) => $files->has((int) $paymentId))
            ->values()
            ->all();

        if (! empty($missingPaymentIds)) {
            DB::connection('mysql_loan')
                ->table('loan_files')
                ->whereIn('fileable_id', $missingPaymentIds)
                ->where('category', 'payment_doc')
                ->whereIn('fileable_type', ['loan_payment', \Modules\LoanManagement\Entities\LoanPayment::class])
                ->when(Schema::connection('mysql_loan')->hasColumn('loan_files', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                ->orderByDesc('id')
                ->get()
                ->each(function ($file) use ($files) {
                    $paymentId = (int) ($file->fileable_id ?? 0);
                    if ($paymentId > 0 && ! $files->has($paymentId)) {
                        $files->put($paymentId, $file);
                    }
                });
        }

        foreach ($payments as $payment) {
            $file = $files->get((int) $payment->id);
            $payment->payment_doc_url = $this->loanFileUrl($file);
        }

        return $payments;
    }

    protected function loanFileUrl($file): ?string
    {
        $path = $file->path ?? $file->file_path ?? null;
        if (! $file || empty($path)) {
            return null;
        }

        return Storage::disk($file->disk ?? 'public')->url($path);
    }

    protected function dashboardPaymentBaseQuery(array $filters, bool $excludeInitialDownPayments = false)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payments')
            || ! Schema::connection('mysql_loan')->hasTable('loans')
            || ! Schema::connection('mysql_loan')->hasColumn('loan_payments', 'loan_id')) {
            return null;
        }

        $dateColumn = $this->firstLoanReportColumn('loan_payments', ['paid_date', 'paid_on', 'payment_date', 'paid_at', 'created_at']);
        if (! $dateColumn) {
            return null;
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $query = DB::connection('mysql_loan')
            ->table('loan_payments as p')
            ->join('loans as l', 'l.id', '=', 'p.loan_id');

        $query->whereDate('p.'.$dateColumn, '>=', $filters['date_from'])
            ->whereDate('p.'.$dateColumn, '<=', $filters['date_to']);

        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'deleted_at')) {
            $query->whereNull('p.deleted_at');
        }
        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'status')) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereIn('p.status', ['paid', 'confirmed', ''])
                    ->orWhereNull('p.status');
            });
        }

        if ($excludeInitialDownPayments
            && Schema::connection('mysql_loan')->hasColumn('loans', 'loan_date')
            && Schema::connection('mysql_loan')->hasColumn('loans', 'down_payment')) {
            $query->where(function ($paymentQuery) use ($dateColumn, $amountExpr) {
                $paymentQuery->whereNull('l.down_payment')
                    ->orWhere('l.down_payment', '<=', 0)
                    ->orWhereRaw('DATE(p.'.$dateColumn.') <> DATE(l.loan_date)')
                    ->orWhereRaw('ABS(('.$amountExpr.') - l.down_payment) > 0.0001');
            });
        }

        $this->applyDashboardLoanLocationAndSearchFilters($query, $filters, 'l');
        if (Schema::connection('mysql_loan')->hasColumn('loans', 'deleted_at')) {
            $query->whereNull('l.deleted_at');
        }

        return $query;
    }

    protected function dashboardPaymentTypeExpression(string $alias = 'p'): string
    {
        $prefix = $alias.'.';

        if (! Schema::connection('mysql_loan')->hasColumn('loan_payments', 'payment_type')) {
            if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'schedule_id')) {
                return 'CASE WHEN '.$prefix.'schedule_id IS NULL THEN "loan" ELSE "monthly" END';
            }

            return '"monthly"';
        }

        $type = 'LOWER(TRIM(COALESCE('.$prefix.'payment_type, "")))';

        return 'CASE
            WHEN '.$type.' IN ("loan", "initial", "down_payment", "downpayment", "deposit", "customer_deposit", "customer_deposit_payment") THEN "loan"
            WHEN '.$type.' IN ("monthly", "collection", "installment", "") THEN "monthly"
            ELSE '.$type.'
        END';
    }

    protected function applyDashboardLoanFilters($query, array $filters, string $loanAlias, string $dateColumn): void
    {
        $query->whereDate($loanAlias.'.'.$dateColumn, '>=', $filters['date_from'])
            ->whereDate($loanAlias.'.'.$dateColumn, '<=', $filters['date_to']);

        if (Schema::connection('mysql_loan')->hasColumn('loans', 'deleted_at')) {
            $query->whereNull($loanAlias.'.deleted_at');
        }

        $this->applyDashboardLoanLocationAndSearchFilters($query, $filters, $loanAlias);
    }

    protected function applyDashboardLoanLocationAndSearchFilters($query, array $filters, string $loanAlias): void
    {
        if (! empty($filters['location_id'])) {
            $locationFilter = $this->parseYearlyLocationFilter((string) $filters['location_id']);
            if (! empty($locationFilter)) {
                $query->where(function ($where) use ($loanAlias, $locationFilter) {
                    if (! empty($locationFilter['loan_location_id']) && Schema::connection('mysql_loan')->hasColumn('loans', 'business_location_id')) {
                        $where->orWhere($loanAlias.'.business_location_id', (int) $locationFilter['loan_location_id']);
                    }
                    if (! empty($locationFilter['main_location_id']) && Schema::connection('mysql_loan')->hasColumn('loans', 'main_location_id')) {
                        $where->orWhere($loanAlias.'.main_location_id', (int) $locationFilter['main_location_id']);
                    }
                    if (! empty($locationFilter['legacy_id'])) {
                        if (Schema::connection('mysql_loan')->hasColumn('loans', 'business_location_id')) {
                            $where->orWhere($loanAlias.'.business_location_id', (int) $locationFilter['legacy_id']);
                        }
                        if (Schema::connection('mysql_loan')->hasColumn('loans', 'main_location_id')) {
                            $where->orWhere($loanAlias.'.main_location_id', (int) $locationFilter['legacy_id']);
                        }
                    }
                    if (! empty($locationFilter['name'])) {
                        foreach (['location_name_snapshot', 'business_location_name_snapshot'] as $column) {
                            if (Schema::connection('mysql_loan')->hasColumn('loans', $column)) {
                                $where->orWhere($loanAlias.'.'.$column, $locationFilter['name']);
                            }
                        }
                    }
                });
            }
        }

        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';
            $searchColumns = array_values(array_filter(['loan_number', 'source_invoice_no', 'customer_name_snapshot', 'customer_phone_snapshot'], fn ($column) => Schema::connection('mysql_loan')->hasColumn('loans', $column)));
            if (! empty($searchColumns)) {
                $query->where(function ($where) use ($loanAlias, $like, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $where->orWhere($loanAlias.'.'.$column, 'like', $like);
                    }
                });
            }
        }
    }

    protected function buildYearlyLoanSummary(array $filters): array
    {
        $years = range((int) $filters['start_year'], (int) $filters['end_year']);
        $rows = [];
        foreach ($years as $year) {
            $rows[$year] = $this->emptyYearlySummaryRow($year);
        }

        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return [
                'rows' => array_values($rows),
                'totals' => $this->sumYearlySummaryRows($rows),
                'cards' => $this->yearlySummaryCards($rows),
            ];
        }

        $loanRows = $this->yearlyLoanAggregates($filters);
        foreach ($loanRows as $row) {
            $year = (int) $row->report_year;
            if (! isset($rows[$year])) {
                continue;
            }
            $rows[$year]['loan_count'] = (int) ($row->loan_count ?? 0);
            $rows[$year]['principal_total'] = (float) ($row->principal_total ?? 0);
            $rows[$year]['interest_total'] = (float) ($row->interest_total ?? 0);
            $rows[$year]['loan_total'] = (float) ($row->loan_total ?? 0);
            $rows[$year]['loan_paid_total'] = (float) ($row->loan_paid_total ?? 0);
            $rows[$year]['loan_balance_total'] = (float) ($row->loan_balance_total ?? 0);
            $rows[$year]['paid_customer_count'] = (int) ($row->paid_customer_count ?? 0);
            $rows[$year]['closed_count'] = (int) ($row->closed_count ?? 0);
            $rows[$year]['closed_principal_total'] = (float) ($row->closed_principal_total ?? 0);
            $rows[$year]['closed_interest_total'] = (float) ($row->closed_interest_total ?? 0);
            $rows[$year]['closed_loan_total'] = (float) ($row->closed_loan_total ?? 0);
            $rows[$year]['closed_paid_total'] = (float) ($row->closed_paid_total ?? 0);
            $rows[$year]['closed_balance_total'] = (float) ($row->closed_balance_total ?? 0);
            $rows[$year]['bad_count'] = (int) ($row->bad_count ?? 0);
            $rows[$year]['bad_principal_total'] = (float) ($row->bad_principal_total ?? 0);
            $rows[$year]['bad_interest_total'] = (float) ($row->bad_interest_total ?? 0);
            $rows[$year]['bad_loan_total'] = (float) ($row->bad_loan_total ?? 0);
            $rows[$year]['bad_paid_total'] = (float) ($row->bad_paid_total ?? 0);
            $rows[$year]['bad_balance_total'] = (float) ($row->bad_balance_total ?? 0);
        }

        foreach ($this->yearlyScheduleAggregates($filters) as $row) {
            $year = (int) $row->report_year;
            if (! isset($rows[$year])) {
                continue;
            }
            $rows[$year]['schedule_count'] = (int) ($row->schedule_count ?? 0);
            $rows[$year]['schedule_principal_total'] = (float) ($row->schedule_principal_total ?? 0);
            $rows[$year]['schedule_interest_total'] = (float) ($row->schedule_interest_total ?? 0);
            $rows[$year]['schedule_due_total'] = (float) ($row->schedule_due_total ?? 0);
            $rows[$year]['schedule_paid_total'] = (float) ($row->schedule_paid_total ?? 0);
            $rows[$year]['schedule_balance_total'] = (float) ($row->schedule_balance_total ?? 0);
            $rows[$year]['overdue_count'] = (int) ($row->overdue_count ?? 0);
            $rows[$year]['overdue_balance_total'] = (float) ($row->overdue_balance_total ?? 0);
        }

        foreach ($this->yearlyPaymentAggregates($filters) as $row) {
            $year = (int) $row->report_year;
            if (! isset($rows[$year])) {
                continue;
            }
            $rows[$year]['payment_count'] = (int) ($row->payment_count ?? 0);
            $rows[$year]['collection_payment_total'] = (float) ($row->collection_payment_total ?? 0);
            $rows[$year]['deposit_payment_total'] = (float) ($row->deposit_payment_total ?? 0);
            $rows[$year]['payment_total'] = (float) ($row->payment_total ?? 0);
        }

        return [
            'rows' => array_values($rows),
            'totals' => $this->sumYearlySummaryRows($rows),
            'cards' => $this->yearlySummaryCards($rows),
        ];
    }

    protected function buildPeriodicLoanSummary(array $filters, string $period): array
    {
        $rows = $this->periodicLoanSummaryRows($filters, $period);

        foreach ($this->periodicLoanAggregates($filters, $period) as $row) {
            $key = (string) $row->report_key;
            if (! isset($rows[$key])) {
                $rows[$key] = $this->emptyPeriodicLoanSummaryRow($key, $this->periodicLoanSummaryLabel($key, $period));
            }

            $rows[$key]['loan_count'] = (int) ($row->loan_count ?? 0);
            $rows[$key]['principal_total'] = (float) ($row->principal_total ?? 0);
            $rows[$key]['interest_total'] = (float) ($row->interest_total ?? 0);
            $rows[$key]['loan_total'] = (float) ($row->loan_total ?? 0);
            $rows[$key]['loan_paid_total'] = (float) ($row->loan_paid_total ?? 0);
            $rows[$key]['loan_balance_total'] = (float) ($row->loan_balance_total ?? 0);
            $rows[$key]['paid_customer_count'] = (int) ($row->paid_customer_count ?? 0);
            $rows[$key]['closed_count'] = (int) ($row->closed_count ?? 0);
            $rows[$key]['closed_principal_total'] = (float) ($row->closed_principal_total ?? 0);
            $rows[$key]['closed_interest_total'] = (float) ($row->closed_interest_total ?? 0);
            $rows[$key]['closed_loan_total'] = (float) ($row->closed_loan_total ?? 0);
            $rows[$key]['closed_paid_total'] = (float) ($row->closed_paid_total ?? 0);
            $rows[$key]['closed_balance_total'] = (float) ($row->closed_balance_total ?? 0);
            $rows[$key]['bad_count'] = (int) ($row->bad_count ?? 0);
            $rows[$key]['bad_principal_total'] = (float) ($row->bad_principal_total ?? 0);
            $rows[$key]['bad_interest_total'] = (float) ($row->bad_interest_total ?? 0);
            $rows[$key]['bad_loan_total'] = (float) ($row->bad_loan_total ?? 0);
            $rows[$key]['bad_paid_total'] = (float) ($row->bad_paid_total ?? 0);
            $rows[$key]['bad_balance_total'] = (float) ($row->bad_balance_total ?? 0);
        }

        foreach ($this->periodicScheduleAggregates($filters, $period) as $row) {
            $key = (string) $row->report_key;
            if (! isset($rows[$key])) {
                $rows[$key] = $this->emptyPeriodicLoanSummaryRow($key, $this->periodicLoanSummaryLabel($key, $period));
            }

            $rows[$key]['schedule_count'] = (int) ($row->schedule_count ?? 0);
            $rows[$key]['schedule_due_total'] = (float) ($row->schedule_due_total ?? 0);
            $rows[$key]['schedule_paid_total'] = (float) ($row->schedule_paid_total ?? 0);
            $rows[$key]['schedule_balance_total'] = (float) ($row->schedule_balance_total ?? 0);
            $rows[$key]['overdue_count'] = (int) ($row->overdue_count ?? 0);
            $rows[$key]['overdue_balance_total'] = (float) ($row->overdue_balance_total ?? 0);
        }

        foreach ($this->periodicPaymentAggregates($filters, $period) as $row) {
            $key = (string) $row->report_key;
            if (! isset($rows[$key])) {
                $rows[$key] = $this->emptyPeriodicLoanSummaryRow($key, $this->periodicLoanSummaryLabel($key, $period));
            }

            $rows[$key]['payment_count'] = (int) ($row->payment_count ?? 0);
            $rows[$key]['collection_payment_total'] = (float) ($row->collection_payment_total ?? 0);
            $rows[$key]['deposit_payment_total'] = (float) ($row->deposit_payment_total ?? 0);
            $rows[$key]['payment_total'] = (float) ($row->payment_total ?? 0);
        }

        $rows = array_filter($rows, fn ($row) => $this->periodicLoanSummaryRowHasData($row));

        ksort($rows);
        $totals = $this->sumPeriodicLoanSummaryRows($rows);

        return [
            'rows' => array_values($rows),
            'totals' => $totals,
            'cards' => [
                ['label' => 'Periods', 'value' => count($rows), 'type' => 'number', 'icon' => 'fa fa-calendar', 'tone' => 'teal'],
                ['label' => 'Loans', 'value' => $totals['loan_count'], 'type' => 'number', 'icon' => 'fa fa-file-text-o', 'tone' => 'blue'],
                ['label' => 'Principal', 'value' => $totals['principal_total'], 'type' => 'money', 'icon' => 'fa fa-money', 'tone' => 'green'],
                ['label' => 'Collected', 'value' => $totals['payment_total'], 'type' => 'money', 'icon' => 'fa fa-check-circle', 'tone' => 'purple'],
                ['label' => 'Balance', 'value' => $totals['loan_balance_total'], 'type' => 'money', 'icon' => 'fa fa-balance-scale', 'tone' => 'orange'],
                ['label' => 'Overdue', 'value' => $totals['overdue_count'], 'type' => 'number', 'icon' => 'fa fa-warning', 'tone' => 'red'],
            ],
        ];
    }

    protected function periodicLoanSummaryRows(array $filters, string $period): array
    {
        $rows = [];

        if ($period === 'monthly') {
            try {
                $start = \Carbon\Carbon::parse($filters['date_from'])->startOfMonth();
                $end = \Carbon\Carbon::parse($filters['date_to'])->startOfMonth();
            } catch (\Throwable $e) {
                $start = now()->startOfYear();
                $end = now()->startOfMonth();
            }

            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }

            while ($start->lte($end) && count($rows) < 312) {
                $key = $start->format('Y-m');
                $rows[$key] = $this->emptyPeriodicLoanSummaryRow($key, $this->periodicLoanSummaryLabel($key, $period));
                $start->addMonth();
            }

            return $rows;
        }

        try {
            $start = \Carbon\Carbon::parse($filters['date_from'])->startOfDay();
            $end = \Carbon\Carbon::parse($filters['date_to'])->startOfDay();
        } catch (\Throwable $e) {
            $start = now()->startOfDay();
            $end = now()->startOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        while ($start->lte($end) && count($rows) < 370) {
            $key = $start->toDateString();
            $rows[$key] = $this->emptyPeriodicLoanSummaryRow($key, $this->periodicLoanSummaryLabel($key, $period));
            $start->addDay();
        }

        return $rows;
    }

    protected function emptyPeriodicLoanSummaryRow(string $key, string $label): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'loan_count' => 0,
            'principal_total' => 0.0,
            'interest_total' => 0.0,
            'loan_total' => 0.0,
            'loan_paid_total' => 0.0,
            'loan_balance_total' => 0.0,
            'paid_customer_count' => 0,
            'closed_count' => 0,
            'closed_principal_total' => 0.0,
            'closed_interest_total' => 0.0,
            'closed_loan_total' => 0.0,
            'closed_paid_total' => 0.0,
            'closed_balance_total' => 0.0,
            'bad_count' => 0,
            'bad_principal_total' => 0.0,
            'bad_interest_total' => 0.0,
            'bad_loan_total' => 0.0,
            'bad_paid_total' => 0.0,
            'bad_balance_total' => 0.0,
            'schedule_count' => 0,
            'schedule_due_total' => 0.0,
            'schedule_paid_total' => 0.0,
            'schedule_balance_total' => 0.0,
            'payment_count' => 0,
            'collection_payment_total' => 0.0,
            'deposit_payment_total' => 0.0,
            'payment_total' => 0.0,
            'overdue_count' => 0,
            'overdue_balance_total' => 0.0,
        ];
    }

    protected function periodicLoanSummaryRowHasData(array $row): bool
    {
        foreach ([
            'loan_count', 'principal_total', 'interest_total', 'loan_total', 'loan_paid_total', 'loan_balance_total',
            'paid_customer_count',
            'closed_count', 'closed_principal_total', 'closed_interest_total', 'closed_loan_total', 'closed_paid_total', 'closed_balance_total',
            'bad_count', 'bad_principal_total', 'bad_interest_total', 'bad_loan_total', 'bad_paid_total', 'bad_balance_total',
            'schedule_count', 'schedule_due_total', 'schedule_paid_total', 'schedule_balance_total',
            'payment_count', 'collection_payment_total', 'deposit_payment_total', 'payment_total',
            'overdue_count', 'overdue_balance_total',
        ] as $field) {
            if ((float) ($row[$field] ?? 0) != 0) {
                return true;
            }
        }

        return false;
    }

    protected function sumPeriodicLoanSummaryRows(array $rows): array
    {
        $totals = $this->emptyPeriodicLoanSummaryRow('total', 'Total');

        foreach ($rows as $row) {
            foreach ($totals as $key => $value) {
                if (is_numeric($value) && isset($row[$key])) {
                    $totals[$key] += $row[$key];
                }
            }
        }

        return $totals;
    }

    protected function periodicLoanSummaryLabel(string $key, string $period): string
    {
        try {
            return $period === 'monthly'
                ? \Carbon\Carbon::createFromFormat('Y-m', $key)->format('M Y')
                : \Carbon\Carbon::parse($key)->format('d M Y');
        } catch (\Throwable $e) {
            return $key;
        }
    }

    protected function emptyYearlySummaryRow(int $year): array
    {
        return [
            'year' => $year,
            'loan_count' => 0,
            'principal_total' => 0.0,
            'interest_total' => 0.0,
            'loan_total' => 0.0,
            'loan_paid_total' => 0.0,
            'loan_balance_total' => 0.0,
            'paid_customer_count' => 0,
            'closed_count' => 0,
            'closed_principal_total' => 0.0,
            'closed_interest_total' => 0.0,
            'closed_loan_total' => 0.0,
            'closed_paid_total' => 0.0,
            'closed_balance_total' => 0.0,
            'bad_count' => 0,
            'bad_principal_total' => 0.0,
            'bad_interest_total' => 0.0,
            'bad_loan_total' => 0.0,
            'bad_paid_total' => 0.0,
            'bad_balance_total' => 0.0,
            'schedule_count' => 0,
            'schedule_principal_total' => 0.0,
            'schedule_interest_total' => 0.0,
            'schedule_due_total' => 0.0,
            'schedule_paid_total' => 0.0,
            'schedule_balance_total' => 0.0,
            'payment_count' => 0,
            'collection_payment_total' => 0.0,
            'deposit_payment_total' => 0.0,
            'payment_total' => 0.0,
            'overdue_count' => 0,
            'overdue_balance_total' => 0.0,
        ];
    }

    protected function yearlyLoanAggregates(array $filters)
    {
        $columns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $principalExpr = $this->coalesceSql('loans', 'l', ['principal_amount', 'financed_amount']);
        $interestExpr = $this->coalesceSql('loans', 'l', ['interest_amount']);
        $loanTotalExpr = $this->coalesceSql('loans', 'l', ['total_amount', 'total_payable_amount']);
        $paidExpr = $this->coalesceSql('loans', 'l', ['paid_amount']);
        $balanceExpr = $this->coalesceSql('loans', 'l', ['balance_amount']);
        $closedCondition = $this->closedLoanConditionSql('l');

        $joinCustomers = Schema::connection('mysql_loan')->hasTable('loan_customers')
            && in_array('customer_id', $columns, true)
            && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'id');
        $customerBlacklist = $joinCustomers && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'blacklist_status');
        $badCondition = $this->badLoanConditionSql('l', $columns, $customerBlacklist ? 'c' : null);

        $query = DB::connection('mysql_loan')->table('loans as l');
        if ($joinCustomers) {
            $query->leftJoin('loan_customers as c', 'c.id', '=', 'l.customer_id');
        }
        $this->applyYearlyLoanFilters($query, $filters, 'l', $dateColumn);

        return $query
            ->selectRaw('YEAR(l.'.$dateColumn.') as report_year')
            ->selectRaw('COUNT(*) as loan_count')
            ->selectRaw('COALESCE(SUM('.$principalExpr.'), 0) as principal_total')
            ->selectRaw('COALESCE(SUM('.$interestExpr.'), 0) as interest_total')
            ->selectRaw('COALESCE(SUM('.$loanTotalExpr.'), 0) as loan_total')
            ->selectRaw('COALESCE(SUM('.$paidExpr.'), 0) as loan_paid_total')
            ->selectRaw('COALESCE(SUM('.$balanceExpr.'), 0) as loan_balance_total')
            ->selectRaw('SUM(CASE WHEN '.$paidExpr.' > 0 THEN 1 ELSE 0 END) as paid_customer_count')
            ->selectRaw('SUM(CASE WHEN '.$closedCondition.' THEN 1 ELSE 0 END) as closed_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$principalExpr.' ELSE 0 END), 0) as closed_principal_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$interestExpr.' ELSE 0 END), 0) as closed_interest_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$loanTotalExpr.' ELSE 0 END), 0) as closed_loan_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$paidExpr.' ELSE 0 END), 0) as closed_paid_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$balanceExpr.' ELSE 0 END), 0) as closed_balance_total')
            ->selectRaw('SUM(CASE WHEN '.$badCondition.' THEN 1 ELSE 0 END) as bad_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$principalExpr.' ELSE 0 END), 0) as bad_principal_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$interestExpr.' ELSE 0 END), 0) as bad_interest_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$loanTotalExpr.' ELSE 0 END), 0) as bad_loan_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$paidExpr.' ELSE 0 END), 0) as bad_paid_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$balanceExpr.' ELSE 0 END), 0) as bad_balance_total')
            ->groupByRaw('YEAR(l.'.$dateColumn.')')
            ->get();
    }

    protected function yearlyScheduleAggregates(array $filters)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payment_schedules')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loan_payment_schedules');
        $dateColumn = $this->firstLoanReportColumn('loan_payment_schedules', ['due_date', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $balanceExpr = $this->coalesceSql('loan_payment_schedules', 's', ['balance_amount', 'amount_balance'], '0');
        $statusExpr = in_array('status', $columns, true) ? 'LOWER(COALESCE(s.status, ""))' : '""';
        $overdueCase = 'CASE WHEN ('.$balanceExpr.' > 0 AND ('.$statusExpr.' IN ("late", "overdue") OR s.'.$dateColumn.' < CURDATE())) THEN 1 ELSE 0 END';

        $query = DB::connection('mysql_loan')->table('loan_payment_schedules as s')
            ->join('loans as l', 'l.id', '=', 's.loan_id');
        $this->applyYearlyLoanFilters($query, $filters, 'l', 'loan_date', 's', $dateColumn);

        return $query
            ->selectRaw('YEAR(s.'.$dateColumn.') as report_year')
            ->selectRaw('COUNT(*) as schedule_count')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['principal_amount', 'principal_due', 'principal', 'installment_value']).' as schedule_principal_total')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['interest_amount', 'interest_due', 'interest', 'benefit_value']).' as schedule_interest_total')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['schedule_amount', 'amount_due', 'total']).' as schedule_due_total')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['paid_amount', 'amount_paid', 'paid_value']).' as schedule_paid_total')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['balance_amount', 'amount_balance']).' as schedule_balance_total')
            ->selectRaw('SUM('.$overdueCase.') as overdue_count')
            ->selectRaw('SUM(CASE WHEN '.$overdueCase.' = 1 THEN '.$balanceExpr.' ELSE 0 END) as overdue_balance_total')
            ->groupByRaw('YEAR(s.'.$dateColumn.')')
            ->get();
    }

    protected function yearlyPaymentAggregates(array $filters)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payments')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loan_payments');
        $dateColumn = $this->firstLoanReportColumn('loan_payments', ['paid_date', 'paid_on', 'payment_date', 'paid_at', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $typeExpr = in_array('payment_type', $columns, true) ? 'LOWER(COALESCE(p.payment_type, ""))' : '""';
        $collectionCase = 'CASE WHEN '.$typeExpr.' = "monthly" OR ('.$typeExpr.' = "" AND '.(in_array('schedule_id', $columns, true) ? 'p.schedule_id IS NOT NULL' : '0').') THEN '.$amountExpr.' ELSE 0 END';
        $depositCase = 'CASE WHEN '.$typeExpr.' IN ("loan", "initial", "down_payment", "downpayment", "deposit") OR ('.$typeExpr.' = "" AND '.(in_array('schedule_id', $columns, true) ? 'p.schedule_id IS NULL' : '0').') THEN '.$amountExpr.' ELSE 0 END';

        $query = DB::connection('mysql_loan')->table('loan_payments as p')
            ->join('loans as l', 'l.id', '=', 'p.loan_id');
        $this->applyYearlyLoanFilters($query, $filters, 'l', 'loan_date', 'p', $dateColumn);

        if (in_array('status', $columns, true)) {
            $query->whereRaw('LOWER(COALESCE(p.status, "")) NOT IN ("cancelled", "canceled", "failed", "void", "deleted", "rejected")');
        }

        return $query
            ->selectRaw('YEAR(p.'.$dateColumn.') as report_year')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('SUM('.$collectionCase.') as collection_payment_total')
            ->selectRaw('SUM('.$depositCase.') as deposit_payment_total')
            ->selectRaw('SUM('.$amountExpr.') as payment_total')
            ->groupByRaw('YEAR(p.'.$dateColumn.')')
            ->get();
    }

    protected function monthlyLoanAggregates(array $filters)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $principalExpr = $this->coalesceSql('loans', 'l', ['principal_amount', 'financed_amount']);
        $interestExpr = $this->coalesceSql('loans', 'l', ['interest_amount']);
        $loanTotalExpr = $this->coalesceSql('loans', 'l', ['total_amount', 'total_payable_amount']);
        $paidExpr = $this->coalesceSql('loans', 'l', ['paid_amount']);
        $balanceExpr = $this->coalesceSql('loans', 'l', ['balance_amount']);
        $closedCondition = $this->closedLoanConditionSql('l');

        $joinCustomers = Schema::connection('mysql_loan')->hasTable('loan_customers')
            && in_array('customer_id', $columns, true)
            && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'id');
        $customerBlacklist = $joinCustomers && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'blacklist_status');
        $badCondition = $this->badLoanConditionSql('l', $columns, $customerBlacklist ? 'c' : null);

        $query = DB::connection('mysql_loan')->table('loans as l');
        if ($joinCustomers) {
            $query->leftJoin('loan_customers as c', 'c.id', '=', 'l.customer_id');
        }
        $this->applyYearlyLoanFilters($query, $filters, 'l', $dateColumn);

        return $query
            ->selectRaw('YEAR(l.'.$dateColumn.') as report_year')
            ->selectRaw('MONTH(l.'.$dateColumn.') as report_month')
            ->selectRaw('COUNT(*) as loan_count')
            ->selectRaw('COALESCE(SUM('.$principalExpr.'), 0) as principal_total')
            ->selectRaw('COALESCE(SUM('.$interestExpr.'), 0) as interest_total')
            ->selectRaw('COALESCE(SUM('.$loanTotalExpr.'), 0) as loan_total')
            ->selectRaw('COALESCE(SUM('.$paidExpr.'), 0) as loan_paid_total')
            ->selectRaw('COALESCE(SUM('.$balanceExpr.'), 0) as loan_balance_total')
            ->selectRaw('SUM(CASE WHEN '.$paidExpr.' > 0 THEN 1 ELSE 0 END) as paid_customer_count')
            ->selectRaw('SUM(CASE WHEN '.$closedCondition.' THEN 1 ELSE 0 END) as closed_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$principalExpr.' ELSE 0 END), 0) as closed_principal_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$interestExpr.' ELSE 0 END), 0) as closed_interest_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$loanTotalExpr.' ELSE 0 END), 0) as closed_loan_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$paidExpr.' ELSE 0 END), 0) as closed_paid_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$balanceExpr.' ELSE 0 END), 0) as closed_balance_total')
            ->selectRaw('SUM(CASE WHEN '.$badCondition.' THEN 1 ELSE 0 END) as bad_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$principalExpr.' ELSE 0 END), 0) as bad_principal_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$interestExpr.' ELSE 0 END), 0) as bad_interest_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$loanTotalExpr.' ELSE 0 END), 0) as bad_loan_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$paidExpr.' ELSE 0 END), 0) as bad_paid_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$balanceExpr.' ELSE 0 END), 0) as bad_balance_total')
            ->groupByRaw('YEAR(l.'.$dateColumn.'), MONTH(l.'.$dateColumn.')')
            ->get();
    }

    protected function monthlyPaymentAggregates(array $filters)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payments')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loan_payments');
        $dateColumn = $this->firstLoanReportColumn('loan_payments', ['paid_date', 'paid_on', 'payment_date', 'paid_at', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $typeExpr = in_array('payment_type', $columns, true) ? 'LOWER(COALESCE(p.payment_type, ""))' : '""';
        $collectionCase = 'CASE WHEN '.$typeExpr.' = "monthly" OR ('.$typeExpr.' = "" AND '.(in_array('schedule_id', $columns, true) ? 'p.schedule_id IS NOT NULL' : '0').') THEN '.$amountExpr.' ELSE 0 END';
        $depositCase = 'CASE WHEN '.$typeExpr.' IN ("loan", "initial", "down_payment", "downpayment", "deposit") OR ('.$typeExpr.' = "" AND '.(in_array('schedule_id', $columns, true) ? 'p.schedule_id IS NULL' : '0').') THEN '.$amountExpr.' ELSE 0 END';
        $penaltyExpr = $this->coalesceSql('loan_payments', 'p', ['penalty_amount'], '0');
        $discountExpr = $this->coalesceSql('loan_payments', 'p', ['discount_amount'], '0');

        $query = DB::connection('mysql_loan')->table('loan_payments as p')
            ->join('loans as l', 'l.id', '=', 'p.loan_id');
        $this->applyYearlyLoanFilters($query, $filters, 'l', 'loan_date', 'p', $dateColumn);

        if (in_array('status', $columns, true)) {
            $query->whereRaw('LOWER(COALESCE(p.status, "")) NOT IN ("cancelled", "canceled", "failed", "void", "deleted", "rejected")');
        }

        return $query
            ->selectRaw('YEAR(p.'.$dateColumn.') as report_year')
            ->selectRaw('MONTH(p.'.$dateColumn.') as report_month')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('SUM('.$collectionCase.') as collection_payment_total')
            ->selectRaw('SUM('.$depositCase.') as deposit_payment_total')
            ->selectRaw('SUM('.$amountExpr.') as payment_total')
            ->selectRaw('SUM('.$penaltyExpr.') as penalty_total')
            ->selectRaw('SUM('.$discountExpr.') as discount_total')
            ->groupByRaw('YEAR(p.'.$dateColumn.'), MONTH(p.'.$dateColumn.')')
            ->get();
    }

    protected function periodicLoanAggregates(array $filters, string $period)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $principalExpr = $this->coalesceSql('loans', 'l', ['principal_amount', 'financed_amount']);
        $interestExpr = $this->coalesceSql('loans', 'l', ['interest_amount']);
        $loanTotalExpr = $this->coalesceSql('loans', 'l', ['total_amount', 'total_payable_amount', 'principal_amount']);
        $paidExpr = $this->coalesceSql('loans', 'l', ['paid_amount', 'total_paid', 'down_payment']);
        $balanceExpr = $this->coalesceSql('loans', 'l', ['balance_amount', 'amount_balance']);
        $closedCondition = $this->closedLoanConditionSql('l');
        $periodExpr = $this->periodicReportSql('l', $dateColumn, $period);

        $joinCustomers = Schema::connection('mysql_loan')->hasTable('loan_customers')
            && in_array('customer_id', $columns, true)
            && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'id');
        $customerBlacklist = $joinCustomers && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'blacklist_status');
        $badCondition = $this->badLoanConditionSql('l', $columns, $customerBlacklist ? 'c' : null);

        $query = DB::connection('mysql_loan')->table('loans as l');
        if ($joinCustomers) {
            $query->leftJoin('loan_customers as c', 'c.id', '=', 'l.customer_id');
        }
        $this->applyPeriodicLoanFilters($query, $filters, $period, 'l', $dateColumn);

        return $query
            ->selectRaw($periodExpr.' as report_key')
            ->selectRaw('COUNT(*) as loan_count')
            ->selectRaw('COALESCE(SUM('.$principalExpr.'), 0) as principal_total')
            ->selectRaw('COALESCE(SUM('.$interestExpr.'), 0) as interest_total')
            ->selectRaw('COALESCE(SUM('.$loanTotalExpr.'), 0) as loan_total')
            ->selectRaw('COALESCE(SUM('.$paidExpr.'), 0) as loan_paid_total')
            ->selectRaw('COALESCE(SUM('.$balanceExpr.'), 0) as loan_balance_total')
            ->selectRaw('SUM(CASE WHEN '.$paidExpr.' > 0 THEN 1 ELSE 0 END) as paid_customer_count')
            ->selectRaw('SUM(CASE WHEN '.$closedCondition.' THEN 1 ELSE 0 END) as closed_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$principalExpr.' ELSE 0 END), 0) as closed_principal_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$interestExpr.' ELSE 0 END), 0) as closed_interest_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$loanTotalExpr.' ELSE 0 END), 0) as closed_loan_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$paidExpr.' ELSE 0 END), 0) as closed_paid_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$closedCondition.' THEN '.$balanceExpr.' ELSE 0 END), 0) as closed_balance_total')
            ->selectRaw('SUM(CASE WHEN '.$badCondition.' THEN 1 ELSE 0 END) as bad_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$principalExpr.' ELSE 0 END), 0) as bad_principal_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$interestExpr.' ELSE 0 END), 0) as bad_interest_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$loanTotalExpr.' ELSE 0 END), 0) as bad_loan_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$paidExpr.' ELSE 0 END), 0) as bad_paid_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.$badCondition.' THEN '.$balanceExpr.' ELSE 0 END), 0) as bad_balance_total')
            ->groupByRaw($periodExpr)
            ->get();
    }

    protected function periodicScheduleAggregates(array $filters, string $period)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payment_schedules')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loan_payment_schedules');
        $dateColumn = $this->firstLoanReportColumn('loan_payment_schedules', ['due_date', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $balanceExpr = $this->coalesceSql('loan_payment_schedules', 's', ['balance_amount', 'amount_balance'], '0');
        $statusExpr = in_array('status', $columns, true) ? 'LOWER(COALESCE(s.status, ""))' : '""';
        $overdueCase = 'CASE WHEN ('.$balanceExpr.' > 0 AND ('.$statusExpr.' IN ("late", "overdue") OR s.'.$dateColumn.' < CURDATE())) THEN 1 ELSE 0 END';
        $periodExpr = $this->periodicReportSql('s', $dateColumn, $period);

        $query = DB::connection('mysql_loan')->table('loan_payment_schedules as s')
            ->join('loans as l', 'l.id', '=', 's.loan_id');
        $loanDateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'created_at']) ?: 'id';
        $this->applyPeriodicLoanFilters($query, $filters, $period, 'l', $loanDateColumn, 's', $dateColumn);

        return $query
            ->selectRaw($periodExpr.' as report_key')
            ->selectRaw('COUNT(*) as schedule_count')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['schedule_amount', 'amount_due', 'total']).' as schedule_due_total')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['paid_amount', 'amount_paid', 'paid_value']).' as schedule_paid_total')
            ->selectRaw($this->sumSql('loan_payment_schedules', 's', ['balance_amount', 'amount_balance']).' as schedule_balance_total')
            ->selectRaw('SUM('.$overdueCase.') as overdue_count')
            ->selectRaw('SUM(CASE WHEN '.$overdueCase.' = 1 THEN '.$balanceExpr.' ELSE 0 END) as overdue_balance_total')
            ->groupByRaw($periodExpr)
            ->get();
    }

    protected function periodicPaymentAggregates(array $filters, string $period)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payments')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loan_payments');
        $dateColumn = $this->firstLoanReportColumn('loan_payments', ['paid_date', 'paid_on', 'payment_date', 'paid_at', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'p', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $typeExpr = in_array('payment_type', $columns, true) ? 'LOWER(COALESCE(p.payment_type, ""))' : '""';
        $collectionCase = 'CASE WHEN '.$typeExpr.' = "monthly" OR ('.$typeExpr.' = "" AND '.(in_array('schedule_id', $columns, true) ? 'p.schedule_id IS NOT NULL' : '0').') THEN '.$amountExpr.' ELSE 0 END';
        $depositCase = 'CASE WHEN '.$typeExpr.' IN ("loan", "initial", "down_payment", "downpayment", "deposit") OR ('.$typeExpr.' = "" AND '.(in_array('schedule_id', $columns, true) ? 'p.schedule_id IS NULL' : '0').') THEN '.$amountExpr.' ELSE 0 END';
        $periodExpr = $this->periodicReportSql('p', $dateColumn, $period);

        $query = DB::connection('mysql_loan')->table('loan_payments as p')
            ->join('loans as l', 'l.id', '=', 'p.loan_id');
        $loanDateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'created_at']) ?: 'id';
        $this->applyPeriodicLoanFilters($query, $filters, $period, 'l', $loanDateColumn, 'p', $dateColumn);

        if (in_array('status', $columns, true)) {
            $query->whereRaw('LOWER(COALESCE(p.status, "")) NOT IN ("cancelled", "canceled", "failed", "void", "deleted", "rejected")');
        }

        return $query
            ->selectRaw($periodExpr.' as report_key')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('SUM('.$collectionCase.') as collection_payment_total')
            ->selectRaw('SUM('.$depositCase.') as deposit_payment_total')
            ->selectRaw('SUM('.$amountExpr.') as payment_total')
            ->groupByRaw($periodExpr)
            ->get();
    }

    protected function periodicReportSql(string $alias, string $dateColumn, string $period): string
    {
        return $period === 'monthly'
            ? 'DATE_FORMAT('.$alias.'.'.$dateColumn.', "%Y-%m")'
            : 'DATE('.$alias.'.'.$dateColumn.')';
    }

    protected function applyPeriodicLoanFilters($query, array $filters, string $period, string $loanAlias, string $loanDateColumn, ?string $dataAlias = null, ?string $dataDateColumn = null): void
    {
        if ($period === 'monthly') {
            $this->applyYearlyLoanFilters($query, $filters, $loanAlias, $loanDateColumn, $dataAlias, $dataDateColumn);
            return;
        }

        $dateAlias = $dataAlias ?: $loanAlias;
        $dateColumn = $dataDateColumn ?: $loanDateColumn;
        $query->whereDate($dateAlias.'.'.$dateColumn, '>=', $filters['date_from'])
            ->whereDate($dateAlias.'.'.$dateColumn, '<=', $filters['date_to']);

        if (Schema::connection('mysql_loan')->hasColumn('loans', 'deleted_at')) {
            $query->whereNull($loanAlias.'.deleted_at');
        }
        if ($dataAlias && Schema::connection('mysql_loan')->hasColumn($dataAlias === 's' ? 'loan_payment_schedules' : 'loan_payments', 'deleted_at')) {
            $query->whereNull($dataAlias.'.deleted_at');
        }

        $this->applyDashboardLoanLocationAndSearchFilters($query, $filters, $loanAlias);
    }

    protected function applyYearlyLoanFilters($query, array $filters, string $loanAlias, string $loanDateColumn, ?string $dataAlias = null, ?string $dataDateColumn = null): void
    {
        $dateAlias = $dataAlias ?: $loanAlias;
        $dateColumn = $dataDateColumn ?: $loanDateColumn;

        $query->whereDate($dateAlias.'.'.$dateColumn, '>=', $filters['date_from'])
            ->whereDate($dateAlias.'.'.$dateColumn, '<=', $filters['date_to']);

        if (Schema::connection('mysql_loan')->hasColumn('loans', 'deleted_at')) {
            $query->whereNull($loanAlias.'.deleted_at');
        }
        if ($dataAlias && Schema::connection('mysql_loan')->hasColumn($dataAlias === 's' ? 'loan_payment_schedules' : 'loan_payments', 'deleted_at')) {
            $query->whereNull($dataAlias.'.deleted_at');
        }

        if (! empty($filters['location_id'])) {
            $locationFilter = $this->parseYearlyLocationFilter((string) $filters['location_id']);
            if (! empty($locationFilter)) {
                $canFilterLocation =
                    (! empty($locationFilter['loan_location_id']) && Schema::connection('mysql_loan')->hasColumn('loans', 'business_location_id'))
                    || (! empty($locationFilter['main_location_id']) && Schema::connection('mysql_loan')->hasColumn('loans', 'main_location_id'))
                    || (! empty($locationFilter['legacy_id']) && (
                        Schema::connection('mysql_loan')->hasColumn('loans', 'business_location_id')
                        || Schema::connection('mysql_loan')->hasColumn('loans', 'main_location_id')
                    ))
                    || (! empty($locationFilter['name']) && (
                        Schema::connection('mysql_loan')->hasColumn('loans', 'location_name_snapshot')
                        || Schema::connection('mysql_loan')->hasColumn('loans', 'business_location_name_snapshot')
                    ));

                if (! $canFilterLocation) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->where(function ($where) use ($loanAlias, $locationFilter) {
                    if (! empty($locationFilter['loan_location_id']) && Schema::connection('mysql_loan')->hasColumn('loans', 'business_location_id')) {
                        $where->orWhere($loanAlias.'.business_location_id', (int) $locationFilter['loan_location_id']);
                    }
                    if (! empty($locationFilter['main_location_id']) && Schema::connection('mysql_loan')->hasColumn('loans', 'main_location_id')) {
                        $where->orWhere($loanAlias.'.main_location_id', (int) $locationFilter['main_location_id']);
                    }
                    if (! empty($locationFilter['legacy_id'])) {
                        if (Schema::connection('mysql_loan')->hasColumn('loans', 'business_location_id')) {
                            $where->orWhere($loanAlias.'.business_location_id', (int) $locationFilter['legacy_id']);
                        }
                        if (Schema::connection('mysql_loan')->hasColumn('loans', 'main_location_id')) {
                            $where->orWhere($loanAlias.'.main_location_id', (int) $locationFilter['legacy_id']);
                        }
                    }
                    if (! empty($locationFilter['name'])) {
                        foreach (['location_name_snapshot', 'business_location_name_snapshot'] as $column) {
                            if (Schema::connection('mysql_loan')->hasColumn('loans', $column)) {
                                $where->orWhere($loanAlias.'.'.$column, $locationFilter['name']);
                            }
                        }
                    }
                });
            }
        }

        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';
            $searchColumns = array_values(array_filter(['loan_number', 'source_invoice_no', 'customer_name_snapshot', 'customer_phone_snapshot'], fn ($column) => Schema::connection('mysql_loan')->hasColumn('loans', $column)));
            if (! empty($searchColumns)) {
                $query->where(function ($where) use ($loanAlias, $like, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $where->orWhere($loanAlias.'.'.$column, 'like', $like);
                    }
                });
            }
        }
    }

    protected function firstLoanReportColumn(string $table, array $candidates, ?array $columns = null): ?string
    {
        $columns = $columns ?: Schema::connection('mysql_loan')->getColumnListing($table);

        foreach ($candidates as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }

        return null;
    }

    protected function coalesceSql(string $table, string $alias, array $columns, string $fallback = '0'): string
    {
        $available = Schema::connection('mysql_loan')->hasTable($table)
            ? Schema::connection('mysql_loan')->getColumnListing($table)
            : [];
        $parts = [];
        foreach ($columns as $column) {
            if (in_array($column, $available, true)) {
                $parts[] = $alias.'.'.$column;
            }
        }

        return $parts ? 'COALESCE('.implode(', ', $parts).', '.$fallback.')' : $fallback;
    }

    protected function sumSql(string $table, string $alias, array $columns): string
    {
        return 'COALESCE(SUM('.$this->coalesceSql($table, $alias, $columns).'), 0)';
    }

    protected function closedLoanCountSql(string $alias): string
    {
        return 'SUM(CASE WHEN '.$this->closedLoanConditionSql($alias).' THEN 1 ELSE 0 END)';
    }

    protected function closedLoanConditionSql(string $alias): string
    {
        $columns = Schema::connection('mysql_loan')->hasTable('loans')
            ? Schema::connection('mysql_loan')->getColumnListing('loans')
            : [];

        if (empty($columns)) {
            return '0';
        }

        $conditions = [];

        if (in_array('status', $columns, true)) {
            $conditions[] = 'LOWER(COALESCE('.$alias.'.status, "")) IN ("completed", "closed", "paid", "paid_off", "pay off", "payoff")';
        }

        $paidExpr = $this->coalesceSql('loans', $alias, ['paid_amount']);
        $balanceExpr = $this->coalesceSql('loans', $alias, ['balance_amount']);
        if (in_array('paid_amount', $columns, true) && in_array('balance_amount', $columns, true)) {
            $conditions[] = '('.$paidExpr.' > 0 AND '.$balanceExpr.' <= 0)';
        }

        $totalExpr = $this->coalesceSql('loans', $alias, ['total_amount', 'total_payable_amount', 'principal_amount']);
        $paymentTotalExpr = $this->loanPaymentTotalSubquerySql($alias);
        if ($paymentTotalExpr !== null) {
            $conditions[] = '('.$totalExpr.' > 0 AND '.$paymentTotalExpr.' >= '.$totalExpr.')';
        }

        return $conditions ? '('.implode(' OR ', $conditions).')' : '0';
    }

    protected function loanPaymentTotalSubquerySql(string $loanAlias): ?string
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_payments')
            || ! Schema::connection('mysql_loan')->hasColumn('loan_payments', 'loan_id')) {
            return null;
        }

        $amountExpr = $this->coalesceSql('loan_payments', 'lp', ['total_paid_base', 'total_paid', 'amount_base', 'amount'], '0');
        $where = ['lp.loan_id = '.$loanAlias.'.id'];

        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'status')) {
            $where[] = 'LOWER(COALESCE(lp.status, "")) NOT IN ("cancelled", "canceled", "failed", "void", "deleted", "rejected")';
        }
        if (Schema::connection('mysql_loan')->hasColumn('loan_payments', 'deleted_at')) {
            $where[] = 'lp.deleted_at IS NULL';
        }

        return '(SELECT COALESCE(SUM('.$amountExpr.'), 0) FROM loan_payments lp WHERE '.implode(' AND ', $where).')';
    }

    protected function badLoanConditionSql(string $alias, array $columns, ?string $customerAlias = null): string
    {
        $conditions = [];

        if (in_array('blacklist_status', $columns, true)) {
            $conditions[] = 'COALESCE('.$alias.'.blacklist_status, 0) = 1';
        }
        if (in_array('blacklisted_at', $columns, true)) {
            $conditions[] = $alias.'.blacklisted_at IS NOT NULL';
        }
        if (in_array('written_off_at', $columns, true)) {
            $conditions[] = $alias.'.written_off_at IS NOT NULL';
        }
        if (in_array('collection_status', $columns, true)) {
            $conditions[] = 'LOWER(COALESCE('.$alias.'.collection_status, "")) IN ("blacklisted", "delinquent", "legal", "debt_collection", "recovery", "write_off", "written_off")';
        }
        if (in_array('risk_level', $columns, true)) {
            $conditions[] = 'LOWER(COALESCE('.$alias.'.risk_level, "")) IN ("high", "high_risk", "critical", "fraud_risk", "hard_skip")';
        }
        if ($customerAlias) {
            $conditions[] = 'COALESCE('.$customerAlias.'.blacklist_status, 0) = 1';
        }

        return $conditions ? '('.implode(' OR ', $conditions).')' : '0';
    }

    protected function sumYearlySummaryRows(array $rows): array
    {
        $totals = $this->emptyYearlySummaryRow(0);
        $totals['year'] = 'Total';
        foreach ($rows as $row) {
            foreach ($totals as $key => $value) {
                if ($key === 'year') {
                    continue;
                }
                $totals[$key] += $row[$key] ?? 0;
            }
        }

        return $totals;
    }

    protected function yearlySummaryCards(array $rows): array
    {
        $totals = $this->sumYearlySummaryRows($rows);

        return [
            ['label' => $this->loanReportText('Registered Installment Customers', 'អតិថិជនចុះឈ្មោះរំលស់'), 'value' => number_format((float) $totals['loan_count'], 0), 'icon' => 'fa fa-users', 'tone' => 'teal'],
            ['label' => $this->loanReportText('Registered Principal', 'ប្រាក់ដើមចុះឈ្មោះ'), 'value' => '$'.number_format((float) $totals['principal_total'], 2), 'icon' => 'fa fa-credit-card', 'tone' => 'blue'],
            ['label' => $this->loanReportText('Paid Total Customers', 'អតិថិជនបានបង់ទូរទៅ'), 'value' => number_format((float) $totals['paid_customer_count'], 0), 'icon' => 'fa fa-check-circle-o', 'tone' => 'green'],
            ['label' => $this->loanReportText('Paid Off Customers', 'អតិថិជនបានបង់ផ្ដាច់'), 'value' => number_format((float) $totals['closed_count'], 0), 'icon' => 'fa fa-check-square-o', 'tone' => 'orange'],
            ['label' => $this->loanReportText('Bad Customers', 'អតិថិជនរំលស់ខូច'), 'value' => number_format((float) $totals['bad_count'], 0), 'icon' => 'fa fa-warning', 'tone' => 'red'],
            ['label' => $this->loanReportText('Bad Balance', 'សមតុល្យអតិថិជនខូច'), 'value' => '$'.number_format((float) $totals['bad_balance_total'], 2), 'icon' => 'fa fa-line-chart', 'tone' => 'purple'],
        ];
    }

    protected function adminLoanRows(array $rows): array
    {
        return array_map(function (array $row) {
            $activeCount = max(0, (int) $row['loan_count'] - (int) $row['closed_count'] - (int) $row['bad_count']);

            return [
                'year' => $row['year'],
                'registered' => [
                    'customers' => (int) $row['loan_count'],
                    'loan_amount' => (float) $row['principal_total'],
                    'interest' => (float) $row['interest_total'],
                    'total_interest' => (float) $row['loan_total'],
                ],
                'general_paid' => [
                    'principal_paid' => (float) $row['collection_payment_total'],
                    'interest_paid' => (float) $row['deposit_payment_total'],
                    'interest_deducted' => 0.0,
                    'penalties_received' => max(0, (float) $row['payment_total'] - (float) $row['collection_payment_total'] - (float) $row['deposit_payment_total']),
                ],
                'paid_off' => [
                    'settled_customers' => (int) $row['closed_count'],
                    'settled_principal' => (float) $row['closed_principal_total'],
                    'settled_interest' => (float) $row['closed_interest_total'],
                    'settled_penalties' => 0.0,
                    'prepayment_discount' => max(0, (float) $row['closed_balance_total']),
                ],
                'active' => [
                    'active_customers' => $activeCount,
                    'active_principal' => max(0, (float) $row['principal_total'] - (float) $row['closed_principal_total'] - (float) $row['bad_principal_total']),
                    'active_monthly_interest' => max(0, (float) $row['interest_total'] - (float) $row['closed_interest_total'] - (float) $row['bad_interest_total']),
                    'active_total_interest' => max(0, (float) $row['loan_balance_total'] - (float) $row['bad_balance_total']),
                ],
                'bad_debt' => [
                    'bad_customers' => (int) $row['bad_count'],
                    'bad_principal' => (float) $row['bad_principal_total'],
                    'bad_interest' => (float) $row['bad_interest_total'],
                    'bad_total' => (float) $row['bad_balance_total'],
                ],
            ];
        }, $rows);
    }

    protected function adminLoanMonthlyRows(array $filters): array
    {
        $rows = [];
        try {
            $start = \Carbon\Carbon::parse($filters['date_from'])->startOfMonth();
            $end = \Carbon\Carbon::parse($filters['date_to'])->startOfMonth();
        } catch (\Throwable $e) {
            $start = \Carbon\Carbon::create((int) $filters['start_year'], 1, 1)->startOfMonth();
            $end = \Carbon\Carbon::create((int) $filters['end_year'], 12, 1)->startOfMonth();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        while ($start->lte($end) && count($rows) < 312) {
            $rows[$this->adminLoanMonthKey((int) $start->format('Y'), (int) $start->format('m'))] = $this->emptyAdminLoanMonthlyRow((int) $start->format('Y'), (int) $start->format('m'));
            $start->addMonth();
        }

        foreach ($this->monthlyLoanAggregates($filters) as $row) {
            $year = (int) $row->report_year;
            $month = (int) $row->report_month;
            $key = $this->adminLoanMonthKey($year, $month);
            if (! isset($rows[$key])) {
                continue;
            }

            $activeCount = max(0, (int) ($row->loan_count ?? 0) - (int) ($row->closed_count ?? 0) - (int) ($row->bad_count ?? 0));
            $rows[$key]['registered']['customers'] = (int) ($row->loan_count ?? 0);
            $rows[$key]['registered']['loan_amount'] = (float) ($row->principal_total ?? 0);
            $rows[$key]['registered']['interest'] = (float) ($row->interest_total ?? 0);
            $rows[$key]['registered']['total_interest'] = (float) ($row->loan_total ?? 0);
            $rows[$key]['paid_off']['settled_customers'] = (int) ($row->closed_count ?? 0);
            $rows[$key]['paid_off']['settled_principal'] = (float) ($row->closed_principal_total ?? 0);
            $rows[$key]['paid_off']['settled_interest'] = (float) ($row->closed_interest_total ?? 0);
            $rows[$key]['paid_off']['prepayment_discount'] = max(0, (float) ($row->closed_balance_total ?? 0));
            $rows[$key]['active']['active_customers'] = $activeCount;
            $rows[$key]['active']['active_principal'] = max(0, (float) ($row->principal_total ?? 0) - (float) ($row->closed_principal_total ?? 0) - (float) ($row->bad_principal_total ?? 0));
            $rows[$key]['active']['active_monthly_interest'] = max(0, (float) ($row->interest_total ?? 0) - (float) ($row->closed_interest_total ?? 0) - (float) ($row->bad_interest_total ?? 0));
            $rows[$key]['active']['active_total_interest'] = max(0, (float) ($row->loan_balance_total ?? 0) - (float) ($row->bad_balance_total ?? 0));
            $rows[$key]['bad_debt']['bad_customers'] = (int) ($row->bad_count ?? 0);
            $rows[$key]['bad_debt']['bad_principal'] = (float) ($row->bad_principal_total ?? 0);
            $rows[$key]['bad_debt']['bad_interest'] = (float) ($row->bad_interest_total ?? 0);
            $rows[$key]['bad_debt']['bad_total'] = (float) ($row->bad_balance_total ?? 0);
        }

        foreach ($this->monthlyPaymentAggregates($filters) as $row) {
            $key = $this->adminLoanMonthKey((int) $row->report_year, (int) $row->report_month);
            if (! isset($rows[$key])) {
                continue;
            }

            $collectionTotal = (float) ($row->collection_payment_total ?? 0);
            $depositTotal = (float) ($row->deposit_payment_total ?? 0);
            $paymentTotal = (float) ($row->payment_total ?? 0);
            $rows[$key]['general_paid']['principal_paid'] = $collectionTotal;
            $rows[$key]['general_paid']['interest_paid'] = $depositTotal;
            $rows[$key]['general_paid']['interest_deducted'] = (float) ($row->discount_total ?? 0);
            $rows[$key]['general_paid']['penalties_received'] = max(0, (float) ($row->penalty_total ?? 0) ?: $paymentTotal - $collectionTotal - $depositTotal);
            $rows[$key]['paid_off']['settled_penalties'] = (float) ($row->penalty_total ?? 0);
        }

        return array_values($rows);
    }

    protected function emptyAdminLoanMonthlyRow(int $year, int $month): array
    {
        return [
            'id' => $this->adminLoanMonthKey($year, $month),
            'year' => $year,
            'month' => $month,
            'registered' => ['customers' => 0, 'loan_amount' => 0.0, 'interest' => 0.0, 'total_interest' => 0.0],
            'general_paid' => ['principal_paid' => 0.0, 'interest_paid' => 0.0, 'interest_deducted' => 0.0, 'penalties_received' => 0.0],
            'paid_off' => ['settled_customers' => 0, 'settled_principal' => 0.0, 'settled_interest' => 0.0, 'settled_penalties' => 0.0, 'prepayment_discount' => 0.0],
            'active' => ['active_customers' => 0, 'active_principal' => 0.0, 'active_monthly_interest' => 0.0, 'active_total_interest' => 0.0],
            'bad_debt' => ['bad_customers' => 0, 'bad_principal' => 0.0, 'bad_interest' => 0.0, 'bad_total' => 0.0],
        ];
    }

    protected function adminLoanMonthKey(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    protected function adminLoanExportRows(array $rows): array
    {
        return array_map(function (array $row) {
            return [
                'Year' => $row['year'],
                'Registered Customers' => $row['registered']['customers'] ?? 0,
                'Registered Principal' => $row['registered']['loan_amount'] ?? 0,
                'Registered Monthly Interest' => $row['registered']['interest'] ?? 0,
                'Registered Total Interest' => $row['registered']['total_interest'] ?? 0,
                'General Paid Principal' => $row['general_paid']['principal_paid'] ?? 0,
                'General Paid Interest' => $row['general_paid']['interest_paid'] ?? 0,
                'General Paid Interest Deducted' => $row['general_paid']['interest_deducted'] ?? 0,
                'General Paid Penalty' => $row['general_paid']['penalties_received'] ?? 0,
                'Paid Off Customers' => $row['paid_off']['settled_customers'] ?? 0,
                'Paid Off Principal' => $row['paid_off']['settled_principal'] ?? 0,
                'Paid Off Interest' => $row['paid_off']['settled_interest'] ?? 0,
                'Paid Off Penalties' => $row['paid_off']['settled_penalties'] ?? 0,
                'Prepayment Discount' => $row['paid_off']['prepayment_discount'] ?? 0,
                'Active / Ongoing Customers' => $row['active']['active_customers'] ?? 0,
                'Active / Ongoing Principal' => $row['active']['active_principal'] ?? 0,
                'Active / Ongoing Monthly Interest' => $row['active']['active_monthly_interest'] ?? 0,
                'Active / Ongoing Total Interest' => $row['active']['active_total_interest'] ?? 0,
                'Bad Customers' => $row['bad_debt']['bad_customers'] ?? 0,
                'Bad Principal' => $row['bad_debt']['bad_principal'] ?? 0,
                'Bad Interest' => $row['bad_debt']['bad_interest'] ?? 0,
                'Bad Total' => $row['bad_debt']['bad_total'] ?? 0,
            ];
        }, $rows);
    }

    protected function adminLoanTotals(array $rows): array
    {
        $totals = [
            'registered' => ['customers' => 0, 'loan_amount' => 0.0, 'interest' => 0.0, 'total_interest' => 0.0],
            'general_paid' => ['principal_paid' => 0.0, 'interest_paid' => 0.0, 'interest_deducted' => 0.0, 'penalties_received' => 0.0],
            'paid_off' => ['settled_customers' => 0, 'settled_principal' => 0.0, 'settled_interest' => 0.0, 'settled_penalties' => 0.0, 'prepayment_discount' => 0.0],
            'active' => ['active_customers' => 0, 'active_principal' => 0.0, 'active_monthly_interest' => 0.0, 'active_total_interest' => 0.0],
            'bad_debt' => ['bad_customers' => 0, 'bad_principal' => 0.0, 'bad_interest' => 0.0, 'bad_total' => 0.0],
        ];

        foreach ($rows as $row) {
            foreach ($totals as $group => $columns) {
                foreach ($columns as $key => $value) {
                    $totals[$group][$key] += $row[$group][$key] ?? 0;
                }
            }
        }

        $totals['settlement_rate'] = $totals['registered']['customers'] > 0
            ? ($totals['paid_off']['settled_customers'] / $totals['registered']['customers']) * 100
            : 0;
        $totals['bad_debt_ratio'] = $totals['registered']['loan_amount'] > 0
            ? ($totals['bad_debt']['bad_principal'] / $totals['registered']['loan_amount']) * 100
            : 0;

        return $totals;
    }

    protected function adminLoanDetailRows(array $filters, string $group)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loans')) {
            return collect();
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing('loans');
        $dateColumn = $this->firstLoanReportColumn('loans', ['loan_date', 'created_at'], $columns);
        if (! $dateColumn) {
            return collect();
        }

        $joinCustomers = Schema::connection('mysql_loan')->hasTable('loan_customers')
            && in_array('customer_id', $columns, true)
            && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'id');
        $customerNameExpr = $joinCustomers && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'khmer_name')
            ? 'COALESCE(NULLIF(c.khmer_name, ""), '.(in_array('customer_name_snapshot', $columns, true) ? 'l.customer_name_snapshot' : 'NULL').')'
            : (in_array('customer_name_snapshot', $columns, true) ? 'l.customer_name_snapshot' : 'NULL');
        $customerBlacklist = $joinCustomers && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'blacklist_status');
        $closedCondition = $this->closedLoanConditionSql('l');
        $statusExpr = in_array('status', $columns, true)
            ? 'CASE WHEN '.$closedCondition.' THEN "completed" ELSE l.status END'
            : 'CASE WHEN '.$closedCondition.' THEN "completed" ELSE "pending" END';

        $q = DB::connection('mysql_loan')->table('loans as l');
        if ($joinCustomers) {
            $q->leftJoin('loan_customers as c', 'c.id', '=', 'l.customer_id');
        }

        $this->applyYearlyLoanFilters($q, $filters, 'l', $dateColumn);
        if (! empty($filters['month'])) {
            $q->whereMonth('l.'.$dateColumn, (int) $filters['month']);
        }
        $this->applyAdminLoanDetailGroupFilter($q, $group, $columns, $customerBlacklist ? 'c' : null);

        return $q->selectRaw(
                'l.id, '.
                (in_array('loan_number', $columns, true) ? 'l.loan_number' : 'CAST(l.id as CHAR)').' as loan_number, '.
                'l.'.$dateColumn.' as loan_date, '.
                (in_array('customer_id', $columns, true) ? 'l.customer_id' : 'NULL').' as customer_id, '.
                ($joinCustomers && Schema::connection('mysql_loan')->hasColumn('loan_customers', 'telegram_chat_id') ? 'c.telegram_chat_id' : 'NULL').' as telegram_chat_id, '.
                $customerNameExpr.' as customer_name, '.
                (in_array('customer_phone_snapshot', $columns, true) ? 'l.customer_phone_snapshot' : 'NULL').' as customer_phone, '.
                (in_array('customer_address_snapshot', $columns, true) ? 'l.customer_address_snapshot' : 'NULL').' as customer_address, '.
                (in_array('id_card_number', $columns, true) ? 'l.id_card_number' : 'NULL').' as id_card_number, '.
                (in_array('source_invoice_no', $columns, true) ? 'l.source_invoice_no' : 'NULL').' as source_invoice_no, '.
                (in_array('source_type', $columns, true) ? 'l.source_type' : 'NULL').' as source_type, '.
                (in_array('source_transaction_id', $columns, true) ? 'l.source_transaction_id' : 'NULL').' as source_transaction_id, '.
                (in_array('location_name_snapshot', $columns, true) ? 'l.location_name_snapshot' : (in_array('business_location_name_snapshot', $columns, true) ? 'l.business_location_name_snapshot' : 'NULL')).' as location_name, '.
                (in_array('business_location_name_snapshot', $columns, true) ? 'l.business_location_name_snapshot' : 'NULL').' as business_location_name_snapshot, '.
                (in_array('principal_amount', $columns, true) ? 'l.principal_amount' : '0').' as principal_amount, '.
                (in_array('interest_amount', $columns, true) ? 'l.interest_amount' : '0').' as interest_amount, '.
                (in_array('total_amount', $columns, true) ? 'l.total_amount' : '0').' as total_amount, '.
                (in_array('paid_amount', $columns, true) ? 'l.paid_amount' : '0').' as paid_amount, '.
                (in_array('balance_amount', $columns, true) ? 'l.balance_amount' : '0').' as balance_amount, '.
                (in_array('down_payment', $columns, true) ? 'l.down_payment' : '0').' as down_payment, '.
                (in_array('installment_count', $columns, true) ? 'l.installment_count' : '0').' as installment_count, '.
                (in_array('duration_months', $columns, true) ? 'l.duration_months' : '0').' as duration_months, '.
                (in_array('interest_rate', $columns, true) ? 'l.interest_rate' : '0').' as interest_rate, '.
                (in_array('interest_type', $columns, true) ? 'l.interest_type' : 'NULL').' as interest_type, '.
                (in_array('payment_frequency', $columns, true) ? 'l.payment_frequency' : 'NULL').' as payment_frequency, '.
                (in_array('first_due_date', $columns, true) ? 'l.first_due_date' : 'NULL').' as first_due_date, '.
                (in_array('maturity_date', $columns, true) ? 'l.maturity_date' : 'NULL').' as maturity_date, '.
                $statusExpr.' as status, '.
                (in_array('currency', $columns, true) ? 'l.currency' : "'USD'").' as currency, '.
                (in_array('collector_name_snapshot', $columns, true) ? 'l.collector_name_snapshot' : 'NULL').' as collector_name_snapshot, '.
                (in_array('collection_status', $columns, true) ? 'l.collection_status' : 'NULL').' as collection_status, '.
                (in_array('risk_level', $columns, true) ? 'l.risk_level' : 'NULL').' as risk_level, '.
                (in_array('note', $columns, true) ? 'l.note' : 'NULL').' as note'
            )
            ->orderByDesc('l.'.$dateColumn)
            ->orderByDesc('l.id')
            ->limit(1000)
            ->get()
            ->each(function ($loanRow) {
                $loanRow->items = $this->adminLoanItems((int) $loanRow->id);
                $loanRow->related_counts = [
                    'products' => $loanRow->items->count(),
                    'schedules' => $this->adminLoanRelatedCount('loan_payment_schedules', (int) $loanRow->id),
                    'payments' => $this->adminLoanRelatedCount('loan_payments', (int) $loanRow->id),
                    'documents' => $this->adminLoanRelatedCount('loan_files', (int) $loanRow->id),
                ];
            });
    }

    protected function applyAdminLoanDetailGroupFilter($query, string $group, array $columns, ?string $customerAlias = null): void
    {
        if ($group === 'paidOff') {
            $query->whereRaw($this->closedLoanConditionSql('l'));
            return;
        }

        if ($group === 'badDebt') {
            $query->whereRaw($this->badLoanConditionSql('l', $columns, $customerAlias));
            return;
        }

        if ($group === 'active') {
            $query->whereRaw('NOT ('.$this->closedLoanConditionSql('l').')')
                ->whereRaw('NOT ('.$this->badLoanConditionSql('l', $columns, $customerAlias).')');
            return;
        }

        if ($group === 'generalPaid') {
            $paidExpr = $this->coalesceSql('loans', 'l', ['paid_amount']);
            $query->whereRaw($paidExpr.' > 0');
        }
    }

    protected function adminLoanItems(int $loan)
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_items')) {
            return collect();
        }

        $query = DB::connection('mysql_loan')->table('loan_items')->where('loan_id', $loan);
        if (Schema::connection('mysql_loan')->hasColumn('loan_items', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('id')->get();
    }

    protected function adminLoanRelatedCount(string $table, int $loan): int
    {
        if (! Schema::connection('mysql_loan')->hasTable($table) || ! Schema::connection('mysql_loan')->hasColumn($table, 'loan_id')) {
            return 0;
        }

        $query = DB::connection('mysql_loan')->table($table)->where('loan_id', $loan);
        if (Schema::connection('mysql_loan')->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    protected function updateAdminLoanCustomerSnapshot(int $loan, array $data): void
    {
        if (! Schema::connection('mysql_loan')->hasTable('loan_customers')) {
            return;
        }

        $loanRow = DB::connection('mysql_loan')->table('loans')->where('id', $loan)->first();
        $customerId = (int) ($loanRow->customer_id ?? 0);
        if ($customerId <= 0) {
            return;
        }

        DB::connection('mysql_loan')->table('loan_customers')->where('id', $customerId)->update($this->adminLoanSafeColumns('loan_customers', [
            'name' => $data['customer_name_snapshot'] ?? null,
            'khmer_name' => $data['customer_name_snapshot'] ?? null,
            'phone' => $data['customer_phone_snapshot'] ?? null,
            'mobile' => $data['customer_phone_snapshot'] ?? null,
            'address' => $data['customer_address_snapshot'] ?? null,
            'id_card_number' => $data['id_card_number'] ?? null,
            'updated_at' => now(),
        ]));
    }

    protected function updateAdminLoanItems(int $loan, array $items): void
    {
        if (empty($items) || ! Schema::connection('mysql_loan')->hasTable('loan_items')) {
            return;
        }

        foreach ($items as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            $exists = DB::connection('mysql_loan')->table('loan_items')
                ->where('id', $itemId)
                ->where('loan_id', $loan)
                ->exists();
            if (! $exists) {
                continue;
            }

            $qty = round((float) ($item['qty'] ?? 1), 4);
            $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);
            $discount = round((float) ($item['discount'] ?? 0), 2);
            $lineTotal = array_key_exists('line_total', $item) && $item['line_total'] !== null
                ? round((float) $item['line_total'], 2)
                : max(0, round(($qty * $unitPrice) - $discount, 2));

            DB::connection('mysql_loan')->table('loan_items')->where('id', $itemId)->update($this->adminLoanSafeColumns('loan_items', [
                'product_name_snapshot' => $item['product_name_snapshot'] ?? null,
                'product_name' => $item['product_name_snapshot'] ?? null,
                'sku_snapshot' => $item['sku_snapshot'] ?? null,
                'sku' => $item['sku_snapshot'] ?? null,
                'imei_snapshot' => $item['imei_snapshot'] ?? null,
                'imei' => $item['imei_snapshot'] ?? null,
                'serial_number_snapshot' => $item['serial_number_snapshot'] ?? null,
                'serial_number' => $item['serial_number_snapshot'] ?? null,
                'brand' => $item['brand'] ?? null,
                'category' => $item['category'] ?? null,
                'color' => $item['color'] ?? null,
                'color_snapshot' => $item['color'] ?? null,
                'storage' => $item['storage'] ?? null,
                'storage_snapshot' => $item['storage'] ?? null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
                'updated_at' => now(),
            ]));
        }
    }

    protected function adminLoanSafeColumns(string $table, array $values): array
    {
        if (! Schema::connection('mysql_loan')->hasTable($table)) {
            return [];
        }

        $columns = Schema::connection('mysql_loan')->getColumnListing($table);

        return array_intersect_key($values, array_flip($columns));
    }

    protected function parseYearlyLocationFilter(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        if (strpos($value, ':') !== false) {
            $filter = [];
            foreach (explode('|', $value) as $part) {
                [$key, $raw] = array_pad(explode(':', $part, 2), 2, null);
                if ($key === 'loan' && ctype_digit((string) $raw)) {
                    $filter['loan_location_id'] = (int) $raw;
                } elseif ($key === 'main' && ctype_digit((string) $raw)) {
                    $filter['main_location_id'] = (int) $raw;
                } elseif ($key === 'name' && $raw !== null) {
                    $name = trim(rawurldecode((string) $raw));
                    if ($name !== '') {
                        $filter['name'] = $name;
                    }
                }
            }

            return $filter;
        }

        if (ctype_digit($value)) {
            return ['legacy_id' => (int) $value];
        }

        return [];
    }

    protected function loanReportLocationOptions(): array
    {
        $options = [];

        if (Schema::connection('mysql_loan')->hasTable('loan_business_locations')) {
            $hasMainLocationId = Schema::connection('mysql_loan')->hasColumn('loan_business_locations', 'main_location_id');

            $options = DB::connection('mysql_loan')
                ->table('loan_business_locations')
                ->selectRaw('id, '.($hasMainLocationId ? 'main_location_id' : 'NULL as main_location_id').', COALESCE(NULLIF(name, ""), CONCAT("Location #", id)) as name')
                ->when(Schema::connection('mysql_loan')->hasColumn('loan_business_locations', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                ->orderBy('name')
                ->get()
                ->mapWithKeys(function ($row) {
                    $key = 'loan:'.(int) $row->id;
                    if (! empty($row->main_location_id)) {
                        $key .= '|main:'.(int) $row->main_location_id;
                    }
                    if (! empty($row->name)) {
                        $key .= '|name:'.rawurlencode((string) $row->name);
                    }

                    return [$key => $row->name];
                })
                ->all();
        }

        if (Schema::connection('mysql_loan')->hasTable('loans')) {
            $loanColumns = Schema::connection('mysql_loan')->getColumnListing('loans');
            foreach (['location_name_snapshot', 'business_location_name_snapshot'] as $column) {
                if (! in_array($column, $loanColumns, true)) {
                    continue;
                }

                DB::connection('mysql_loan')
                    ->table('loans')
                    ->selectRaw('DISTINCT '.$column.' as name')
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->orderBy($column)
                    ->get()
                    ->each(function ($row) use (&$options) {
                        $name = trim((string) ($row->name ?? ''));
                        if ($name === '') {
                            return;
                        }
                        if (in_array($name, $options, true)) {
                            return;
                        }
                        $key = 'name:'.rawurlencode($name);
                        if (! array_key_exists($key, $options)) {
                            $options[$key] = $name;
                        }
                    });
            }
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    protected function downloadYearlyLoanSummaryCsv(array $payload, array $filters)
    {
        $columns = [
            $this->loanReportText('Year', 'ឆ្នាំ'),
            $this->loanReportText('Registered Count', 'ចំនួនចុះឈ្មោះ'), $this->loanReportText('Registered Principal', 'ប្រាក់ដើមចុះឈ្មោះ'), $this->loanReportText('Registered Interest', 'ការប្រាក់ចុះឈ្មោះ'), $this->loanReportText('Registered Total', 'សរុបចុះឈ្មោះ'),
            $this->loanReportText('Paid Customer Count', 'ចំនួនអតិថិជនបានបង់'), $this->loanReportText('Collection Payments', 'បង់ប្រចាំខែ'), $this->loanReportText('Deposit Payments', 'ប្រាក់កក់'), $this->loanReportText('Paid Total', 'បានបង់ទូរទៅ'),
            $this->loanReportText('Paid Off Count', 'ចំនួនបង់ផ្ដាច់'), $this->loanReportText('Paid Off Principal', 'ប្រាក់ដើមបង់ផ្ដាច់'), $this->loanReportText('Paid Off Interest', 'ការប្រាក់បង់ផ្ដាច់'), $this->loanReportText('Paid Off Total', 'សរុបបង់ផ្ដាច់'), $this->loanReportText('Paid Off Paid', 'បានបង់ផ្ដាច់'), $this->loanReportText('Paid Off Balance', 'សមតុល្យបង់ផ្ដាច់'),
            $this->loanReportText('Bad Count', 'ចំនួនអតិថិជនខូច'), $this->loanReportText('Bad Principal', 'ប្រាក់ដើមអតិថិជនខូច'), $this->loanReportText('Bad Interest', 'ការប្រាក់អតិថិជនខូច'), $this->loanReportText('Bad Total', 'សរុបអតិថិជនខូច'), $this->loanReportText('Bad Paid', 'បានបង់អតិថិជនខូច'), $this->loanReportText('Bad Balance', 'សមតុល្យអតិថិជនខូច'),
        ];
        $lines = [$columns];
        foreach ($payload['rows'] as $row) {
            $lines[] = [
                $row['year'],
                $row['loan_count'], $row['principal_total'], $row['interest_total'], $row['loan_total'],
                $row['paid_customer_count'], $row['collection_payment_total'], $row['deposit_payment_total'], $row['payment_total'],
                $row['closed_count'], $row['closed_principal_total'], $row['closed_interest_total'], $row['closed_loan_total'], $row['closed_paid_total'], $row['closed_balance_total'],
                $row['bad_count'], $row['bad_principal_total'], $row['bad_interest_total'], $row['bad_loan_total'], $row['bad_paid_total'], $row['bad_balance_total'],
            ];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'yearly-loan-summary-'.$filters['date_from'].'-'.$filters['date_to'].'.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function downloadPeriodicLoanSummaryCsv(array $payload, array $filters, string $period)
    {
        $periodHeader = $period === 'monthly'
            ? $this->loanReportText('Month', 'ខែ')
            : $this->loanReportText('Date', 'ថ្ងៃ');

        $columns = [
            $periodHeader,
            $this->loanReportText('Registered Count', 'ចំនួនចុះឈ្មោះ'), $this->loanReportText('Registered Principal', 'ប្រាក់ដើមចុះឈ្មោះ'), $this->loanReportText('Registered Interest', 'ការប្រាក់ចុះឈ្មោះ'), $this->loanReportText('Registered Total', 'សរុបចុះឈ្មោះ'),
            $this->loanReportText('Paid Customer Count', 'ចំនួនអតិថិជនបានបង់'), $this->loanReportText('Collection Payments', 'បង់ប្រចាំខែ'), $this->loanReportText('Deposit Payments', 'ប្រាក់កក់'), $this->loanReportText('Paid Total', 'បានបង់ទូរទៅ'),
            $this->loanReportText('Paid Off Count', 'ចំនួនបង់ផ្ដាច់'), $this->loanReportText('Paid Off Principal', 'ប្រាក់ដើមបង់ផ្ដាច់'), $this->loanReportText('Paid Off Interest', 'ការប្រាក់បង់ផ្ដាច់'), $this->loanReportText('Paid Off Total', 'សរុបបង់ផ្ដាច់'), $this->loanReportText('Paid Off Paid', 'បានបង់ផ្ដាច់'), $this->loanReportText('Paid Off Balance', 'សមតុល្យបង់ផ្ដាច់'),
            $this->loanReportText('Bad Count', 'ចំនួនអតិថិជនខូច'), $this->loanReportText('Bad Principal', 'ប្រាក់ដើមអតិថិជនខូច'), $this->loanReportText('Bad Interest', 'ការប្រាក់អតិថិជនខូច'), $this->loanReportText('Bad Total', 'សរុបអតិថិជនខូច'), $this->loanReportText('Bad Paid', 'បានបង់អតិថិជនខូច'), $this->loanReportText('Bad Balance', 'សមតុល្យអតិថិជនខូច'),
        ];
        $lines = [$columns];
        foreach ($payload['rows'] as $row) {
            $lines[] = [
                $row['label'] ?? $row['key'],
                $row['loan_count'], $row['principal_total'], $row['interest_total'], $row['loan_total'],
                $row['paid_customer_count'], $row['collection_payment_total'], $row['deposit_payment_total'], $row['payment_total'],
                $row['closed_count'], $row['closed_principal_total'], $row['closed_interest_total'], $row['closed_loan_total'], $row['closed_paid_total'], $row['closed_balance_total'],
                $row['bad_count'], $row['bad_principal_total'], $row['bad_interest_total'], $row['bad_loan_total'], $row['bad_paid_total'], $row['bad_balance_total'],
            ];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = $period === 'monthly'
            ? 'monthly-loan-summary-'.$filters['date_from'].'-'.$filters['date_to'].'.csv'
            : 'daily-loan-summary-'.$filters['date_from'].'-'.$filters['date_to'].'.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function loanReportText(string $english, string $khmer): string
    {
        return $this->loanReportIsKhmer() ? $khmer : $english;
    }

    protected function loanReportIsKhmer(): bool
    {
        return session('user.language', config('app.locale')) === 'km';
    }
}
