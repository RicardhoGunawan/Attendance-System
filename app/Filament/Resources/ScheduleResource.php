<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Filament\Resources\ScheduleResource\RelationManagers;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Office Management';

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()->hasRole('admin');
    // }


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Pegawai')
                    ->relationship('user', 'name')
                    ->required(),

                Select::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'name')
                    ->required(),

                Select::make('office_id')
                    ->label('Office')
                    ->relationship('office', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Pegawai')->sortable(),
                Tables\Columns\TextColumn::make('shift.name')->label('Shift')->sortable(),
                Tables\Columns\TextColumn::make('office.name')->label('Office')->sortable(),
            ])
            ->filters([
                // Add custom filters if necessary
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
