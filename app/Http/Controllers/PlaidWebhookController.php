<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTransactionUpdate;
use App\Models\Company;
use Illuminate\Http\Request;

class PlaidWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        if ($payload['webhook_type'] === 'TRANSACTIONS') {
            $this->handleTransactionsWebhook($payload);
        }

        return response()->json(['message' => 'Plaid Transaction Webhook Received']);
    }

    private function handleTransactionsWebhook(array $payload): void
    {
        if ($payload['webhook_code'] === 'DEFAULT_UPDATE') {
            $this->handleDefaultUpdate($payload);
        }
    }

    private function handleDefaultUpdate(array $payload): void
    {
        $newTransactions = $payload['new_transactions'];
        $itemID = $payload['item_id'];

        $company = Company::whereHas('connectedBankAccounts', static function ($query) use ($itemID) {
            $query->where('item_id', $itemID);
        })->first();

        if ($company && $newTransactions > 0) {
            ProcessTransactionUpdate::dispatch($company, $itemID)
                ->onQueue('transactions');
        }
    }
}
