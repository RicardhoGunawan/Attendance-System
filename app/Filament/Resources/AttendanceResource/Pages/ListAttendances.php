<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
            Actions\Action::make('Tambah Presensi')
                ->url('/attendance') // URL tujuan
                ->color('success') // Warna tombol (opsional)
                ->icon('heroicon-o-calendar'), // Ikon tombol (opsional)
            
            Actions\CreateAction::make(),
        ];
    }
}
