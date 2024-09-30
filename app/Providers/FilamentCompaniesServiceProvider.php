<?php

namespace App\Providers;

use Exception;
use Filament\Forms;
use Filament\Pages;
use Filament\Panel;
use Filament\Tables;
use Filament\Actions;
use Filament\Widgets;
use App\Models\Company;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use App\Livewire\UpdatePassword;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Select;
use App\Filament\Company\Pages\Reports;
use Filament\Navigation\NavigationGroup;
use App\Livewire\UpdateProfileInformation;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use Wallo\FilamentCompanies\Enums\Feature;
use App\Filament\Company\Clusters\Settings;
use Wallo\FilamentCompanies\Enums\Provider;
use App\Actions\FilamentCompanies\DeleteUser;
use App\Filament\Company\Pages\CreateCompany;
use App\Filament\Company\Pages\ManageCompany;
use Wallo\FilamentCompanies\Pages\Auth\Login;
use App\Support\FilamentComponentConfigurator;
use Wallo\FilamentCompanies\FilamentCompanies;
use App\Filament\Components\PanelShiftDropdown;
use Illuminate\Session\Middleware\StartSession;
use App\Actions\FilamentCompanies\CreateNewUser;
use App\Actions\FilamentCompanies\DeleteCompany;
use App\Http\Middleware\ConfigureCurrentCompany;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Wallo\FilamentCompanies\Pages\Auth\Register;
use App\Filament\Company\Resources\StaffResource;
use App\Actions\FilamentCompanies\SetUserPassword;
use App\Actions\FilamentCompanies\UpdateCompanyName;
use App\Filament\Company\Pages\Service\LiveCurrency;
use App\Actions\FilamentCompanies\AddCompanyEmployee;
use App\Actions\FilamentCompanies\HandleInvalidState;
use App\Actions\FilamentCompanies\UpdateUserPassword;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Actions\FilamentCompanies\ResolveSocialiteUser;
use App\Filament\Company\Pages\Accounting\AccountChart;
use App\Filament\Company\Pages\Accounting\Transactions;
use App\Actions\FilamentCompanies\InviteCompanyEmployee;
use App\Actions\FilamentCompanies\RemoveCompanyEmployee;
use App\Filament\Company\Pages\Service\ConnectedAccount;
use Filament\Http\Middleware\DisableBladeIconComponents;
use App\Actions\FilamentCompanies\CreateConnectedAccount;
use App\Actions\FilamentCompanies\CreateUserFromProvider;
use App\Actions\FilamentCompanies\UpdateConnectedAccount;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Filament\Company\Resources\Banking\AccountResource;
use App\Filament\Company\Resources\Core\DepartmentResource;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Actions\FilamentCompanies\UpdateUserProfileInformation;
use Wallo\FilamentCompanies\Actions\GenerateRedirectForProvider;

