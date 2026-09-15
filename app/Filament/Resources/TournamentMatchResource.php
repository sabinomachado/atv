<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TournamentMatchResource\Pages;
use App\Models\Court;
use App\Models\TournamentMatch;
use App\Services\MatchAvailabilityService;
use App\Services\TennisScoreService;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TournamentMatchResource extends Resource
{
    protected static ?string $model = TournamentMatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Torneio';

    protected static ?string $modelLabel = 'Confronto';

    protected static ?string $pluralModelLabel = 'Confrontos';

    /**
     * A match can't be "agendado"/"concluído" without a court and a
     * date/time — otherwise it renders as a broken null reference wherever
     * the schedule is displayed.
     */
    private const STATUSES_REQUIRING_SCHEDULE = [
        TournamentMatch::STATUS_SCHEDULED,
        TournamentMatch::STATUS_COMPLETED,
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Confronto')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('player1_id')
                            ->label('Jogador 1')
                            ->relationship('player1', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->different('player2_id'),
                        Forms\Components\Select::make('player2_id')
                            ->label('Jogador 2')
                            ->relationship('player2', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->different('player1_id'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                TournamentMatch::STATUS_PENDING => 'Pendente',
                                TournamentMatch::STATUS_SCHEDULED => 'Agendado',
                                TournamentMatch::STATUS_COMPLETED => 'Concluído',
                                TournamentMatch::STATUS_CANCELLED => 'Cancelado',
                            ])
                            ->required()
                            ->live()
                            ->default(TournamentMatch::STATUS_PENDING),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Agendamento')
                    ->schema([
                        Forms\Components\Select::make('court_id')
                            ->label('Quadra')
                            ->relationship('court', 'name')
                            ->live()
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get) => in_array($get('status'), self::STATUSES_REQUIRING_SCHEDULE, true)),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Data e hora')
                            ->native(false)
                            ->seconds(false)
                            ->live()
                            ->required(fn (Get $get) => in_array($get('status'), self::STATUSES_REQUIRING_SCHEDULE, true))
                            ->rules([
                                fn (Get $get, ?Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    if (blank($value) || blank($get('court_id')) || $get('override_availability')) {
                                        return;
                                    }

                                    $court = Court::find($get('court_id'));

                                    if (! $court) {
                                        return;
                                    }

                                    $reason = app(MatchAvailabilityService::class)
                                        ->unavailabilityReason($court, Carbon::parse($value), $record?->id);

                                    if ($reason !== null) {
                                        $fail(self::availabilityErrorMessage($reason));
                                    }
                                },
                            ])
                            ->helperText('Precisa respeitar a janela mínima de 90 minutos entre jogos na mesma quadra, bloqueios e os dias disponíveis da quadra.'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duração (min)')
                            ->required()
                            ->numeric()
                            ->default(90),
                        Forms\Components\Toggle::make('override_availability')
                            ->label('Forçar agendamento (ignorar validações)')
                            ->helperText('Use apenas em casos excepcionais: ignora conflitos, bloqueios e dias disponíveis da quadra.')
                            ->dehydrated(false)
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player1.name')
                    ->label('Jogador 1')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('player2.name')
                    ->label('Jogador 2')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('court.name')
                    ->label('Quadra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Data/hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duração (min)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('score')
                    ->label('Placar')
                    ->getStateUsing(fn (TournamentMatch $record) => $record->formattedScore() ?? '—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        TournamentMatch::STATUS_PENDING => 'Pendente',
                        TournamentMatch::STATUS_SCHEDULED => 'Agendado',
                        TournamentMatch::STATUS_COMPLETED => 'Concluído',
                        TournamentMatch::STATUS_CANCELLED => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        TournamentMatch::STATUS_PENDING => 'gray',
                        TournamentMatch::STATUS_SCHEDULED => 'warning',
                        TournamentMatch::STATUS_COMPLETED => 'success',
                        TournamentMatch::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at')
            ->filters([
                Tables\Filters\SelectFilter::make('court_id')
                    ->label('Quadra')
                    ->relationship('court', 'name'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        TournamentMatch::STATUS_PENDING => 'Pendente',
                        TournamentMatch::STATUS_SCHEDULED => 'Agendado',
                        TournamentMatch::STATUS_COMPLETED => 'Concluído',
                        TournamentMatch::STATUS_CANCELLED => 'Cancelado',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('registerResult')
                    ->label('Registrar resultado')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->visible(fn (TournamentMatch $record) => in_array($record->status, [
                        TournamentMatch::STATUS_SCHEDULED,
                        TournamentMatch::STATUS_COMPLETED,
                    ], true))
                    ->form(fn (TournamentMatch $record) => [
                        Forms\Components\Fieldset::make('1º set')
                            ->schema([
                                Forms\Components\TextInput::make('set1_player1')->label($record->player1->name)->numeric()->minValue(0)->required(),
                                Forms\Components\TextInput::make('set1_player2')->label($record->player2->name)->numeric()->minValue(0)->required(),
                            ])->columns(2),
                        Forms\Components\Fieldset::make('2º set')
                            ->schema([
                                Forms\Components\TextInput::make('set2_player1')->label($record->player1->name)->numeric()->minValue(0)->required(),
                                Forms\Components\TextInput::make('set2_player2')
                                    ->label($record->player2->name)
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    // Attached here (always required/filled) rather than on the optional
                                    // 3rd-set fields: Filament skips extra rules on a field left blank,
                                    // so a rule on set3_player2 alone would never run when it's empty.
                                    ->rules([
                                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            $data = [
                                                'set1_player1' => $get('set1_player1'),
                                                'set1_player2' => $get('set1_player2'),
                                                'set2_player1' => $get('set2_player1'),
                                                'set2_player2' => $value,
                                                'set3_player1' => $get('set3_player1'),
                                                'set3_player2' => $get('set3_player2'),
                                            ];

                                            if (self::hasIncompleteThirdSet($data)) {
                                                $fail('Preencha os dois placares do 3º set.');

                                                return;
                                            }

                                            $sets = self::setsFromResultFormData($data);

                                            if ($sets === null) {
                                                return;
                                            }

                                            $error = app(TennisScoreService::class)->validationError($sets);

                                            if ($error !== null) {
                                                $fail($error);
                                            }
                                        },
                                    ]),
                            ])->columns(2),
                        Forms\Components\Fieldset::make('3º set (super tie-break, se necessário)')
                            ->schema([
                                Forms\Components\TextInput::make('set3_player1')->label($record->player1->name)->numeric()->minValue(0),
                                Forms\Components\TextInput::make('set3_player2')->label($record->player2->name)->numeric()->minValue(0),
                            ])->columns(2),
                    ])
                    ->fillForm(fn (TournamentMatch $record) => [
                        'set1_player1' => $record->score[0]['player1'] ?? null,
                        'set1_player2' => $record->score[0]['player2'] ?? null,
                        'set2_player1' => $record->score[1]['player1'] ?? null,
                        'set2_player2' => $record->score[1]['player2'] ?? null,
                        'set3_player1' => $record->score[2]['player1'] ?? null,
                        'set3_player2' => $record->score[2]['player2'] ?? null,
                    ])
                    ->action(function (array $data, TournamentMatch $record) {
                        $sets = self::setsFromResultFormData($data);
                        $winner = app(TennisScoreService::class)->matchWinner($sets);

                        $record->update([
                            'score' => $sets,
                            'winner_player_id' => $winner === 'player1' ? $record->player1_id : $record->player2_id,
                            'status' => TournamentMatch::STATUS_COMPLETED,
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function hasIncompleteThirdSet(array $data): bool
    {
        return filled($data['set3_player1'] ?? null) !== filled($data['set3_player2'] ?? null);
    }

    /**
     * Builds the [{player1,player2}, ...] sets array from the result form's
     * flat fields, or null while the first two sets aren't fully filled yet.
     *
     * @return array<int, array{player1: int, player2: int}>|null
     */
    protected static function setsFromResultFormData(array $data): ?array
    {
        foreach (['set1_player1', 'set1_player2', 'set2_player1', 'set2_player2'] as $field) {
            if (! is_numeric($data[$field] ?? null)) {
                return null;
            }
        }

        $sets = [
            ['player1' => (int) $data['set1_player1'], 'player2' => (int) $data['set1_player2']],
            ['player1' => (int) $data['set2_player1'], 'player2' => (int) $data['set2_player2']],
        ];

        if (is_numeric($data['set3_player1'] ?? null) && is_numeric($data['set3_player2'] ?? null)) {
            $sets[] = ['player1' => (int) $data['set3_player1'], 'player2' => (int) $data['set3_player2']];
        }

        return $sets;
    }

    protected static function availabilityErrorMessage(string $reason): string
    {
        return match ($reason) {
            MatchAvailabilityService::REASON_COURT_UNAVAILABLE_ON_DAY => 'Esta quadra não está disponível neste dia da semana.',
            MatchAvailabilityService::REASON_BLOCKED_SLOT => 'Este horário está bloqueado nesta quadra.',
            MatchAvailabilityService::REASON_MATCH_CONFLICT => 'Já existe um jogo marcado nesta quadra dentro da janela de 90 minutos.',
            default => 'Horário indisponível para esta quadra.',
        };
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
            'index' => Pages\ListTournamentMatches::route('/'),
            'create' => Pages\CreateTournamentMatch::route('/create'),
            'edit' => Pages\EditTournamentMatch::route('/{record}/edit'),
        ];
    }
}
