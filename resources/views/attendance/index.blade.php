<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Presensi - Employee Attendance Management System">
    <title>Sistem Presensi | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.10.7/dayjs.min.js"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-white min-h-screen flex flex-col" 
      x-data="{ 
          showHistory: true,
          currentTime: '',
          updateTime() {
              this.currentTime = new Date().toLocaleTimeString();
          }
      }"
      x-init="setInterval(() => updateTime(), 1000)">
    
    <!-- Header -->
    <header class="bg-blue-600 text-white py-4 shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h1 class="text-3xl font-bold">Sistem Presensi</h1>
                <span class="text-xl" x-text="currentTime"></span>
            </div>
            <nav class="flex items-center space-x-6">
                <a href="admin/attendances" class="text-white hover:underline">Home</a>
               
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-8">
        <!-- Alerts Container -->
        <div class="mb-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" 
                     x-data="{ show: true }" 
                     x-show="show">
                    <span class="block sm:inline">{{ session('success') }}</span>
                    <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                        <span class="sr-only">Close</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                        </svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                     x-data="{ show: true }" 
                     x-show="show">
                    <span class="block sm:inline">{{ session('error') }}</span>
                    <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                        <span class="sr-only">Close</span>
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        <!-- Today's Attendance Card -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8 hover:shadow-lg transition-shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-blue-700">Presensi Hari Ini</h2>
                <span class="text-gray-600" x-text="new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></span>
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                @if (!$attendance)
                    <form action="{{ route('attendance.check-in') }}" method="POST" class="mb-4 md:mb-0">
                        @csrf
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-3 rounded-full transition transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Check In
                            </span>
                        </button>
                    </form>
                @elseif (!$attendance->check_out)
                    <div class="mb-4 md:mb-0">
                        <p class="text-gray-600 mb-2">Check In Time: {{ $attendance->check_in }}</p>
                        <form action="{{ route('attendance.check-out') }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="bg-green-500 hover:bg-green-700 text-white px-6 py-3 rounded-full transition transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Check Out
                                </span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <p class="text-gray-600">Anda sudah melakukan check-in dan check-out hari ini.</p>
                        <p class="text-gray-600">Check In: {{ $attendance->check_in }}</p>
                        <p class="text-gray-600">Check Out: {{ $attendance->check_out }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Attendance History Section -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-blue-700">Riwayat Presensi</h2>
                <button class="bg-gray-300 px-4 py-2 rounded-full hover:bg-gray-400 transition flex items-center space-x-2"
                        @click="showHistory = !showHistory">
                    <span x-text="showHistory ? 'Sembunyikan' : 'Tampilkan'"></span>
                    <svg class="w-4 h-4" :class="{'rotate-180': !showHistory}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <div x-show="showHistory" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-4">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($history as $record)
                        <div class="border rounded-lg p-4 bg-blue-50 hover:bg-blue-100 shadow-sm transition transform hover:scale-105">
                            <div class="flex justify-between items-start mb-2">
                                <p class="text-gray-700 font-medium">{{ $record->date }}</p>
                                <span class="px-2 py-1 text-xs rounded-full @if($record->status === 'On Time') bg-green-200 text-green-800 @elseif($record->status === 'Late') bg-yellow-200 text-yellow-800 @else bg-red-200 text-red-800 @endif">
                                    {{ $record->status }}
                                </span>
                            </div>
                            <div class="space-y-1">
                                <p class="text-gray-600 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                    {{ $record->check_in }}
                                </p>
                                <p class="text-gray-600 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    {{ $record->check_out }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $history->links() }}
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-blue-600 text-white py-4 mt-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p>&copy; 2025 Sistem Presensi. All rights reserved.</p>
                <div class="mt-4 md:mt-0">
                    <a href="/privacy" class="hover:underline mr-4">Privacy Policy</a>
                    <a href="/terms" class="hover:underline">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>