class FilamentCompaniesServiceProvider extends PanelProvider
{
    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('company')
            ->path('company')
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->tenantMenu(false)
            ->plugin(
                FilamentCompanies::make()
                    ->userPanel('user')
                    ->switchCurrentCompany()
                    ->updateProfileInformation(component: UpdateProfileInformation::class)
                    ->updatePasswords(component: UpdatePassword::class)
                    ->setPasswords()
                    ->connectedAccounts()
                    ->manageBrowserSessions()
                    ->accountDeletion()
                    ->profilePhotos()
                    ->api()
                    ->companies(invitations: true)
                    ->termsAndPrivacyPolicy()
                    ->notifications()
                    ->modals()
                    ->socialite(
                        providers: [Provider::Github],
                        features: [Feature::RememberSession, Feature::ProviderAvatars],
                    ),
            )
            ->plugin(
                PanelShiftDropdown::make()
                    ->logoutItem()
                    ->companySettings(),
            )
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Gray,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->items([
                        ...Dashboard::getNavigationItems(),
                        ...Reports::getNavigationItems(),
                        ...Settings::getNavigationItems(),
                        ...StaffResource::getNavigationItems(),

                    ])
                    ->groups([
                        NavigationGroup::make('Accounting')
                            ->localizeLabel()
                            ->icon('heroicon-o-clipboard-document-list')
                            ->extraSidebarAttributes(['class' => 'es-sidebar-group'])
                            ->items([
                                ...AccountChart::getNavigationItems(),
                                ...Transactions::getNavigationItems(),
                            ]),
                        NavigationGroup::make('Banking')
                            ->localizeLabel()
                            ->icon('heroicon-o-building-library')
                            ->items(AccountResource::getNavigationItems()),
                        NavigationGroup::make('HR')
                            ->icon('heroicon-o-user-group')
                            ->items(
                                [
                                    ...DepartmentResource::getNavigationItems(),

                                ]
                            ),

                        NavigationGroup::make('Services')
                            ->localizeLabel()
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->items([
                                ...ConnectedAccount::getNavigationItems(),
                                ...LiveCurrency::getNavigationItems(),
                            ]),
                    ]);
            })
            ->viteTheme('resources/css/filament/company/theme.css')
            ->brandLogo(static fn() => view('components.icons.logo'))
            ->tenant(Company::class)
            ->tenantProfile(ManageCompany::class)
            ->tenantRegistration(CreateCompany::class)
            ->discoverResources(in: app_path('Filament/Company/Resources'), for: 'App\\Filament\\Company\\Resources')
            ->discoverPages(in: app_path('Filament/Company/Pages'), for: 'App\\Filament\\Company\\Pages')
            ->discoverClusters(in: app_path('Filament/Company/Clusters'), for: 'App\\Filament\\Company\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->authGuard('web')
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->tenantMiddleware([
                ConfigureCurrentCompany::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();
        $this->configureDefaults();

        FilamentCompanies::createUsersUsing(CreateNewUser::class);
        FilamentCompanies::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        FilamentCompanies::updateUserPasswordsUsing(UpdateUserPassword::class);

        FilamentCompanies::createCompaniesUsing(CreateCompany::class);
        FilamentCompanies::updateCompanyNamesUsing(UpdateCompanyName::class);
        FilamentCompanies::addCompanyEmployeesUsing(AddCompanyEmployee::class);
        FilamentCompanies::inviteCompanyEmployeesUsing(InviteCompanyEmployee::class);
        FilamentCompanies::removeCompanyEmployeesUsing(RemoveCompanyEmployee::class);
        FilamentCompanies::deleteCompaniesUsing(DeleteCompany::class);
        FilamentCompanies::deleteUsersUsing(DeleteUser::class);

        FilamentCompanies::resolvesSocialiteUsersUsing(ResolveSocialiteUser::class);
        FilamentCompanies::createUsersFromProviderUsing(CreateUserFromProvider::class);
        FilamentCompanies::createConnectedAccountsUsing(CreateConnectedAccount::class);
        FilamentCompanies::updateConnectedAccountsUsing(UpdateConnectedAccount::class);
        FilamentCompanies::setUserPasswordsUsing(SetUserPassword::class);
        FilamentCompanies::handlesInvalidStateUsing(HandleInvalidState::class);
        FilamentCompanies::generatesProvidersRedirectsUsing(GenerateRedirectForProvider::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        FilamentCompanies::defaultApiTokenPermissions(['read']);

        FilamentCompanies::role('admin', 'Administrator', [
            'create',
            'read',
            'update',
            'delete',
        ])->description('Administrator users can perform any action.');

        FilamentCompanies::role('editor', 'Editor', [
            'read',
            'create',
            'update',
        ])->description('Editor users have the ability to read, create, and update.');
    }

    /**
     * Configure the default settings for Filament.
     */
    protected function configureDefaults(): void
    {
        $this->configureSelect();

        Actions\CreateAction::configureUsing(static fn(Actions\CreateAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Actions\EditAction::configureUsing(static fn(Actions\EditAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Tables\Actions\EditAction::configureUsing(static fn(Tables\Actions\EditAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Tables\Actions\CreateAction::configureUsing(static fn(Tables\Actions\CreateAction $action) => FilamentComponentConfigurator::configureActionModals($action));
        Forms\Components\DateTimePicker::configureUsing(static function (Forms\Components\DateTimePicker $component) {
            $component->native(false);
        });
    }

    /**
     * Configure the default settings for the Select component.
     */
    protected function configureSelect(): void
    {
        Select::configureUsing(function (Select $select): void {
            $isSelectable = fn(): bool => !$this->hasRequiredRule($select);

            $select
                ->native(false)
                ->selectablePlaceholder($isSelectable);
        }, isImportant: true);
    }

    protected function hasRequiredRule(Select $component): bool
    {
        $rules = $component->getValidationRules();

        return in_array('required', $rules, true);
    }
}
