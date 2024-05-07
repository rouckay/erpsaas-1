<?php

namespace App\Contracts;

use App\DTO\AccountBalanceDTO;
use App\DTO\AccountBalanceReportDTO;
use App\Enums\Accounting\AccountCategory;
use App\Models\Accounting\Account;
use App\ValueObjects\Money;

interface AccountHandler
{
    public function getDebitBalance(Account $account, string $startDate, string $endDate): Money;

    public function getCreditBalance(Account $account, string $startDate, string $endDate): Money;

    public function getNetMovement(Account $account, string $startDate, string $endDate): Money;

    public function getStartingBalance(Account $account, string $startDate): ?Money;

    public function getEndingBalance(Account $account, string $startDate, string $endDate): ?Money;

    public function calculateNetMovementByCategory(AccountCategory $category, int $debitBalance, int $creditBalance): int;

    public function getBalances(Account $account, string $startDate, string $endDate): array;

    public function formatBalances(array $balances): AccountBalanceDTO;

    public function buildAccountBalanceReport(string $startDate, string $endDate): AccountBalanceReportDTO;

    public function getTotalBalanceForAllBankAccounts(string $startDate, string $endDate): Money;

    public function getAccountCategoryOrder(): array;

    public function getEarliestTransactionDate(): string;
}
