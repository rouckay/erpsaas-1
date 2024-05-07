<?php

namespace App\Observers;

use App\Enums\Accounting\JournalEntryType;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Transaction;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        if ($transaction->type->isJournal()) {
            return;
        }

        [$debitAccount, $creditAccount] = $this->determineAccounts($transaction);

        if ($debitAccount === null || $creditAccount === null) {
            return;
        }

        $this->createJournalEntries($transaction, $debitAccount, $creditAccount);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $transaction->refresh(); // DO NOT REMOVE

        if ($transaction->type->isJournal() || $this->hasRelevantChanges($transaction) === false) {
            return;
        }

        $journalEntries = $transaction->journalEntries;

        $debitEntry = $journalEntries->where('type', JournalEntryType::Debit)->first();
        $creditEntry = $journalEntries->where('type', JournalEntryType::Credit)->first();

        if ($debitEntry === null || $creditEntry === null) {
            return;
        }

        [$debitAccount, $creditAccount] = $this->determineAccounts($transaction);

        if ($debitAccount === null || $creditAccount === null) {
            return;
        }

        $convertedTransactionAmount = $this->getConvertedTransactionAmount($transaction);

        $this->updateJournalEntriesForTransaction($debitEntry, $debitAccount, $convertedTransactionAmount);
        $this->updateJournalEntriesForTransaction($creditEntry, $creditAccount, $convertedTransactionAmount);
    }

    /**
     * Handle the Transaction "deleting" event.
     */
    public function deleting(Transaction $transaction): void
    {
        DB::transaction(static function () use ($transaction) {
            $transaction->journalEntries()->each(fn (JournalEntry $entry) => $entry->delete());
        });
    }

    private function determineAccounts(Transaction $transaction): array
    {
        $chartAccount = $transaction->account;
        $bankAccount = $transaction->bankAccount?->account;

        $debitAccount = $transaction->type->isWithdrawal() ? $chartAccount : $bankAccount;
        $creditAccount = $transaction->type->isWithdrawal() ? $bankAccount : $chartAccount;

        return [$debitAccount, $creditAccount];
    }

    private function createJournalEntries(Transaction $transaction, Account $debitAccount, Account $creditAccount): void
    {
        $convertedTransactionAmount = $this->getConvertedTransactionAmount($transaction);

        $debitAccount->journalEntries()->create([
            'company_id' => $transaction->company_id,
            'transaction_id' => $transaction->id,
            'type' => JournalEntryType::Debit,
            'amount' => $convertedTransactionAmount,
            'description' => $transaction->description,
        ]);

        $creditAccount->journalEntries()->create([
            'company_id' => $transaction->company_id,
            'transaction_id' => $transaction->id,
            'type' => JournalEntryType::Credit,
            'amount' => $convertedTransactionAmount,
            'description' => $transaction->description,
        ]);
    }

    private function getConvertedTransactionAmount(Transaction $transaction): string
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
        $bankAccountCurrency = $transaction->bankAccount->account->currency_code;
        $chartAccountCurrency = $transaction->account->currency_code;

        if ($bankAccountCurrency !== $defaultCurrency) {
            return $this->convertToDefaultCurrency($transaction->amount, $bankAccountCurrency, $defaultCurrency);
        } elseif ($chartAccountCurrency !== $defaultCurrency) {
            return $this->convertToDefaultCurrency($transaction->amount, $chartAccountCurrency, $defaultCurrency);
        }

        return $transaction->amount;
    }

    private function convertToDefaultCurrency(string $amount, string $fromCurrency, string $toCurrency): string
    {
        $amountInCents = CurrencyConverter::prepareForAccessor($amount, $fromCurrency);

        $convertedAmountInCents = CurrencyConverter::convertBalance($amountInCents, $fromCurrency, $toCurrency);

        return CurrencyConverter::prepareForMutator($convertedAmountInCents, $toCurrency);
    }

    private function hasRelevantChanges(Transaction $transaction): bool
    {
        return $transaction->wasChanged(['amount', 'account_id', 'bank_account_id', 'type']);
    }

    private function updateJournalEntriesForTransaction(JournalEntry $journalEntry, Account $account, string $convertedTransactionAmount): void
    {
        DB::transaction(static function () use ($journalEntry, $account, $convertedTransactionAmount) {
            $journalEntry->update([
                'account_id' => $account->id,
                'amount' => $convertedTransactionAmount,
            ]);
        });
    }
}
