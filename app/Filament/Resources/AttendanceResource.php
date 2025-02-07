<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup = 'Attendance Management';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Membatasi akses hanya untuk attendance milik employee yang sedang login
        if (auth()->user()->hasRole('employee')) {
            return $query->where('user_id', auth()->id());
        }

        return $query;
    }

    // Membatasi actions untuk employee
    protected function getTableActions(): array
    {
        if (auth()->user()->hasRole('employee')) {
            // Employee tidak bisa edit atau hapus data
            return [];
        }

        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    // Membatasi bulk actions untuk employee
    protected function getTableBulkActions(): array
    {
        if (auth()->user()->hasRole('employee')) {
            // Employee tidak bisa melakukan bulk action
            return [];
        }

        return [
            Tables\Actions\DeleteBulkAction::make(),
        ];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\DatePicker::make('date')
                            ->required(),
                        Forms\Components\TimePicker::make('check_in')
                            ->nullable(),
                        Forms\Components\TimePicker::make('check_out')
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'present' => 'Present',
                                'late' => 'Late',
                                'absent' => 'Absent',
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->nullable()
                            ->rows(3),
                    ])
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->time()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'present',
                        'warning' => 'late',
                        'danger' => 'absent',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name'),
            ])
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
