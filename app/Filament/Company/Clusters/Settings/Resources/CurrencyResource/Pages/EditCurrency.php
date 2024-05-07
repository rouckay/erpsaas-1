<?php

namespace App\Filament\Company\Clusters\Settings\Resources\CurrencyResource\Pages;

use App\Concerns\HandlesResourceRecordUpdate;
use App\Filament\Company\Clusters\Settings\Resources\CurrencyResource;
use App\Models\Setting\Currency;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditCurrency extends EditRecord
{
    use HandlesResourceRecordUpdate;

    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['enabled'] = (bool) $data['enabled'];

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordUpdate(Model | Currency $record, array $data): Model | Currency
    {
        $user = Auth::user();

        if (! $user) {
            throw new Halt('No authenticated user found');
        }

        return $this->handleRecordUpdateWithUniqueField($record, $data, $user);
    }
}
