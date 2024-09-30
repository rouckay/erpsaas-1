<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\StaffResource\Pages;
use App\Filament\Company\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $tenantOwnershipRelationshipName = 'company';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('father_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('gender')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('tazkira')
                    ->maxLength(255),
                Forms\Components\Select::make('company')
                    ->relationship('company', 'id')
                ,
                Forms\Components\TextInput::make('degree')
                    ->maxLength(255),
                Forms\Components\TextInput::make('field_of_education')
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_of_university')
                    ->maxLength(255),
                Forms\Components\TextInput::make('work_experience')
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_of_organization')
                    ->maxLength(255),
                Forms\Components\TextInput::make('position')
                    ->maxLength(255),
                Forms\Components\TextInput::make('duration')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ref1')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ref2')
                    ->maxLength(255),
                Forms\Components\TextInput::make('ref3')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('father_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tazkira')
                    ->searchable(),
                Tables\Columns\TextColumn::make('degree')
                    ->searchable(),
                Tables\Columns\TextColumn::make('field_of_education')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_of_university')
                    ->searchable(),
                Tables\Columns\TextColumn::make('work_experience')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_of_organization')
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ref1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ref2')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ref3')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
