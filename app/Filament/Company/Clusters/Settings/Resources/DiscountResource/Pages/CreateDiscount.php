<?php

namespace App\Filament\Company\Clusters\Settings\Resources\DiscountResource\Pages;

use App\Concerns\HandlesResourceRecordCreation;
use App\Enums\Setting\DiscountType;
use App\Filament\Company\Clusters\Settings\Resources\DiscountResource;
use App\Models\Setting\Discount;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateDiscount extends CreateRecord
{
    use HandlesResourceRecordCreation;

    protected static string $resource = DiscountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['enabled'] = (bool) $data['enabled'];

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        if (! $user) {
            throw new Halt('No authenticated user found');
        }

        $evaluatedTypes = [DiscountType::Sales, DiscountType::Purchase];

        return $this->handleRecordCreationWithUniqueField($data, new Discount(), $user, 'type', $evaluatedTypes);
    }
}
