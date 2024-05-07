<?php

namespace App\Filament\Company\Pages\Reports;

use App\DTO\AccountBalanceReportDTO;
use App\Filament\Forms\Components\DateRangeSelect;
use App\Models\Company;
use App\Services\AccountBalancesExportService;
use App\Services\AccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountBalances extends Page
{
    protected static string $view = 'filament.company.pages.reports.account-balances';

    protected static ?string $slug = 'reports/account-balances';

    public string $startDate = '';

    public string $endDate = '';

    public string $dateRange = '';

    public string $fiscalYearStartDate = '';

    public string $fiscalYearEndDate = '';

    public Company $company;

    public AccountBalanceReportDTO $accountBalanceReport;

    protected AccountService $accountService;

    protected AccountBalancesExportService $accountBalancesExportService;

    public function boot(AccountService $accountService, AccountBalancesExportService $accountBalancesExportService): void
    {
        $this->accountService = $accountService;
        $this->accountBalancesExportService = $accountBalancesExportService;
    }

    public function mount(): void
    {
        $this->company = auth()->user()->currentCompany;
        $this->fiscalYearStartDate = $this->company->locale->fiscalYearStartDate();
        $this->fiscalYearEndDate = $this->company->locale->fiscalYearEndDate();
        $this->dateRange = $this->getDefaultDateRange();
        $this->setDateRange(Carbon::parse($this->fiscalYearStartDate), Carbon::parse($this->fiscalYearEndDate));

        $this->loadAccountBalances();
    }

    public function getDefaultDateRange(): string
    {
        return 'FY-' . now()->year;
    }

    public function loadAccountBalances(): void
    {
        $this->accountBalanceReport = $this->accountService->buildAccountBalanceReport($this->startDate, $this->endDate);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportCSV')
                    ->label('CSV')
                    ->action(fn () => $this->exportCSV()),
                Action::make('exportPDF')
                    ->label('PDF')
                    ->action(fn () => $this->exportPDF()),
            ])
                ->label('Export')
                ->button()
                ->outlined()
                ->dropdownWidth('max-w-[7rem]')
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-c-chevron-down')
                ->iconSize(IconSize::Small)
                ->iconPosition(IconPosition::After),
        ];
    }

    public function exportCSV(): StreamedResponse
    {
        return $this->accountBalancesExportService->exportToCsv($this->company, $this->accountBalanceReport, $this->startDate, $this->endDate);
    }

    public function exportPDF(): StreamedResponse
    {
        $pdf = Pdf::loadView('components.company.reports.account-balances', [
            'accountBalanceReport' => $this->accountBalanceReport,
            'startDate' => Carbon::parse($this->startDate)->format('M d, Y'),
            'endDate' => Carbon::parse($this->endDate)->format('M d, Y'),
        ])->setPaper('a4');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'account-balances.pdf');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    DateRangeSelect::make('dateRange')
                        ->label('Date Range')
                        ->selectablePlaceholder(false)
                        ->startDateField('startDate')
                        ->endDateField('endDate'),
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->displayFormat('Y-m-d')
                        ->afterStateUpdated(static function (Set $set) {
                            $set('dateRange', 'Custom');
                        }),
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->displayFormat('Y-m-d')
                        ->afterStateUpdated(static function (Set $set) {
                            $set('dateRange', 'Custom');
                        }),
                ])->live(),
            ]);
    }

    public function setDateRange(Carbon $start, Carbon $end): void
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->isFuture() ? now()->format('Y-m-d') : $end->format('Y-m-d');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
