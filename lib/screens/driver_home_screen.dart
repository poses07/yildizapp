import 'dart:async';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import 'driver_history_screen.dart';
import 'driver_profile_screen.dart';
import 'chat_screen.dart';

import '../services/directions_service.dart';

class DriverHomeScreen extends StatefulWidget {
  final Map<String, dynamic> driverData;

  const DriverHomeScreen({super.key, required this.driverData});

  @override
  State<DriverHomeScreen> createState() => _DriverHomeScreenState();
}

enum DriverState { offline, online, riding }

enum RideStatus {
  goingToPickup,
  arrivedAtPickup,
  goingToDestination,
  completed,
}

class _DriverHomeScreenState extends State<DriverHomeScreen> {
  final Completer<GoogleMapController> _controller = Completer();
  final String _apiKey = "AIzaSyC180xlREmLzJJnQSKY1zZTCKIKa6AeiyE";
  late DirectionsService _directionsService;

  LatLng _currentPosition = const LatLng(41.0082, 28.9784); // Istanbul default
  bool _isLocationLoaded = false; // Konumun yüklendiğini takip etmek için
  bool _isOnline = false;
  DriverState _driverState = DriverState.offline;
  RideStatus _rideStatus = RideStatus.goingToPickup;
  final Set<Marker> _markers = {};
  final Set<Polyline> _polylines = {};
  Timer? _requestTimer;
  Timer? _locationTimer;
  bool _hasIncomingRequest = false;
  int _selectedIndex = 0;

  // Current Booking Data
  Map<String, dynamic>? _currentBooking;

  // Mock Ride Data
  LatLng _pickupLocation = const LatLng(40.9902, 29.0297); // Kadıköy Rıhtım
  LatLng _destinationLocation = const LatLng(
    40.9632,
    29.0656,
  ); // Bağdat Caddesi

  @override
  void initState() {
    super.initState();
    _directionsService = DirectionsService(_apiKey);
    _getCurrentLocation();
  }

  @override
  void dispose() {
    _requestTimer?.cancel();
    _locationTimer?.cancel();
    super.dispose();
  }

