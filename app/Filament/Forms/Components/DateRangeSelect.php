<?php

namespace App\Filament\Forms\Components;

use App\Facades\Accounting;
use App\Models\Company;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;

class DateRangeSelect extends Select
{
    public string $fiscalYearStartDate = '';

    public string $fiscalYearEndDate = '';

    public string $startDateField = '';

    public string $endDateField = '';

    public Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = auth()->user()->currentCompany;
        $this->fiscalYearStartDate = $this->company->locale->fiscalYearStartDate();
        $this->fiscalYearEndDate = $this->company->locale->fiscalYearEndDate();

        $this->options($this->getDateRangeOptions())
            ->afterStateUpdated(function ($state, Set $set) {
                $this->updateDateRange($state, $set);
            });
    }

    public function startDateField(string $fieldName): static
    {
        $this->startDateField = $fieldName;

        return $this;
    }

    public function endDateField(string $fieldName): static
    {
        $this->endDateField = $fieldName;

        return $this;
    }

    public function getDateRangeOptions(): array
    {
        $earliestDate = Carbon::parse(Accounting::getEarliestTransactionDate());
        $currentDate = now();
        $fiscalYearStartCurrent = Carbon::parse($this->fiscalYearStartDate);

        $options = [
            'Fiscal Year' => [],
            'Fiscal Quarter' => [],
            'Calendar Year' => [],
            'Calendar Quarter' => [],
            'Month' => [],
            'Custom' => [],
        ];

        $period = CarbonPeriod::create($earliestDate, '1 month', $currentDate);

        foreach ($period as $date) {
            $options['Fiscal Year']['FY-' . $date->year] = $date->year;

            $fiscalYearStart = $fiscalYearStartCurrent->copy()->subYears($currentDate->year - $date->year);

            for ($i = 0; $i < 4; $i++) {
                $quarterNumber = $i + 1;
                $quarterStart = $fiscalYearStart->copy()->addMonths(($quarterNumber - 1) * 3);
                $quarterEnd = $quarterStart->copy()->addMonths(3)->subDay();

                if ($quarterStart->lessThanOrEqualTo($currentDate) && $quarterEnd->greaterThanOrEqualTo($earliestDate)) {
                    $options['Fiscal Quarter']['FQ-' . $quarterNumber . '-' . $date->year] = 'Q' . $quarterNumber . ' ' . $date->year;
                }
            }

            $options['Calendar Year']['Y-' . $date->year] = $date->year;
            $quarterKey = 'Q-' . $date->quarter . '-' . $date->year;
            $options['Calendar Quarter'][$quarterKey] = 'Q' . $date->quarter . ' ' . $date->year;
            $options['Month']['M-' . $date->format('Y-m')] = $date->format('F Y');
            $options['Custom']['Custom'] = 'Custom';
        }

        $options['Fiscal Year'] = array_reverse($options['Fiscal Year'], true);
        $options['Fiscal Quarter'] = array_reverse($options['Fiscal Quarter'], true);
        $options['Calendar Year'] = array_reverse($options['Calendar Year'], true);
        $options['Calendar Quarter'] = array_reverse($options['Calendar Quarter'], true);
        $options['Month'] = array_reverse($options['Month'], true);

        return $options;
    }

    public function updateDateRange($state, Set $set): void
    {
        if ($state === null) {
            $set($this->startDateField, null);
            $set($this->endDateField, null);

            return;
        }

        [$type, $param1, $param2] = explode('-', $state) + [null, null, null];
        $this->processDateRange($type, $param1, $param2, $set);
    }

    public function processDateRange($type, $param1, $param2, Set $set): void
    {
        match ($type) {
            'FY' => $this->processFiscalYear($param1, $set),
            'FQ' => $this->processFiscalQuarter($param1, $param2, $set),
            'Y' => $this->processCalendarYear($param1, $set),
            'Q' => $this->processCalendarQuarter($param1, $param2, $set),
            'M' => $this->processMonth("{$param1}-{$param2}", $set),
            'Custom' => null,
        };
    }

    public function processFiscalYear($year, Set $set): void
    {
        $currentYear = now()->year;
        $diff = $currentYear - $year;
        $fiscalYearStart = Carbon::parse($this->fiscalYearStartDate)->subYears($diff);
        $fiscalYearEnd = Carbon::parse($this->fiscalYearEndDate)->subYears($diff);
        $this->setDateRange($fiscalYearStart, $fiscalYearEnd, $set);
    }

    public function processFiscalQuarter($quarter, $year, Set $set): void
    {
        $currentYear = now()->year;
        $diff = $currentYear - $year;
        $fiscalYearStart = Carbon::parse($this->fiscalYearStartDate)->subYears($diff);
        $quarterStart = $fiscalYearStart->copy()->addMonths(($quarter - 1) * 3);
        $quarterEnd = $quarterStart->copy()->addMonths(3)->subDay();
        $this->setDateRange($quarterStart, $quarterEnd, $set);
    }

    public function processCalendarYear($year, Set $set): void
    {
        $start = Carbon::createFromDate($year)->startOfYear();
        $end = Carbon::createFromDate($year)->endOfYear();
        $this->setDateRange($start, $end, $set);
    }

    public function processCalendarQuarter($quarter, $year, Set $set): void
    {
        $month = ($quarter - 1) * 3 + 1;
        $start = Carbon::createFromDate($year, $month, 1);
        $end = Carbon::createFromDate($year, $month, 1)->endOfQuarter();
        $this->setDateRange($start, $end, $set);
    }

    public function processMonth($yearMonth, Set $set): void
    {
        $start = Carbon::parse($yearMonth)->startOfMonth();
        $end = Carbon::parse($yearMonth)->endOfMonth();
        $this->setDateRange($start, $end, $set);
    }

    public function setDateRange(Carbon $start, Carbon $end, Set $set): void
    {
        $set($this->startDateField, $start->format('Y-m-d'));
        $set($this->endDateField, $end->isFuture() ? now()->format('Y-m-d') : $end->format('Y-m-d'));
    }
}
