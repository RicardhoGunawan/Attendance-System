<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Schedules';
    protected static ?string $navigationGroup = 'Office Management';

    protected static ?string $modelLabel = 'Jadwal';
    protected static ?string $pluralLabel = 'Jadwal';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->check() && auth()->user()->hasRole('employee')) {
            return $query->where('user_id', auth()->id());
        }

        return $query;
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()->schema([
                Grid::make(2)->schema([
                    Select::make('user_id')
                        ->label('Pegawai')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(1),

                    Select::make('shift_id')
                        ->label('Shift')
                        ->relationship('shift', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(1),

                    Select::make('office_id')
                        ->label('Office')
                        ->relationship('office', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(1),

                    Toggle::make('status')
                        ->label('Work From Anywhere (WFA)')
                        ->helperText('Izinkan pegawai untuk bekerja dari mana saja')
                        ->default(false)
                        ->required()
                        ->columnSpan(1),
                ])
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift')
                    ->sortable(),

                Tables\Columns\TextColumn::make('office.name')
                    ->label('Office')
                    ->sortable(),

                IconColumn::make('status')
                    ->label('WFA Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-home')
                    ->falseIcon('heroicon-o-building-office')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn(Schedule $record): string => $record->status
                        ? 'Work From Anywhere Active'
                        : 'Office Only'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shift_id')
                    ->label('Filter Shift')
                    ->relationship('shift', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('office_id')
                    ->label('Filter Office')
                    ->relationship('office', 'name')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('WFA Status')
                    ->placeholder('All Statuses')
                    ->trueLabel('WFA Active')
                    ->falseLabel('Office Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('View Schedule Details'),

                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Schedule'),

                Tables\Actions\Action::make('toggleWFA')
                    ->label(fn(Schedule $record): string => $record->status ? 'Disable WFA' : 'Enable WFA')
                    ->icon(fn(Schedule $record): string => $record->status ? 'heroicon-o-building-office' : 'heroicon-o-home')
                    ->color(fn(Schedule $record): string => $record->status ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Schedule $record): void {
                        $record->update(['status' => !$record->status]);
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enableWFA')
                    ->label('Enable WFA')
                    ->icon('heroicon-o-home')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn($records) => $records->each->update(['status' => true]))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('disableWFA')
                    ->label('Disable WFA')
                    ->icon('heroicon-o-building-office')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn($records) => $records->each->update(['status' => false]))
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', true)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}