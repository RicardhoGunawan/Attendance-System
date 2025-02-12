<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 0.5rem;
        }

        .stat-card {
            @apply p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700;
        }

        .user-location {
            background: none;
            border: none;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Attendance Dashboard</h1>
            <p class="text-gray-600">{{ now()->format('l, F j, Y') }}</p>
        </div>

        <!-- Today's Status -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <!-- Add shift information in the Today's Status card -->
            <div class="stat-card">
                <h3 class="text-xl font-bold">Today's Status</h3>
                @if($schedule)
                    <p class="mt-2">
                        <span class="font-semibold">Office:</span>
                        {{ $schedule->office->name }}
                    </p>
                    <p class="mt-1">
                        <span class="font-semibold">Shift:</span>
                        {{ $schedule->shift->name }} ({{ $schedule->shift->start_time }} - {{ $schedule->shift->end_time }})
                    </p>
                @endif
                @if($attendance)
                        <p class="mt-2">
                            <span class="font-semibold">Check-in:</span>
                            {{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') }}
                        </p>
                        @if($attendance->check_out)
                            <p class="mt-1">
                                <span class="font-semibold">Check-out:</span>
                                {{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') }}
                            </p>
                        @endif
                        <span class="inline-block mt-2 px-3 py-1 text-sm font-semibold rounded-full
                    @if($attendance->status === 'present') bg-green-100 text-green-800
                    @elseif($attendance->status === 'late') bg-yellow-100 text-yellow-800
                    @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($attendance->status) }}
                        </span>
                @else
                    <p class="mt-2 text-gray-600">No attendance recorded yet</p>
                @endif
            </div>

            <!-- Monthly Statistics -->
            <div class="stat-card">
                <h3 class="text-xl font-bold">Present</h3>
                <p class="mt-2 text-3xl font-bold text-green-600">{{ $monthlyStats['present'] }}</p>
                <p class="text-sm text-gray-600">This month</p>
            </div>

            <div class="stat-card">
                <h3 class="text-xl font-bold">Late</h3>
                <p class="mt-2 text-3xl font-bold text-yellow-600">{{ $monthlyStats['late'] }}</p>
                <p class="text-sm text-gray-600">This month</p>
            </div>

            <div class="stat-card">
                <h3 class="text-xl font-bold">Attendance Rate</h3>
                <p class="mt-2 text-3xl font-bold text-blue-600">{{ $monthlyStats['attendance_rate'] }}%</p>
                <p class="text-sm text-gray-600">This month</p>
            </div>
        </div>

        <!-- Map and Action Buttons -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-bold mb-4">Office Location</h2>
                    <div id="map"></div>
                    <div id="locationStatus" class="mt-2 text-sm text-gray-600"></div>
                </div>
            </div>
            <div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-bold mb-4">Actions</h2>
                    @if(!$attendance)
                        <button id="checkInBtn"
                            class="w-full mb-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Check In
                        </button>
                    @elseif(!$attendance->check_out)
                        <button id="checkOutBtn"
                            class="w-full mb-4 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Check Out
                        </button>
                    @else
                        <button disabled
                            class="w-full mb-4 bg-gray-400 text-white font-bold py-2 px-4 rounded cursor-not-allowed">
                            Attendance Completed
                        </button>
                    @endif
                    <div id="status" class="mt-4 text-sm"></div>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Attendance History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Check In</th>
                            <th class="px-6 py-3">Check Out</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $record)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $record->date }}</td>
                                <td class="px-6 py-4">{{ $record->check_in }}</td>
                                <td class="px-6 py-4">{{ $record->check_out ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                        @if($record->status === 'present') bg-green-100 text-green-800
                                                        @elseif($record->status === 'late') bg-yellow-100 text-yellow-800
                                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $record->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $history->links() }}
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let map, userMarker, officeMarker, circle;
            let currentPosition = null;

            // Initialize map
            const schedule = @json($schedule);
            const mapContainer = document.getElementById('map');
            const locationStatus = document.getElementById('locationStatus');

            function initMap() {
                if (!schedule || !schedule.office) {
                    mapContainer.innerHTML = 'No office location available';
                    return;
                }

                try {
                    const office = schedule.office;
                    // Initialize the map with office location
                    map = L.map('map').setView([office.latitude, office.longitude], 15);

                    // Add tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    // Add office marker
                    officeMarker = L.marker([office.latitude, office.longitude])
                        .bindPopup(office.name)
                        .addTo(map);

                    // Add radius circle
                    circle = L.circle([office.latitude, office.longitude], {
                        radius: office.radius,
                        color: 'blue',
                        fillColor: '#30f',
                        fillOpacity: 0.1
                    }).addTo(map);

                    // Start watching user location
                    if (navigator.geolocation) {
                        locationStatus.textContent = 'Getting your location...';
                        navigator.geolocation.watchPosition(updateUserLocation, handleLocationError, {
                            enableHighAccuracy: true
                        });
                    } else {
                        locationStatus.textContent = 'Geolocation is not supported by your browser';
                    }
                } catch (error) {
                    console.error('Map initialization error:', error);
                    mapContainer.innerHTML = 'Error initializing map';
                }
            }

            function calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371000; // Earth's radius in meters
                const φ1 = lat1 * Math.PI / 180;
                const φ2 = lat2 * Math.PI / 180;
                const Δφ = (lat2 - lat1) * Math.PI / 180;
                const Δλ = (lon2 - lon1) * Math.PI / 180;

                const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                return R * c;
            }

            function updateUserLocation(position) {
                if (!map) return;

                currentPosition = position;
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                if (userMarker) {
                    userMarker.setLatLng([lat, lng]);
                } else {
                    userMarker = L.marker([lat, lng], {
                        icon: L.divIcon({
                            html: '<div class="h-4 w-4 bg-red-500 rounded-full"></div>',
                            className: 'user-location'
                        })
                    }).addTo(map);
                }

                // Calculate distance from office
                const distance = calculateDistance(
                    lat, lng,
                    office.latitude, office.longitude
                );

                // Update location status
                locationStatus.textContent = `Distance from office: ${Math.round(distance)}m`;

                // Fit bounds to show both markers
                const bounds = L.latLngBounds([
                    [lat, lng],
                    [office.latitude, office.longitude]
                ]);
                map.fitBounds(bounds);
            }

            function handleLocationError(error) {
                console.error('Geolocation error:', error);
                locationStatus.textContent = `Location error: ${error.message}`;
                document.getElementById('status').innerHTML = `Location error: ${error.message}`;
            }

            // Initialize map
            initMap();

            // Check In button handler
            // Update check-in handler
            const checkInBtn = document.getElementById('checkInBtn');
            if (checkInBtn) {
                checkInBtn.addEventListener('click', async () => {
                    if (!currentPosition) {
                        document.getElementById('status').innerHTML = 'Waiting for location...';
                        return;
                    }

                    const statusElement = document.getElementById('status');
                    statusElement.innerHTML = 'Processing check-in...';

                    try {
                        const response = await fetch('/attendance/check-in', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                latitude: currentPosition.coords.latitude,
                                longitude: currentPosition.coords.longitude
                            })
                        });

                        const data = await response.json();
                        if (response.ok) {
                            statusElement.innerHTML = 'Check-in successful! Refreshing...';
                            window.location.reload();
                        } else {
                            statusElement.innerHTML = `Error: ${data.message}`;
                        }
                    } catch (error) {
                        console.error('Check-in error:', error);
                        statusElement.innerHTML = 'An error occurred during check-in';
                    }
                });
            }

            // Check Out button handler
            const checkOutBtn = document.getElementById('checkOutBtn');
            if (checkOutBtn) {
                checkOutBtn.addEventListener('click', async () => {
                    if (!currentPosition) {
                        document.getElementById('status').innerHTML = 'Waiting for location...';
                        return;
                    }

                    const statusElement = document.getElementById('status');
                    statusElement.innerHTML = 'Processing check-out...';

                    try {
                        const response = await fetch('/attendance/check-out', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                latitude: currentPosition.coords.latitude,
                                longitude: currentPosition.coords.longitude
                            })
                        });

                        const data = await response.json();
                        if (response.ok) {
                            statusElement.innerHTML = 'Check-out successful! Refreshing...';
                            window.location.reload();
                        } else {
                            statusElement.innerHTML = `Error: ${data.message}`;
                        }
                    } catch (error) {
                        console.error('Check-out error:', error);
                        statusElement.innerHTML = 'An error occurred during check-out';
                    }
                });
            }
        });
    </script>
</body>

</html>