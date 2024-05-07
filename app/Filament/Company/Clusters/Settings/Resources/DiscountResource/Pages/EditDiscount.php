<?php

namespace App\Filament\Company\Clusters\Settings\Resources\DiscountResource\Pages;

use App\Concerns\HandlesResourceRecordUpdate;
use App\Enums\Setting\DiscountType;
use App\Filament\Company\Clusters\Settings\Resources\DiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditDiscount extends EditRecord
{
    use HandlesResourceRecordUpdate;

    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
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
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = auth()->user();

        if (! $user) {
            throw new Halt('No authenticated user found');
        }

        $evaluatedTypes = [DiscountType::Sales, DiscountType::Purchase];

        return $this->handleRecordUpdateWithUniqueField($record, $data, $user, 'type', $evaluatedTypes);
    }
}
