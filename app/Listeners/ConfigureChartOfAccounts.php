<?php

namespace App\Listeners;

use App\Enums\Accounting\AccountType;
use App\Enums\Banking\BankAccountType;
use App\Events\CompanyGenerated;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Models\Company;
use App\Utilities\Currency\CurrencyAccessor;

class ConfigureChartOfAccounts
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompanyGenerated $event): void
    {
        $company = $event->company;

        $this->createChartOfAccounts($company);
    }

    public function createChartOfAccounts(Company $company): void
    {
        $chartOfAccounts = config('chart-of-accounts.default');

        foreach ($chartOfAccounts as $type => $subtypes) {
            foreach ($subtypes as $subtypeName => $subtypeConfig) {
                $subtype = AccountSubtype::create([
                    'company_id' => $company->id,
                    'multi_currency' => $subtypeConfig['multi_currency'] ?? false,
                    'category' => AccountType::from($type)->getCategory()->value,
                    'type' => $type,
                    'name' => $subtypeName,
                    'description' => $subtypeConfig['description'] ?? 'No description available.',
                ]);

                $this->createDefaultAccounts($company, $subtype, $subtypeConfig);
            }
        }
    }

    private function createDefaultAccounts(Company $company, AccountSubtype $subtype, array $subtypeConfig): void
    {
        if (isset($subtypeConfig['accounts']) && is_array($subtypeConfig['accounts'])) {
            $baseCode = $subtypeConfig['base_code'];

            foreach ($subtypeConfig['accounts'] as $accountName => $accountDetails) {
                $bankAccount = null;

                if ($subtypeConfig['multi_currency'] && isset($subtypeConfig['bank_account_type'])) {
                    $bankAccount = $this->createBankAccountForMultiCurrency($company, $subtypeConfig['bank_account_type']);
                }

                $account = Account::create([
                    'company_id' => $company->id,
                    'subtype_id' => $subtype->id,
                    'category' => $subtype->type->getCategory()->value,
                    'type' => $subtype->type->value,
                    'code' => $baseCode++,
                    'name' => $accountName,
                    'currency_code' => CurrencyAccessor::getDefaultCurrency(),
                    'description' => $accountDetails['description'] ?? 'No description available.',
                    'active' => true,
                    'default' => true,
                    'created_by' => $company->owner->id,
                    'updated_by' => $company->owner->id,
                ]);

                if ($bankAccount) {
                    $account->accountable()->associate($bankAccount);
                }

                $account->save();
            }
        }
    }

    private function createBankAccountForMultiCurrency(Company $company, string $bankAccountType): BankAccount
    {
        $bankAccountType = BankAccountType::from($bankAccountType) ?? BankAccountType::Other;

        return BankAccount::create([
            'company_id' => $company->id,
            'institution_id' => null,
            'type' => $bankAccountType,
            'number' => null,
            'enabled' => BankAccount::where('company_id', $company->id)->where('enabled', true)->doesntExist(),
            'created_by' => $company->owner->id,
            'updated_by' => $company->owner->id,
        ]);
    }
}
