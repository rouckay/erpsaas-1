<?php

namespace App\Filament\Company\Resources\StaffResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use App\Filament\Company\Resources\StaffResource;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->color("primary"),
            Actions\CreateAction::make(),
        ];
    }
}
