<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Models\Office;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;


class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Office Management';

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()->hasRole('admin');
    // }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Office Name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('address')
                    ->label('Address')
                    ->required()
                    ->rows(3),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->label('Phone Number')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->label('Email Address')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('radius')
                    ->numeric()
                    ->label('Radius (in meters)')
                    ->required()
                    ->default(50)
                    ->minValue(1)
                    ->maxValue(1000)
                    ->helperText('Enter the allowed attendance radius (1-1000 meters)'),

                Forms\Components\Grid::make(2)->schema([
                    TextInput::make('latitude')
                        ->label('Latitude')
                        ->required()
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state) {
                                // Dispatch event ke map untuk update marker
                                $set('updateMap', [
                                    'latitude' => $state,
                                    'longitude' => $get('longitude'),
                                ]);
                            }
                        }),

                    TextInput::make('longitude')
                        ->label('Longitude')
                        ->required()
                        ->numeric()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state) {
                                // Dispatch event ke map untuk update marker
                                $set('updateMap', [
                                    'latitude' => $get('latitude'),
                                    'longitude' => $state,
                                ]);
                            }
                        }),
                ]),

                // Map Component
                Forms\Components\View::make('components.office-map')
                    ->columnSpanFull(),
            ])->columns(1),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('address')
                ->limit(50)
                ->searchable(),

            Tables\Columns\TextColumn::make('phone')
                ->searchable(),

            Tables\Columns\TextColumn::make('email')
                ->searchable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}