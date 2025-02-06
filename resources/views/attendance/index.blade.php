// resources/views/attendance/index.blade.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-8">Sistem Presensi</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Presensi Hari Ini</h2>
            
            @if (!$attendance)
                <form action="{{ route('attendance.check-in') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                        Check In
                    </button>
                </form>
            @elseif (!$attendance->check_out)
                <form action="{{ route('attendance.check-out') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">
                        Check Out
                    </button>
                </form>
            @else
                <p class="text-gray-600">Anda sudah melakukan check-in dan check-out hari ini.</p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Riwayat Presensi</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Tanggal</th>
                            <th class="px-4 py-2 text-left">Check In</th>
                            <th class="px-4 py-2 text-left">Check Out</th>
                            <th class="px-4 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $record)
                            <tr>
                                <td class="border px-4 py-2">{{ $record->date }}</td>
                                <td class="border px-4 py-2">{{ $record->check_in }}</td>
                                <td class="border px-4 py-2">{{ $record->check_out }}</td>
                                <td class="border px-4 py-2">{{ $record->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $history->links() }}
            </div>
        </div>
    </div>
</body>
</html>