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
            margin-bottom: 1rem;
        }
        .user-location {
            background: none;
            border: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Attendances</h1>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8 transition-transform transform hover:scale-[1.02] duration-300">
            <!-- Header Section -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-lg">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold">{{ auth()->user()->name }}</h4>
                        <p class="text-gray-500 text-sm">Employee ID: {{ auth()->user()->id }}</p>
                    </div>
                </div>
                <div class="text-gray-500 text-sm">
                    <p id="datetime" class="text-gray-800 mt-2"></p>
                </div>
            </div>

            @if($schedule)
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-600">🏢 Office:</span>
                        <p class="font-semibold text-gray-800">{{ $schedule->office->name }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-600">⏰ Shift:</span>
                        <p class="font-semibold text-gray-800">{{ $schedule->shift->start_time }} - {{ $schedule->shift->end_time }}</p>
                    </div>
                </div>
            @endif

            @if($attendance)
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-600">✅ Check-in:</span>
                        <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-gray-600">❌ Check-out:</span>
                        <p class="font-semibold text-gray-800">
                            {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') : '-' }}
                        </p>
                    </div>
                </div>

                <!-- Attendance Status -->
                <div class="flex items-center space-x-2 mt-4">
                    @php
                        $statusColor = [
                            'present' => 'bg-green-100 text-green-800',
                            'late' => 'bg-yellow-100 text-yellow-800',
                            'absent' => 'bg-red-100 text-red-800'
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusColor[$attendance->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($attendance->status) }}
                    </span>
                </div>
            @else
                <p class="mt-4 text-gray-500 text-center">No attendance recorded yet.</p>
            @endif
        </div>

        <!-- Map and Actions Combined -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Office Location</h2>
            <div id="map"></div>
            <div id="locationStatus" class="text-sm text-gray-600 mb-4"></div>
            
            <!-- Action Buttons -->
            @if(!$attendance)
                <button id="checkInBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded">
                    Check In
                </button>
            @elseif(!$attendance->check_out)
                <button id="checkOutBtn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded">
                    Check Out
                </button>
            @else
                <button disabled class="w-full bg-gray-400 text-white font-bold py-3 px-4 rounded cursor-not-allowed">
                    Attendance Completed
                </button>
            @endif
            <div id="status" class="mt-4 text-sm text-center"></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        document.addEventListener('DOMContentLoaded', function () {
            let map, userMarker, officeMarker, circle;
            let currentPosition = null;

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
                    map = L.map('map').setView([office.latitude, office.longitude], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    officeMarker = L.marker([office.latitude, office.longitude])
                        .bindPopup(office.name)
                        .addTo(map);

                    circle = L.circle([office.latitude, office.longitude], {
                        radius: office.radius,
                        color: 'blue',
                        fillColor: '#30f',
                        fillOpacity: 0.1
                    }).addTo(map);

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
                const R = 6371000;
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

                const distance = calculateDistance(
                    lat, lng,
                    schedule.office.latitude, schedule.office.longitude
                );

                locationStatus.textContent = `Distance from office: ${Math.round(distance)}m`;

                const bounds = L.latLngBounds([
                    [lat, lng],
                    [schedule.office.latitude, schedule.office.longitude]
                ]);
                map.fitBounds(bounds);
            }

            function handleLocationError(error) {
                console.error('Geolocation error:', error);
                locationStatus.textContent = `Location error: ${error.message}`;
                document.getElementById('status').innerHTML = `Location error: ${error.message}`;
            }

            initMap();

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
                            showNotification('Check-in successful!', 'success');
                            setTimeout(() => {
                                window.location.href = '/admin/attendances';
                            }, 2000);
                        } else {
                            statusElement.innerHTML = `Error: ${data.message}`;
                            showNotification(`Error: ${data.message}`, 'error');
                        }
                    } catch (error) {
                        console.error('Check-in error:', error);
                        statusElement.innerHTML = 'An error occurred during check-in';
                        showNotification('An error occurred during check-in', 'error');
                    }
                });
            }

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
                            showNotification('Check-out successful!', 'success');
                            setTimeout(() => {
                                window.location.href = '/admin/attendances';
                            }, 2000);
                        } else {
                            statusElement.innerHTML = `Error: ${data.message}`;
                            showNotification(`Error: ${data.message}`, 'error');
                        }
                    } catch (error) {
                        console.error('Check-out error:', error);
                        statusElement.innerHTML = 'An error occurred during check-out';
                        showNotification('An error occurred during check-out', 'error');
                    }
                });
            }

            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
                }`;
                notification.textContent = message;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        });
    </script>
</body>
</html>