<?php

namespace App\Filament\Resources;

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Tables\Actions\ExportAction;
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

        if (auth()->user()->hasRole('employee')) {
            return $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->visible(fn() => auth()->user()->hasRole('admin')),

                Forms\Components\Select::make('office_id')
                    ->relationship('office', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),

                Forms\Components\TimePicker::make('check_in')
                    ->nullable(),

                Forms\Components\TimePicker::make('check_out')
                    ->nullable(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('check_in_latitude')
                        ->label('Check In Latitude')
                        ->numeric()
                        ->nullable(),

                    Forms\Components\TextInput::make('check_in_longitude')
                        ->label('Check In Longitude')
                        ->numeric()
                        ->nullable(),

                    Forms\Components\TextInput::make('check_out_latitude')
                        ->label('Check Out Latitude')
                        ->numeric()
                        ->nullable(),

                    Forms\Components\TextInput::make('check_out_longitude')
                        ->label('Check Out Longitude')
                        ->numeric()
                        ->nullable(),
                ]),

                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                    ])
                    ->default('present'),

                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->nullable()
                    ->rows(3),
            ])->columns(1)
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => auth()->user()->hasRole('admin')),

                Tables\Columns\TextColumn::make('office.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => auth()->user()->hasRole('admin')),


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
                Tables\Columns\TextColumn::make('overtime')
                    ->label('Lembur (menit)')
                    ->sortable()
                    ->default('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'late' => 'Late',
                        'absent' => 'Absent',
                    ]),
                Tables\Filters\SelectFilter::make('office')
                    ->relationship('office', 'name'),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->visible(fn() => auth()->user()->hasRole('admin')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()->hasRole('admin')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])->visible(fn() => auth()->user()->hasRole('admin')),
            ])
            
            ->headerActions([
                ExportAction::make()
                ->label('Export Data')
                    ->exporter(AttendanceExporter::class)
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