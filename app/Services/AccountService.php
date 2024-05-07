<?php

namespace App\Services;

use App\Contracts\AccountHandler;
use App\DTO\AccountBalanceDTO;
use App\DTO\AccountBalanceReportDTO;
use App\DTO\AccountCategoryDTO;
use App\DTO\AccountDTO;
use App\Enums\Accounting\AccountCategory;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Repositories\Accounting\JournalEntryRepository;
use App\Utilities\Currency\CurrencyAccessor;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection;

class AccountService implements AccountHandler
{
    protected JournalEntryRepository $journalEntryRepository;

    public function __construct(JournalEntryRepository $journalEntryRepository)
    {
        $this->journalEntryRepository = $journalEntryRepository;
    }

    public function getDebitBalance(Account $account, string $startDate, string $endDate): Money
    {
        $amount = $this->journalEntryRepository->sumDebitAmounts($account, $startDate, $endDate);

        return new Money($amount, $account->currency_code);
    }

    public function getCreditBalance(Account $account, string $startDate, string $endDate): Money
    {
        $amount = $this->journalEntryRepository->sumCreditAmounts($account, $startDate, $endDate);

        return new Money($amount, $account->currency_code);
    }

    public function getNetMovement(Account $account, string $startDate, string $endDate): Money
    {
        $debitBalance = $this->journalEntryRepository->sumDebitAmounts($account, $startDate, $endDate);
        $creditBalance = $this->journalEntryRepository->sumCreditAmounts($account, $startDate, $endDate);
        $netMovement = $this->calculateNetMovementByCategory($account->category, $debitBalance, $creditBalance);

        return new Money($netMovement, $account->currency_code);
    }

    public function getStartingBalance(Account $account, string $startDate): ?Money
    {
        if (in_array($account->category, [AccountCategory::Expense, AccountCategory::Revenue], true)) {
            return null;
        }

        $debitBalanceBefore = $this->journalEntryRepository->sumDebitAmounts($account, $startDate);
        $creditBalanceBefore = $this->journalEntryRepository->sumCreditAmounts($account, $startDate);
        $startingBalance = $this->calculateNetMovementByCategory($account->category, $debitBalanceBefore, $creditBalanceBefore);

        return new Money($startingBalance, $account->currency_code);
    }

    public function getEndingBalance(Account $account, string $startDate, string $endDate): ?Money
    {
        if (in_array($account->category, [AccountCategory::Expense, AccountCategory::Revenue], true)) {
            return null;
        }

        $startingBalance = $this->getStartingBalance($account, $startDate)?->getAmount();
        $netMovement = $this->getNetMovement($account, $startDate, $endDate)->getAmount();
        $endingBalance = $startingBalance + $netMovement;

        return new Money($endingBalance, $account->currency_code);
    }

    public function calculateNetMovementByCategory(AccountCategory $category, int $debitBalance, int $creditBalance): int
    {
        return match ($category) {
            AccountCategory::Asset, AccountCategory::Expense => $debitBalance - $creditBalance,
            AccountCategory::Liability, AccountCategory::Equity, AccountCategory::Revenue => $creditBalance - $debitBalance,
        };
    }

    public function getBalances(Account $account, string $startDate, string $endDate): array
    {
        $debitBalance = $this->getDebitBalance($account, $startDate, $endDate)->getAmount();
        $creditBalance = $this->getCreditBalance($account, $startDate, $endDate)->getAmount();
        $netMovement = $this->getNetMovement($account, $startDate, $endDate)->getAmount();

        $balances = [
            'debit_balance' => $debitBalance,
            'credit_balance' => $creditBalance,
            'net_movement' => $netMovement,
        ];

        if (! in_array($account->category, [AccountCategory::Expense, AccountCategory::Revenue], true)) {
            $balances['starting_balance'] = $this->getStartingBalance($account, $startDate)?->getAmount();
            $balances['ending_balance'] = $this->getEndingBalance($account, $startDate, $endDate)?->getAmount();
        }

        return $balances;
    }

