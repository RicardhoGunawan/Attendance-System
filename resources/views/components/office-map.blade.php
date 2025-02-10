<!-- resources/views/components/office-map.blade.php -->
<div>
    <!-- Link Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <!-- Container untuk peta -->
    <div id="map" style="height: 400px;" wire:ignore></div>
    
    <!-- Loading indicator -->
    <div id="loading-location" style="display: none; text-align: center; margin-top: 10px;">
        <span class="text-sm text-gray-600">Mendapatkan lokasi Anda...</span>
    </div>

    <!-- Script Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <script>
        function initializeMap() {
            // Inisialisasi dengan default koordinat Indonesia
            let initialLat = {{ $getState()['latitude'] ?? -6.200000 }};
            let initialLng = {{ $getState()['longitude'] ?? 106.816666 }};
            let hasExistingCoordinates = {{ ($getState()['latitude'] && $getState()['longitude']) ? 'true' : 'false' }};
            
            // Inisialisasi peta
            const map = L.map('map').setView([initialLat, initialLng], 13);
            
            // Tambahkan tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Tambahkan marker
            let marker = L.marker([initialLat, initialLng], {
                draggable: true
            }).addTo(map);

            // Jika tidak ada koordinat yang tersimpan, gunakan geolocation
            if (!hasExistingCoordinates) {
                document.getElementById('loading-location').style.display = 'block';
                
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        // Update marker dan view peta
                        marker.setLatLng([latitude, longitude]);
                        map.setView([latitude, longitude], 15);

                        // Update form values
                        updateCoordinates(latitude, longitude);
                        
                        document.getElementById('loading-location').style.display = 'none';
                    }, function(error) {
                        console.error("Error getting location:", error);
                        document.getElementById('loading-location').style.display = 'none';
                        // Tetap menggunakan koordinat default jika gagal
                    }, {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    });
                }
            }

            // Update koordinat saat marker di-drag
            marker.on('dragend', function(e) {
                let position = marker.getLatLng();
                updateCoordinates(position.lat, position.lng);
            });

            // Update koordinat saat peta diklik
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateCoordinates(e.latlng.lat, e.latlng.lng);
            });

            // Fungsi untuk update nilai form
            function updateCoordinates(lat, lng) {
                @this.set('data.latitude', lat);
                @this.set('data.longitude', lng);
            }

            // Tambahkan tombol "My Location"
            L.Control.MyLocation = L.Control.extend({
                onAdd: function(map) {
                    const button = L.DomUtil.create('button', 'leaflet-bar leaflet-control');
                    button.innerHTML = '📍';
                    button.title = 'Lokasi Saya';
                    button.style.width = '30px';
                    button.style.height = '30px';
                    button.style.backgroundColor = 'white';
                    button.style.cursor = 'pointer';
                    button.style.border = '2px solid rgba(0,0,0,0.2)';
                    button.style.borderRadius = '4px';

                    button.onclick = function() {
                        document.getElementById('loading-location').style.display = 'block';
                        if ("geolocation" in navigator) {
                            navigator.geolocation.getCurrentPosition(function(position) {
                                const latitude = position.coords.latitude;
                                const longitude = position.coords.longitude;

                                marker.setLatLng([latitude, longitude]);
                                map.setView([latitude, longitude], 15);
                                updateCoordinates(latitude, longitude);
                                
                                document.getElementById('loading-location').style.display = 'none';
                            }, function(error) {
                                console.error("Error getting location:", error);
                                document.getElementById('loading-location').style.display = 'none';
                                alert('Tidak dapat mendapatkan lokasi Anda. Pastikan GPS aktif dan izin lokasi diberikan.');
                            }, {
                                enableHighAccuracy: true,
                                timeout: 5000,
                                maximumAge: 0
                            });
                        }
                    };

                    return button;
                }
            });

            // Tambahkan kontrol "My Location" ke peta
            new L.Control.MyLocation({ position: 'topleft' }).addTo(map);

            // Listen untuk perubahan nilai dari form input
            document.addEventListener('livewire:initialized', () => {
                @this.on('updateMap', (event) => {
                    let newLat = event.latitude;
                    let newLng = event.longitude;
                    
                    if (newLat && newLng) {
                        marker.setLatLng([newLat, newLng]);
                        map.setView([newLat, newLng], 13);
                    }
                });
            });
        }

        // Inisialisasi peta setelah DOM loaded
        document.addEventListener('DOMContentLoaded', initializeMap);
    </script>
</div>