<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockedSlotResource\Pages;
use App\Models\BlockedSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlockedSlotResource extends Resource
{
    protected static ?string $model = BlockedSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Torneio';

    protected static ?string $modelLabel = 'Bloqueio';

    protected static ?string $pluralModelLabel = 'Bloqueios de horário';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('court_id')
                    ->label('Quadra')
                    ->relationship('court', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DateTimePicker::make('start_datetime')
                    ->label('Início')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                Forms\Components\DateTimePicker::make('end_datetime')
                    ->label('Fim')
                    ->native(false)
                    ->seconds(false)
                    ->required()
                    ->after('start_datetime'),
                Forms\Components\TextInput::make('reason')
                    ->label('Motivo')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('court.name')
                    ->label('Quadra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_datetime')
                    ->label('Fim')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
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
            ->defaultSort('start_datetime')
            ->filters([
                Tables\Filters\SelectFilter::make('court_id')
                    ->label('Quadra')
                    ->relationship('court', 'name'),
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
            'index' => Pages\ListBlockedSlots::route('/'),
            'create' => Pages\CreateBlockedSlot::route('/create'),
            'edit' => Pages\EditBlockedSlot::route('/{record}/edit'),
        ];
    }
}
