<?php

namespace App\Listeners;

use App\Enums\Setting\DateFormat;
use App\Enums\Setting\Font;
use App\Enums\Setting\PrimaryColor;
use App\Enums\Setting\RecordsPerPage;
use App\Enums\Setting\TableSortDirection;
use App\Enums\Setting\WeekStart;
use App\Events\CompanyConfigured;
use App\Utilities\Currency\ConfigureCurrencies;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Components\Tab as ResourcesTab;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;

class ConfigureCompanyDefault
{
    /**
     * Handle the event.
     */
    public function handle(CompanyConfigured $event): void
    {
        $company = $event->company;
        $paginationPageOptions = RecordsPerPage::caseValues();
        $defaultPaginationPageOption = $company->appearance->records_per_page->value ?? RecordsPerPage::DEFAULT;
        $defaultSort = $company->appearance->table_sort_direction->value ?? TableSortDirection::DEFAULT;
        $defaultPrimaryColor = $company->appearance->primary_color ?? PrimaryColor::from(PrimaryColor::DEFAULT);
        $defaultFont = $company->appearance->font->value ?? Font::DEFAULT;
        $default_language = $company->locale->language ?? config('transmatic.source_locale');
        $defaultTimezone = $company->locale->timezone ?? config('app.timezone');
        $dateFormat = $company->locale->date_format->value ?? DateFormat::DEFAULT;
        $weekStart = $company->locale->week_start->value ?? WeekStart::DEFAULT;

        app()->setLocale($default_language);
        locale_set_default($default_language);
        config(['app.timezone' => $defaultTimezone]);
        date_default_timezone_set($defaultTimezone);

        Table::configureUsing(static function (Table $table) use ($paginationPageOptions, $defaultSort, $defaultPaginationPageOption): void {

            $table
                ->paginationPageOptions($paginationPageOptions)
                ->defaultSort(column: 'id', direction: $defaultSort)
                ->defaultPaginationPageOption($defaultPaginationPageOption);
        }, isImportant: true);

        FilamentColor::register([
            'primary' => $defaultPrimaryColor->getColor(),
        ]);

        Filament::getPanel('company')
            ->font($defaultFont)
            ->brandName($company->name);

        DatePicker::configureUsing(static function (DatePicker $component) use ($dateFormat, $weekStart) {
            $component
                ->displayFormat($dateFormat)
                ->firstDayOfWeek($weekStart);
        });

        Tab::configureUsing(static function (Tab $tab) {
            $label = $tab->getLabel();

            $translatedLabel = translate($label);

            $tab->label(ucwords($translatedLabel));
        }, isImportant: true);

        Section::configureUsing(static function (Section $section): void {
            $heading = $section->getHeading();

            $translatedHeading = translate($heading);

            $section->heading(ucfirst($translatedHeading));
        }, isImportant: true);

        ResourcesTab::configureUsing(static function (ResourcesTab $tab): void {
            $tab->localizeLabel();
        }, isImportant: true);

        ConfigureCurrencies::syncCurrencies();
    }
}
