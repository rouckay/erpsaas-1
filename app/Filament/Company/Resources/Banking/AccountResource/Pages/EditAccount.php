<?php

namespace App\Filament\Company\Resources\Banking\AccountResource\Pages;

use App\Concerns\HandlesResourceRecordUpdate;
use App\Filament\Company\Resources\Banking\AccountResource;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    use HandlesResourceRecordUpdate;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['enabled'] = (bool) ($data['enabled'] ?? false);

        return $data;
    }
}
