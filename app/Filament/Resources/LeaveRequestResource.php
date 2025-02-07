<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Attendance Management';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('employee')) {
            return $query->where('user_id', auth()->id());
        }

        return $query;
    }

    // Membatasi akses ke leave request hanya untuk user yang relevan
    protected function getTableActions(): array
    {
        if (auth()->user()->hasRole('employee')) {
            return [];
        }

        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    // Membatasi bulk actions untuk employee
    protected function getTableBulkActions(): array
    {
        if (auth()->user()->hasRole('employee')) {
            return [];
        }

        return [
            DeleteBulkAction::make(),
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
                            ->searchable()
                            ->default(fn() => auth()->user()->hasRole('employee') ? auth()->id() : null)
                            ->disabled(fn() => auth()->user()->hasRole('employee')),

                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->maxLength(65535)
                            ->rows(3),

                        Forms\Components\Hidden::make('status')
                            ->default('pending'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->visible(fn() => auth()->user()->hasRole('admin')),

                        Forms\Components\Textarea::make('admin_notes')
                            ->maxLength(65535)
                            ->nullable()
                            ->rows(3)
                            ->visible(fn() => auth()->user()->hasRole('admin') && !auth()->user()->hasRole('employee')),
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
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                // Menambahkan action approve untuk admin
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (LeaveRequest $record) {
                        // Menampilkan modal untuk memasukkan admin notes
                        $record->status = 'approved';
                        $record->save();
                    })
                    ->color('success')
                    ->visible(fn($record) => auth()->user()->hasRole('admin') && $record->status === 'pending'),

                // Menambahkan action reject untuk admin
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (LeaveRequest $record) {
                        // Menampilkan modal untuk memasukkan admin notes
                        $record->status = 'rejected';
                        $record->save();
                    })
                    ->color('danger')
                    ->visible(fn($record) => auth()->user()->hasRole('admin') && $record->status === 'pending'),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
