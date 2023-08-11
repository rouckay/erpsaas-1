<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Wallo\FilamentCompanies\FilamentCompanies;

class Invoice extends Page
{
    public mixed $company;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoice';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Invoice';

    protected static string $view = 'filament.pages.invoice';

    public function mount($company): void
    {
        $this->company = FilamentCompanies::newCompanyModel()->findOrFail($company);
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        Gate::authorize('view', $this->company);
    }

    public static function getSlug(): string
    {
        return '{company}/settings/invoice';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true): string
    {
        return route(static::getRouteName(), ['company' => Auth::user()->currentCompany], $isAbsolute);
    }

    protected function getBreadcrumbs(): array
    {
        return [
            'invoice' => 'Invoice',
        ];
    }
}
