<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Modules\LoanManagement\Helpers\LoanMenuHelper;
use Modules\LoanManagement\Http\Controllers\LoanCollectionController;
use Modules\LoanManagement\Services\LoanCollectionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoanNavigationTest extends TestCase
{
    private static int $userId = 10000;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        config(['cache.default' => 'array', 'session.driver' => 'array', 'view.compiled' => sys_get_temp_dir()]);
        session()->put('user.language', 'en');
        $this->authenticate(['*']);
    }

    private function authenticate(array $permissions): void
    {
        auth()->setUser(new class(['id' => ++self::$userId], $permissions) extends GenericUser {
            public function __construct(array $attributes, private array $permissions)
            {
                parent::__construct($attributes);
            }

            public function can($permission, $arguments = []): bool
            {
                return in_array('*', $this->permissions, true) || in_array($permission, $this->permissions, true);
            }
        });
    }

    private function visit(string $name, ?string $page = null, array $query = []): void
    {
        $request = Request::create('/test', 'GET', $query);
        $request->setLaravelSession(session()->driver());
        $route = new Route(['GET'], 'test', ['as' => $name]);
        $route->bind($request);
        if ($page !== null) {
            $route->setParameter('page', $page);
        }
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);
    }

    private function activeLabels(): array
    {
        $items = array_merge(...array_column(LoanMenuHelper::navigationSections(), 'items'));
        return array_column(array_filter($items, fn ($item) => LoanMenuHelper::navigationItemActive($item)), 'label');
    }

    public function testSixSectionsAndAdminLoanOrder(): void
    {
        $sections = LoanMenuHelper::navigationSections();
        $this->assertSame(['overview', 'installments', 'customers', 'collections', 'reports', 'administration'], array_column($sections, 'key'));
        $this->assertSame(['Dashboard', 'Admin Loan', 'Dashboard Reports'], array_column($sections[0]['items'], 'label'));
        $items = array_merge(...array_column($sections, 'items'));
        foreach (['Cash & Bank', 'Income', 'New Installment', 'Installment Calculator', 'Closed Accounts', 'Installment Calendar'] as $removed) {
            $this->assertNotContains($removed, array_column($items, 'label'));
        }
        foreach ($items as $item) {
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($item['route']));
        }
    }

    public function testCollectionPagesHighlightExactlyOneWorkspace(): void
    {
        foreach ([
            ['loan-management.operations.page', 'due-today', "Today's Collection"],
            ['loan-management.operations.page', 'today-collection', "Today's Collection"],
            ['loan-management.operations.page', 'partial-payments', "Today's Collection"],
            ['loan-management.collection.page', 'delinquent-accounts', 'Overdue Accounts'],
            ['loan-management.collection.page', 'recovery-management', 'Collection Cases'],
            ['loan-management.collection.page', 'debt-collection', 'Collection Cases'],
        ] as [$route, $page, $label]) {
            $this->visit($route, $page);
            $this->assertSame([$label], $this->activeLabels());
        }
    }

    public function testDetailPagesAndReportPeriodsKeepParentActive(): void
    {
        foreach ([
            'loan-management.loans.view' => 'All Installments',
            'loan-management.quotations.show' => 'Quotations & Proposals',
            'loan-management.schedules.calendar' => 'Repayment Schedule',
            'loan-management.reports.monthly-loan-summary' => 'Installment Summary',
            'loan-management.roles.edit' => 'Users & Roles',
        ] as $route => $label) {
            $this->visit($route);
            $this->assertSame([$label], $this->activeLabels());
        }
    }

    public function testRolesOnlyUserHasAuthorizedEntryPoint(): void
    {
        $this->authenticate(['roles.view']);
        $sections = LoanMenuHelper::navigationSections();
        $this->assertCount(1, $sections);
        $this->assertSame('loan-management.roles.index', $sections[0]['items'][0]['route']);
        $this->assertSame(['Roles'], array_column(LoanMenuHelper::workspaceTabs('users'), 'label'));
    }

    public function testWorkspaceTabsRetainFiltersWithoutExportAction(): void
    {
        $this->visit('loan-management.reports.monthly-loan-summary', null, [
            'date_from' => '2026-01-01', 'date_to' => '2026-12-31', 'location_id' => '2', 'export' => 'csv',
        ]);
        $html = view('loanmanagement::layouts.partials.workspace_tabs', ['workspace' => 'summary'])->render();
        $this->assertStringContainsString('date_from=2026-01-01', $html);
        $this->assertStringContainsString('location_id=2', $html);
        $this->assertStringNotContainsString('export=', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function testUnauthorizedCollectionsRequestIsDeniedBeforeQuery(): void
    {
        $this->authenticate([]);
        $this->assertSame([], LoanMenuHelper::navigationSections());
        $controller = new LoanCollectionController(new LoanCollectionService);
        try {
            $controller->index(Request::create('/'), 'due-today');
            $this->fail('Expected permission denial.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function testPermissionLookupFailureDoesNotExposeMenus(): void
    {
        auth()->setUser(new class(['id' => ++self::$userId]) extends GenericUser {
            public function can($permission, $arguments = []): bool
            {
                throw new \RuntimeException('Permission lookup unavailable.');
            }
        });
        $this->assertFalse(LoanMenuHelper::loanUserCan('loan_management.view'));
        $this->assertSame([], LoanMenuHelper::navigationSections());
    }

    public function testKhmerSectionsMenusAndTabsAreTranslated(): void
    {
        session()->put('user.language', 'km');
        $sections = LoanMenuHelper::navigationSections();
        foreach ($sections as $section) {
            $this->assertSame(1, preg_match('/[\x{1780}-\x{17FF}]/u', $section['label']));
            foreach ($section['items'] as $item) {
                $this->assertSame($item['km'], $item['label']);
            }
        }
        foreach (['summary', 'daily-collection', 'overdue', 'cases', 'users', 'settings'] as $workspace) {
            foreach (LoanMenuHelper::workspaceTabs($workspace) as $tab) {
                $this->assertSame($tab['km'], $tab['label']);
            }
        }
    }

    public function testRestrictedPermissionsDoNotExposeAdministration(): void
    {
        $this->authenticate(['loan_management.loans.view', 'loan_management.reports.view']);
        $sections = LoanMenuHelper::navigationSections();
        $this->assertNotContains('administration', array_column($sections, 'key'));
        $this->assertNotContains('collections', array_column($sections, 'key'));
        $this->assertSame([], LoanMenuHelper::workspaceTabs('users'));
        $this->assertSame([], LoanMenuHelper::workspaceTabs('settings'));
    }
}