  Future<void> _getCurrentLocation() async {
    bool serviceEnabled;
    LocationPermission permission;

    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return;

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) return;
    }

    if (permission == LocationPermission.deniedForever) return;

    Position position = await Geolocator.getCurrentPosition();
    if (!mounted) return;
    setState(() {
      _currentPosition = LatLng(position.latitude, position.longitude);
      _isLocationLoaded = true; // Konum başarıyla alındı
    });

    final GoogleMapController controller = await _controller.future;
    controller.animateCamera(CameraUpdate.newLatLng(_currentPosition));
  }

  void _toggleOnlineStatus() async {
    if (!_isLocationLoaded) {
      // Eğer konum henüz alınamadıysa tekrar dene
      await _getCurrentLocation();
      if (!mounted) return;
      if (!_isLocationLoaded) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Konum alınamıyor. Lütfen GPS\'inizi kontrol edin.'),
          ),
        );
        return;
      }
    }

    setState(() {
      _isOnline = !_isOnline;
      _driverState = _isOnline ? DriverState.online : DriverState.offline;
    });

    if (_isOnline) {
      _checkForBookings();
      _startLocationUpdates();
    } else {
      _requestTimer?.cancel();
      _locationTimer?.cancel();
      setState(() {
        _hasIncomingRequest = false;
        _currentBooking = null;
      });
    }
  }

  void _startLocationUpdates() {
    _locationTimer?.cancel();
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (timer) async {
      if (!mounted || !_isOnline) {
        timer.cancel();
        return;
      }

      // Update current location locally first
      Position position = await Geolocator.getCurrentPosition();
      setState(() {
        _currentPosition = LatLng(position.latitude, position.longitude);
      });

      // Send to backend
      final dId =
          widget.driverData['id'] ?? widget.driverData['driver_details']?['id'];

      if (dId != null) {
        await ApiService().updateDriverLocation(
          int.parse(dId.toString()),
          _currentPosition.latitude,
          _currentPosition.longitude,
        );
      }
    });
  }

  void _checkForBookings() {
    _requestTimer?.cancel();
    _requestTimer = Timer.periodic(const Duration(seconds: 5), (timer) async {
      if (!mounted || !_isOnline || _driverState != DriverState.online) {
        timer.cancel();
        return;
      }

      final bookings = await ApiService().getAvailableBookings(
        lat: _currentPosition.latitude,
        lng: _currentPosition.longitude,
      );
      if (bookings.isNotEmpty && mounted) {
        // Take the first booking for now
        final booking = bookings.first;
        setState(() {
          _currentBooking = booking;
          _hasIncomingRequest = true;

          // Update locations from booking
          _pickupLocation = LatLng(
            double.tryParse(booking['pickup_lat'].toString()) ?? 0,
            double.tryParse(booking['pickup_lng'].toString()) ?? 0,
          );
          _destinationLocation = LatLng(
            double.tryParse(booking['dropoff_lat'].toString()) ?? 0,
            double.tryParse(booking['dropoff_lng'].toString()) ?? 0,
          );
        });
        timer.cancel(); // Stop polling while showing request
      }
    });
  }

  void _acceptRide() async {
    if (_currentBooking == null) return;

    final dId =
        widget.driverData['id'] ?? widget.driverData['driver_details']?['id'];

    if (dId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Sürücü kimliği bulunamadı.')),
      );
      return;
    }

    final response = await ApiService().acceptBooking(
      int.parse(_currentBooking!['id'].toString()),
      int.parse(dId.toString()),
    );

    if (response['success'] == true) {
      if (!mounted) return;

      setState(() {
        _hasIncomingRequest = false;
        _driverState = DriverState.riding;
        _rideStatus = RideStatus.goingToPickup;

        // Add Pickup Marker
        _markers.add(
          Marker(
            markerId: const MarkerId('pickup'),
            position: _pickupLocation,
            icon: BitmapDescriptor.defaultMarkerWithHue(
              BitmapDescriptor.hueGreen,
            ),
            infoWindow: const InfoWindow(title: "Müşteri Konumu"),
          ),
        );
      });

      final GoogleMapController controller = await _controller.future;
      controller.animateCamera(CameraUpdate.newLatLngZoom(_pickupLocation, 14));

      _drawRoute(_currentPosition, _pickupLocation);
    } else {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(response['message'] ?? 'İş kabul edilemedi.')),
      );
      _rejectRide();
    }
  }

  void _updateRideStatus() async {
    if (_currentBooking == null) return;

    String newStatus = '';
    RideStatus nextStatus = _rideStatus;

    if (_rideStatus == RideStatus.goingToPickup) {
      // Logic for arriving at pickup (no API status change usually, or 'arrived')
      // Let's just update UI state for now, maybe send 'arrived' if API supported it
      nextStatus = RideStatus.arrivedAtPickup;
    } else if (_rideStatus == RideStatus.arrivedAtPickup) {
      newStatus = 'on_way';
      nextStatus = RideStatus.goingToDestination;
    } else if (_rideStatus == RideStatus.goingToDestination) {
      newStatus = 'completed';
      nextStatus = RideStatus.completed;
    }

    if (newStatus.isNotEmpty) {
      final response = await ApiService().updateBookingStatus(
        int.parse(_currentBooking!['id'].toString()),
        newStatus,
      );

      if (response['success'] != true) {
        if (!mounted) return;

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['message'] ?? 'Durum güncellenemedi.'),
          ),
        );
        return;
      }
    }

    setState(() {
      _rideStatus = nextStatus;

      if (_rideStatus == RideStatus.goingToDestination) {
        _markers.clear();
        _markers.add(
          Marker(
            markerId: const MarkerId('destination'),
            position: _destinationLocation,
            icon: BitmapDescriptor.defaultMarkerWithHue(
              BitmapDescriptor.hueRed,
            ),
            infoWindow: const InfoWindow(title: "Varış Noktası"),
          ),
        );
      } else if (_rideStatus == RideStatus.completed) {
        _polylines.clear();
        _showCompletionDialog();
      }
    });

    if (_rideStatus == RideStatus.goingToDestination) {
      _drawRoute(_currentPosition, _destinationLocation);
    }
  }

  void _showCompletionDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder:
          (context) => AlertDialog(
            backgroundColor: const Color(0xFF2C2C2C),
            title: Text(
              "Yolculuk Tamamlandı",
              style: GoogleFonts.poppins(color: Colors.white),
            ),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.check_circle,
                  color: Colors.greenAccent,
                  size: 60,
                ),
                const SizedBox(height: 20),
                Text(
                  "Kazanç: ₺250",
                  style: GoogleFonts.poppins(
                    color: Color(0xFFFFD700),
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.pop(context);
                  setState(() {
                    _driverState = DriverState.online;
                    _markers.clear();
                    _startSimulation(); // Ready for next ride
                  });
                },
                child: const Text("Tamam"),
              ),
            ],
          ),
    );
  }

  void _startSimulation() {
    // Reset to online state
    setState(() {
      _driverState = DriverState.online;
      _rideStatus = RideStatus.goingToPickup;
      _markers.clear();
    });

    // Resume polling
    _checkForBookings();
  }

  void _rejectRide() {
    setState(() {
      _hasIncomingRequest = false;
      _currentBooking = null;
    });
    // Restart polling
    _checkForBookings();
  }

  Future<void> _drawRoute(LatLng from, LatLng to) async {
    final route = await _directionsService.getRoute(from, to);

    if (mounted && route != null) {
      setState(() {
        _polylines.clear();
        _polylines.add(
          Polyline(
            polylineId: const PolylineId('route'),
            points: route.polylinePoints,
            color: const Color(0xFFFFD700),
            width: 5,
            jointType: JointType.round,
            startCap: Cap.roundCap,
            endCap: Cap.roundCap,
          ),
        );
      });

      // Fit bounds
      final GoogleMapController controller = await _controller.future;
      LatLngBounds bounds;
      if (from.latitude > to.latitude && from.longitude > to.longitude) {
        bounds = LatLngBounds(southwest: to, northeast: from);
      } else if (from.longitude > to.longitude) {
        bounds = LatLngBounds(
          southwest: LatLng(from.latitude, to.longitude),
          northeast: LatLng(to.latitude, from.longitude),
        );
      } else if (from.latitude > to.latitude) {
        bounds = LatLngBounds(
          southwest: LatLng(to.latitude, from.longitude),
          northeast: LatLng(from.latitude, to.longitude),
        );
      } else {
        bounds = LatLngBounds(southwest: from, northeast: to);
      }
      controller.animateCamera(CameraUpdate.newLatLngBounds(bounds, 80));
    }
  }

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final driverName =
        widget.driverData['driver_details']?['full_name'] ?? 'Sürücü';

    return Scaffold(
      backgroundColor: const Color(0xFF1E1E1E),
      body:
          _selectedIndex == 0
              ? Stack(
                children: [
                  GoogleMap(
                    initialCameraPosition: CameraPosition(
                      target: _currentPosition,
                      zoom: 15,
                    ),
                    myLocationEnabled: true,
                    myLocationButtonEnabled: false,
                    zoomControlsEnabled: false,
                    mapType: MapType.normal,
                    style: _mapStyle,
                    onMapCreated: (GoogleMapController controller) {
                      _controller.complete(controller);
                    },
                    markers: _markers,
                    polylines: _polylines,
                  ),

                  // Top Status Bar
                  Positioned(
                    top: 50,
                    left: 20,
                    right: 20,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 20,
                        vertical: 15,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFF2C2C2C),
                        borderRadius: BorderRadius.circular(15),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.3),
                            blurRadius: 10,
                            offset: const Offset(0, 5),
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                "Merhaba, $driverName",
                                style: GoogleFonts.poppins(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                              Text(
                                _isOnline ? "Çevrimiçi" : "Çevrimdışı",
                                style: GoogleFonts.poppins(
                                  color:
                                      _isOnline
                                          ? Colors.greenAccent
                                          : Colors.grey,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                          Container(
                            width: 50,
                            height: 50,
                            decoration: BoxDecoration(
                              color: Colors.grey[800],
                              shape: BoxShape.circle,
                              image: const DecorationImage(
                                image: AssetImage('assets/logo.png'),
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  // Bottom Action Panel
                  if (_hasIncomingRequest)
                    _buildIncomingRequestPanel()
                  else if (_driverState == DriverState.riding)
                    _buildActiveRidePanel()
                  else
                    _buildStatusPanel(),
                ],
              )
              : _selectedIndex == 1
              ? DriverHistoryScreen(driverData: widget.driverData)
              : DriverProfileScreen(driverData: widget.driverData),
      bottomNavigationBar: BottomNavigationBar(
        items: const <BottomNavigationBarItem>[
          BottomNavigationBarItem(icon: Icon(Icons.map), label: 'Harita'),
          BottomNavigationBarItem(icon: Icon(Icons.history), label: 'Geçmiş'),
          BottomNavigationBarItem(icon: Icon(Icons.person), label: 'Profil'),
        ],
        currentIndex: _selectedIndex,
        selectedItemColor: const Color(0xFFFFD700),
        unselectedItemColor: Colors.grey,
        backgroundColor: const Color(0xFF2C2C2C),
        type: BottomNavigationBarType.fixed,
        onTap: _onItemTapped,
      ),
    );
  }

  Widget _buildActiveRidePanel() {
    String statusText = "";
    String buttonText = "";
    Color buttonColor = const Color(0xFFFFD700);

    switch (_rideStatus) {
      case RideStatus.goingToPickup:
        statusText = "Müşteriye Gidiliyor";
        buttonText = "Müşteriye Vardım";
        break;
      case RideStatus.arrivedAtPickup:
        statusText = "Müşteri Bekleniyor";
        buttonText = "Yolculuğu Başlat";
        break;
      case RideStatus.goingToDestination:
        statusText = "Varış Noktasına Gidiliyor";
        buttonText = "Yolculuğu Bitir";
        buttonColor = Colors.redAccent;
        break;
      default:
        break;
    }

    return Positioned(
      bottom: 0,
      left: 0,
      right: 0,
      child: Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Color(0xFF2C2C2C),
          borderRadius: BorderRadius.only(
            topLeft: Radius.circular(30),
            topRight: Radius.circular(30),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black26,
              blurRadius: 20,
              offset: Offset(0, -5),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  statusText,
                  style: GoogleFonts.poppins(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.greenAccent,
                  ),
                ),
                Row(
                  children: [
                    if (_currentBooking != null)
                      Container(
                        margin: const EdgeInsets.only(right: 8),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.1),
                          shape: BoxShape.circle,
                        ),
                        child: IconButton(
                          icon: const Icon(
                            Icons.message,
                            color: Colors.white,
                            size: 20,
                          ),
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder:
                                    (context) => ChatScreen(
                                      bookingId: int.parse(
                                        _currentBooking!['id'].toString(),
                                      ),
                                      senderType: 'driver',
                                      otherName: "Müşteri",
                                    ),
                              ),
                            );
                          },
                        ),
                      ),
                    GestureDetector(
                      onTap: () async {
                        LatLng? target;
                        if (_rideStatus == RideStatus.goingToPickup) {
                          target = _pickupLocation;
                        } else if (_rideStatus ==
                            RideStatus.goingToDestination) {
                          target = _destinationLocation;
                        }

                        if (target != null) {
                          final uri = Uri.parse(
                            "google.navigation:q=${target.latitude},${target.longitude}",
                          );
                          if (await canLaunchUrl(uri)) {
                            await launchUrl(uri);
                          } else {
                            // Fallback to web map
                            final webUri = Uri.parse(
                              "https://www.google.com/maps/dir/?api=1&destination=${target.latitude},${target.longitude}",
                            );
                            await launchUrl(webUri);
                          }
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Şu an aktif bir rota yok.'),
                            ),
                          );
                        }
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.navigation,
                              color: Colors.white,
                              size: 16,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              "Navigasyon",
                              style: GoogleFonts.poppins(
                                color: Colors.white,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 20),
            // Passenger Info
            Row(
              children: [
                const CircleAvatar(
                  radius: 24,
                  backgroundColor: Colors.grey,
                  child: Icon(Icons.person, color: Colors.white),
                ),
                const SizedBox(width: 16),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Mehmet Y.",
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    Row(
                      children: [
                        const Icon(Icons.star, color: Colors.amber, size: 14),
                        Text(
                          " 4.8",
                          style: GoogleFonts.poppins(
                            color: Colors.grey,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const Spacer(),
                IconButton(
                  onPressed: () {},
                  icon: const Icon(Icons.phone, color: Colors.green),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.green.withValues(alpha: 0.1),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  onPressed: () {
                    if (_currentBooking != null) {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder:
                              (context) => ChatScreen(
                                bookingId: int.parse(
                                  _currentBooking!['id'].toString(),
                                ),
                                senderType: 'driver',
                                otherName: 'Müşteri',
                              ),
                        ),
                      );
                    }
                  },
                  icon: const Icon(Icons.message, color: Colors.blue),
                  style: IconButton.styleFrom(
                    backgroundColor: Colors.blue.withValues(alpha: 0.1),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _updateRideStatus,
                style: ElevatedButton.styleFrom(
                  backgroundColor: buttonColor,
                  padding: const EdgeInsets.symmetric(vertical: 18),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                child: Text(
                  buttonText,
                  style: GoogleFonts.poppins(
                    color: Colors.black,
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
              ),
            ),
          ],
        ),
      ).animate().slideY(
        begin: 1,
        end: 0,
        duration: 400.ms,
        curve: Curves.easeOutBack,
      ),
    );
  }

  Widget _buildStatusPanel() {
    return Positioned(
      bottom: 40,
      left: 20,
      right: 20,
      child: Center(
        child: GestureDetector(
          onTap: _toggleOnlineStatus,
          child: Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: _isOnline ? Colors.red : const Color(0xFFFFD700),
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: (_isOnline ? Colors.red : const Color(0xFFFFD700))
                      .withValues(alpha: 0.4),
                  blurRadius: 20,
                  spreadRadius: 5,
                ),
              ],
            ),
            child: Icon(
              _isOnline ? Icons.power_settings_new : Icons.play_arrow,
              color: _isOnline ? Colors.white : Colors.black,
              size: 40,
            ),
          ),
        ),
      ).animate().scale(duration: 300.ms, curve: Curves.easeOutBack),
    );
  }

  Widget _buildIncomingRequestPanel() {
    final distance =
        _currentBooking != null
            ? double.tryParse(
                  _currentBooking!['distance_km'].toString(),
                )?.toStringAsFixed(1) ??
                "0.0"
            : "0.0";
    final price =
        _currentBooking != null
            ? double.tryParse(
                  _currentBooking!['price'].toString(),
                )?.toStringAsFixed(2) ??
                "0.00"
            : "0.00";
    final userName =
        _currentBooking != null
            ? (_currentBooking!['user_name'] ?? 'Müşteri')
            : "Müşteri";
    final pickup =
        _currentBooking != null
            ? (_currentBooking!['pickup_address'] ?? 'Konum')
            : "Konum";
    final dropoff =
        _currentBooking != null
            ? (_currentBooking!['dropoff_address'] ?? 'Varış')
            : "Varış";

    return Positioned(
      bottom: 0,
      left: 0,
      right: 0,
      child: Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Color(0xFF2C2C2C),
          borderRadius: BorderRadius.only(
            topLeft: Radius.circular(30),
            topRight: Radius.circular(30),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black26,
              blurRadius: 20,
              offset: Offset(0, -5),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              "Yeni Yolculuk İsteği",
              style: GoogleFonts.poppins(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 20),
            Row(
              children: [
                const Icon(Icons.person, color: Color(0xFFFFD700), size: 40),
                const SizedBox(width: 16),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Müşteri",
                      style: GoogleFonts.poppins(
                        color: Colors.grey,
                        fontSize: 12,
                      ),
                    ),
                    Text(
                      userName,
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 18,
                      ),
                    ),
                  ],
                ),
                const Spacer(),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      "Mesafe",
                      style: GoogleFonts.poppins(
                        color: Colors.grey,
                        fontSize: 12,
                      ),
                    ),
                    Text(
                      "$distance km",
                      style: GoogleFonts.poppins(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 18,
                      ),
                    ),
                    Text(
                      "₺$price",
                      style: GoogleFonts.poppins(
                        color: Color(0xFFFFD700),
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(color: Colors.white10),
            const SizedBox(height: 16),
            Row(
              children: [
                const Icon(Icons.location_on, color: Colors.greenAccent),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    pickup,
                    style: GoogleFonts.poppins(color: Colors.white),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(Icons.flag, color: Colors.redAccent),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    dropoff,
                    style: GoogleFonts.poppins(color: Colors.white),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 30),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _rejectRide,
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      side: BorderSide(
                        color: Colors.red.withValues(alpha: 0.5),
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      "Reddet",
                      style: GoogleFonts.poppins(
                        color: Colors.red,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: ElevatedButton(
                    onPressed: _acceptRide,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFFFD700),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: Text(
                      "Kabul Et",
                      style: GoogleFonts.poppins(
                        color: Colors.black,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ).animate().slideY(
        begin: 1,
        end: 0,
        duration: 400.ms,
        curve: Curves.easeOutBack,
      ),
    );
  }

  // Dark Map Style
  final String _mapStyle = '''
[
  {
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#242f3e"
      }
    ]
  },
  {
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#746855"
      }
    ]
  },
  {
    "elementType": "labels.text.stroke",
    "stylers": [
      {
        "color": "#242f3e"
      }
    ]
  },
  {
    "featureType": "administrative.locality",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#d59563"
      }
    ]
  },
  {
    "featureType": "poi",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#d59563"
      }
    ]
  },
  {
    "featureType": "poi.park",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#263c3f"
      }
    ]
  },
  {
    "featureType": "poi.park",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#6b9a76"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#38414e"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "geometry.stroke",
    "stylers": [
      {
        "color": "#212a37"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#9ca5b3"
      }
    ]
  },
  {
    "featureType": "road.highway",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#746855"
      }
    ]
  },
  {
    "featureType": "road.highway",
    "elementType": "geometry.stroke",
    "stylers": [
      {
        "color": "#1f2835"
      }
    ]
  },
  {
    "featureType": "road.highway",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#f3d19c"
      }
    ]
  },
  {
    "featureType": "transit",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#2f3948"
      }
    ]
  },
  {
    "featureType": "transit.station",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#d59563"
      }
    ]
  },
  {
    "featureType": "water",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#17263c"
      }
    ]
  },
  {
    "featureType": "water",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#515c6d"
      }
    ]
  },
  {
    "featureType": "water",
    "elementType": "labels.text.stroke",
    "stylers": [
      {
        "color": "#17263c"
      }
    ]
  }
]
  ''';
}