    public function formatBalances(array $balances): AccountBalanceDTO
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        foreach ($balances as $key => $balance) {
            $balances[$key] = money($balance, $defaultCurrency)->format();
        }

        return new AccountBalanceDTO(
            startingBalance: $balances['starting_balance'] ?? null,
            debitBalance: $balances['debit_balance'],
            creditBalance: $balances['credit_balance'],
            netMovement: $balances['net_movement'] ?? null,
            endingBalance: $balances['ending_balance'] ?? null,
        );
    }

    public function buildAccountBalanceReport(string $startDate, string $endDate): AccountBalanceReportDTO
    {
        $allCategories = $this->getAccountCategoryOrder();

        $categoryGroupedAccounts = Account::whereHas('journalEntries')
            ->select('id', 'name', 'currency_code', 'category', 'code')
            ->get()
            ->groupBy(fn (Account $account) => $account->category->getPluralLabel())
            ->sortBy(static fn (Collection $groupedAccounts, string $key) => array_search($key, $allCategories, true));

        $accountCategories = [];
        $reportTotalBalances = [
            'debit_balance' => 0,
            'credit_balance' => 0,
        ];

        foreach ($allCategories as $categoryName) {
            $accountsInCategory = $categoryGroupedAccounts[$categoryName] ?? collect();
            $categorySummaryBalances = [
                'debit_balance' => 0,
                'credit_balance' => 0,
                'net_movement' => 0,
            ];

            if (! in_array($categoryName, [AccountCategory::Expense->getPluralLabel(), AccountCategory::Revenue->getPluralLabel()], true)) {
                $categorySummaryBalances['starting_balance'] = 0;
                $categorySummaryBalances['ending_balance'] = 0;
            }

            $categoryAccounts = [];

            foreach ($accountsInCategory as $account) {
                /** @var Account $account */
                $accountBalances = $this->getBalances($account, $startDate, $endDate);

                if (array_sum($accountBalances) === 0) {
                    continue;
                }

                foreach ($accountBalances as $accountBalanceType => $accountBalance) {
                    $categorySummaryBalances[$accountBalanceType] += $accountBalance;
                }

                $formattedAccountBalances = $this->formatBalances($accountBalances);

                $categoryAccounts[] = new AccountDTO(
                    $account->name,
                    $account->code,
                    $formattedAccountBalances,
                );
            }

            $reportTotalBalances['debit_balance'] += $categorySummaryBalances['debit_balance'];
            $reportTotalBalances['credit_balance'] += $categorySummaryBalances['credit_balance'];

            $formattedCategorySummaryBalances = $this->formatBalances($categorySummaryBalances);

            $accountCategories[$categoryName] = new AccountCategoryDTO(
                $categoryAccounts,
                $formattedCategorySummaryBalances,
            );
        }

        $formattedReportTotalBalances = $this->formatBalances($reportTotalBalances);

        return new AccountBalanceReportDTO($accountCategories, $formattedReportTotalBalances);
    }

    public function getTotalBalanceForAllBankAccounts(string $startDate, string $endDate): Money
    {
        $bankAccountsAccounts = Account::where('accountable_type', BankAccount::class)
            ->get();

        $totalBalance = 0;

        // Get ending balance for each bank account
        foreach ($bankAccountsAccounts as $account) {
            $endingBalance = $this->getEndingBalance($account, $startDate, $endDate)?->getAmount() ?? 0;
            $totalBalance += $endingBalance;
        }

        return new Money($totalBalance, CurrencyAccessor::getDefaultCurrency());
    }

    public function getAccountCategoryOrder(): array
    {
        return [
            AccountCategory::Asset->getPluralLabel(),
            AccountCategory::Liability->getPluralLabel(),
            AccountCategory::Equity->getPluralLabel(),
            AccountCategory::Revenue->getPluralLabel(),
            AccountCategory::Expense->getPluralLabel(),
        ];
    }

    public function getEarliestTransactionDate(): string
    {
        $earliestDate = Transaction::oldest('posted_at')
            ->value('posted_at');

        return $earliestDate ?? now()->format('Y-m-d');
    }
}
