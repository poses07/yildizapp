import 'dart:async';
import 'dart:ui' as ui;
import 'package:flutter/services.dart';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:geolocator/geolocator.dart';
import 'package:sliding_up_panel/sliding_up_panel.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:uuid/uuid.dart';
import 'package:google_fonts/google_fonts.dart';
import '../widgets/rating_dialog.dart';
import '../screens/chat_screen.dart';
import '../services/places_service.dart';
import '../services/directions_service.dart';
import '../services/api_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MapScreen extends StatefulWidget {
  const MapScreen({super.key});

  @override
  State<MapScreen> createState() => _MapScreenState();
}

enum RideState {
  idle,
  routeSelected,
  searching,
  matching,
  driverFound,
  matchCompleting,
  rideActive,
}

class _MapScreenState extends State<MapScreen> with TickerProviderStateMixin {
  final Completer<GoogleMapController> _controller = Completer();
  final PanelController _panelController = PanelController();

  // API Key - In production, keep this secure!
  final String _apiKey = "AIzaSyC180xlREmLzJJnQSKY1zZTCKIKa6AeiyE";
  late PlacesService _placesService;
  late DirectionsService _directionsService;
  String? _sessionToken;
  List<PlacePrediction> _placePredictions = [];

  // Default Antalya Airport coordinates
  static const CameraPosition _initialPosition = CameraPosition(
    target: LatLng(36.8993, 30.8013),
    zoom: 14.4746,
  );

  LatLng _currentPosition = const LatLng(36.8993, 30.8013);
  LatLng? _destinationPosition;
  RideState _rideState = RideState.idle;

  // Markers & Polylines
  final Set<Marker> _markers = {};
  final Set<Polyline> _polylines = {};

  // Mock Data
  double _pricePerKm = 15.0; // Fetched from API
  double _baseFare = 25.0;
  double _minFare = 50.0;

  double _estimatedPrice = 0.0;
  double _distance = 0.0;

  // Driver Data
  String _driverName = "Sürücü Atanıyor...";
  final double _driverRating = 5.0;
  LatLng _driverPosition = const LatLng(
    41.0122,
    28.9760,
  ); // Default, will update
  String _driverCarModel = "";
  String _driverPlate = "";

  final TextEditingController _searchController = TextEditingController();
  Timer? _polyLineTimer;
  Timer? _statusPollingTimer;
  int? _currentBookingId;

  // Nearby Drivers Simulation
  BitmapDescriptor? _carIcon;

  int? _userId;

  @override
  void initState() {
    super.initState();
    _placesService = PlacesService(_apiKey);
    _directionsService = DirectionsService(_apiKey);
    _loadUserId();
    _loadCarIcon();
    _getCurrentLocation();
    _fetchSettings();
  }

  Future<void> _loadUserId() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _userId = prefs.getInt('user_id');
    });
  }

  @override
  void dispose() {
    _polyLineTimer?.cancel();
    _statusPollingTimer?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  // Dark Map Style JSON
  final String _mapStyle = '''
[
  {
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#212121"
      }
    ]
  },
  {
    "elementType": "labels.icon",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#757575"
      }
    ]
  },
  {
    "elementType": "labels.text.stroke",
    "stylers": [
      {
        "color": "#212121"
      }
    ]
  },
  {
    "featureType": "administrative",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#757575"
      }
    ]
  },
  {
    "featureType": "administrative.country",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#9e9e9e"
      }
    ]
  },
  {
    "featureType": "administrative.land_parcel",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "administrative.locality",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#bdbdbd"
      }
    ]
  },
  {
    "featureType": "poi",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#757575"
      }
    ]
  },
  {
    "featureType": "poi.park",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#181818"
      }
    ]
  },
  {
    "featureType": "poi.park",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#616161"
      }
    ]
  },
  {
    "featureType": "poi.park",
    "elementType": "labels.text.stroke",
    "stylers": [
      {
        "color": "#1b1b1b"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "geometry.fill",
    "stylers": [
      {
        "color": "#2c2c2c"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#8a8a8a"
      }
    ]
  },
  {
    "featureType": "road.arterial",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#373737"
      }
    ]
  },
  {
    "featureType": "road.highway",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#3c3c3c"
      }
    ]
  },
  {
    "featureType": "road.highway.controlled_access",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#4e4e4e"
      }
    ]
  },
  {
    "featureType": "road.local",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#616161"
      }
    ]
  },
  {
    "featureType": "transit",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#757575"
      }
    ]
  },
  {
    "featureType": "water",
    "elementType": "geometry",
    "stylers": [
      {
        "color": "#000000"
      }
    ]
  },
  {
    "featureType": "water",
    "elementType": "labels.text.fill",
    "stylers": [
      {
        "color": "#3d3d3d"
      }
    ]
  }
]
''';

  Future<void> _fetchSettings() async {
    final settings = await ApiService().getSettings();
    if (settings.isNotEmpty && mounted) {
      setState(() {
        if (settings['price_per_km'] != null) {
          _pricePerKm = (settings['price_per_km'] as num).toDouble();
        }
        if (settings['base_fare'] != null) {
          _baseFare = (settings['base_fare'] as num).toDouble();
        }
        if (settings['min_fare'] != null) {
          _minFare = (settings['min_fare'] as num).toDouble();
        }
      });
    }
  }

  Future<void> _loadCarIcon() async {
    final Uint8List markerIcon = await _getBytesFromCanvas(
      30,
      30,
      Icons.directions_car_filled,
      const Color(0xFFFFD700),
    );
    if (mounted) {
      setState(() {
        _carIcon = BitmapDescriptor.bytes(markerIcon);
      });
    }
  }

  Future<Uint8List> _getBytesFromCanvas(
    int width,
    int height,
    IconData iconData,
    Color color,
  ) async {
    final ui.PictureRecorder pictureRecorder = ui.PictureRecorder();
    final Canvas canvas = Canvas(pictureRecorder);
    final Paint paint = Paint()..color = Colors.transparent;

    // Draw transparent background
    canvas.drawRect(
      Rect.fromLTWH(0, 0, width.toDouble(), height.toDouble()),
      paint,
    );

    final TextPainter textPainter = TextPainter(
      textDirection: TextDirection.ltr,
    );
    textPainter.text = TextSpan(
      text: String.fromCharCode(iconData.codePoint),
      style: TextStyle(
        fontSize: width.toDouble(),
        fontFamily: iconData.fontFamily,
        color: color,
        shadows: const [
          Shadow(
            blurRadius: 10.0,
            color: Colors.black,
            offset: Offset(2.0, 2.0),
          ),
        ],
      ),
    );
    textPainter.layout();
    textPainter.paint(canvas, const Offset(0, 0));

    final img = await pictureRecorder.endRecording().toImage(width, height);
    final data = await img.toByteData(format: ui.ImageByteFormat.png);
    return data!.buffer.asUint8List();
  }

  Future<void> _getCurrentLocation() async {
    bool serviceEnabled;
    LocationPermission permission;

    // Test if location services are enabled.
    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      // Location services are not enabled.
      // Fallback to default or show error.
      // For now, we just return and keep the default/previous position.
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Konum servisleri kapalı.')),
        );
      }
      return;
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Konum izni reddedildi.')),
          );
        }
        return;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Konum izni kalıcı olarak reddedildi. Ayarlardan açmanız gerekiyor.')),
        );
      }
      return;
    }

    // When we reach here, permissions are granted.
    final position = await Geolocator.getCurrentPosition();

    if (!mounted) return;

    setState(() {
      _currentPosition = LatLng(position.latitude, position.longitude);
    });

    final GoogleMapController controller = await _controller.future;
    controller.animateCamera(CameraUpdate.newLatLng(_currentPosition));
    _updateMarkers();
  }

  void _updateMarkers() {
    setState(() {
      _markers.clear();

      // Always show Pickup Marker (Current Location)
      _markers.add(
        Marker(
          markerId: const MarkerId('pickup'),
          position: _currentPosition,
          icon: BitmapDescriptor.defaultMarkerWithHue(
            BitmapDescriptor.hueYellow,
          ),
          infoWindow: const InfoWindow(title: "Konumunuz"),
        ),
      );

      // Only show destination marker when a route is selected or active
      if (_rideState != RideState.idle) {
        // Destination Marker
        if (_destinationPosition != null) {
          _markers.add(
            Marker(
              markerId: const MarkerId('destination'),
              position: _destinationPosition!,
              icon: BitmapDescriptor.defaultMarkerWithHue(
                BitmapDescriptor.hueRed,
              ),
              infoWindow: const InfoWindow(title: "Varış Noktası"),
            ),
          );
        }
      }

      // Driver Marker (The assigned driver)
      if (_rideState == RideState.driverFound ||
          _rideState == RideState.rideActive) {
        _markers.add(
          Marker(
            markerId: const MarkerId('driver'),
            position: _driverPosition,
            icon:
                _carIcon ??
                BitmapDescriptor.defaultMarkerWithHue(
                  BitmapDescriptor.hueAzure,
                ),
            rotation: 90, // Assume driver is facing East or calculate bearing
            flat: true,
            anchor: const Offset(0.5, 0.5),
            infoWindow: InfoWindow(title: "Sürücü: $_driverName"),
          ),
        );
      }
    });
  }

  void _onSearchChanged(String value) {
    _sessionToken ??= const Uuid().v4();
    _placesService
        .getPredictions(value, _sessionToken)
        .then((predictions) {
          setState(() {
            _placePredictions = predictions;
          });
        })
        .catchError((error) {
          debugPrint("Error fetching predictions: $error");
        });
  }

  void _onPredictionSelected(PlacePrediction prediction) async {
    final detail = await _placesService.getPlaceDetail(
      prediction.placeId,
      _sessionToken,
    );

    _sessionToken = const Uuid().v4();

    if (!mounted) return;

    if (detail != null) {
      setState(() {
        _searchController.text = prediction.description;
        _placePredictions = [];
        _destinationPosition = LatLng(detail.lat, detail.lng);
        _calculateRoute();
      });

      FocusScope.of(context).unfocus();
      _panelController.open(); // Open panel to show ride options
    }
  }

  Future<void> _calculateRoute() async {
    if (_destinationPosition == null) return;

    final route = await _directionsService.getRoute(
      _currentPosition,
      _destinationPosition!,
    );

    if (!mounted) return;

    _polyLineTimer?.cancel();
    List<LatLng> points = [];

    if (route != null) {
      _distance = route.distanceValue / 1000.0;
      points = route.polylinePoints;
    } else {
      _distance =
          Geolocator.distanceBetween(
            _currentPosition.latitude,
            _currentPosition.longitude,
            _destinationPosition!.latitude,
            _destinationPosition!.longitude,
          ) /
          1000;
      points = [_currentPosition, _destinationPosition!];
    }

    setState(() {
      _estimatedPrice = _baseFare + (_distance * _pricePerKm);
      if (_estimatedPrice < _minFare) {
        _estimatedPrice = _minFare;
      }

      _rideState = RideState.routeSelected;
      _updateMarkers();
      _fitBounds();

      // Clear previous polylines
      _polylines.clear();
    });

    // Animate Polyline
    int currentIndex = 0;
    // Calculate speed based on point count to keep animation duration reasonable
    // Increase points per tick and timer duration for better performance
    int pointsPerTick = 5;
    if (points.length > 50) {
      pointsPerTick = (points.length / 20).ceil();
    }

    _polyLineTimer = Timer.periodic(const Duration(milliseconds: 100), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }

      currentIndex += pointsPerTick;
      if (currentIndex >= points.length) {
        currentIndex = points.length;
        timer.cancel();
      }

      setState(() {
        _polylines.clear();
        _polylines.add(
          Polyline(
            polylineId: const PolylineId('route'),
            points: points.sublist(0, currentIndex),
            color: const Color(0xFFFFD700),
            width: 5,
            jointType: JointType.round,
            startCap: Cap.roundCap,
            endCap: Cap.roundCap,
          ),
        );
      });
    });
  }

  Future<void> _fitBounds() async {
    if (_destinationPosition == null) return;

    final GoogleMapController controller = await _controller.future;
    LatLngBounds bounds;

    if (_currentPosition.latitude > _destinationPosition!.latitude &&
        _currentPosition.longitude > _destinationPosition!.longitude) {
      bounds = LatLngBounds(
        southwest: _destinationPosition!,
        northeast: _currentPosition,
      );
    } else if (_currentPosition.longitude > _destinationPosition!.longitude) {
      bounds = LatLngBounds(
        southwest: LatLng(
          _currentPosition.latitude,
          _destinationPosition!.longitude,
        ),
        northeast: LatLng(
          _destinationPosition!.latitude,
          _currentPosition.longitude,
        ),
      );
    } else if (_currentPosition.latitude > _destinationPosition!.latitude) {
      bounds = LatLngBounds(
        southwest: LatLng(
          _destinationPosition!.latitude,
          _currentPosition.longitude,
        ),
        northeast: LatLng(
          _currentPosition.latitude,
          _destinationPosition!.longitude,
        ),
      );
    } else {
      bounds = LatLngBounds(
        southwest: _currentPosition,
        northeast: _destinationPosition!,
      );
    }

    controller.animateCamera(CameraUpdate.newLatLngBounds(bounds, 80));
  }

  void _callVehicle() async {
    if (_userId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Kullanıcı bilgisi bulunamadı. Lütfen tekrar giriş yapın.',
          ),
        ),
      );
      return;
    }

    if (_destinationPosition == null) return;

    setState(() {
      _rideState = RideState.searching;
    });

    final response = await ApiService().createBooking(
      userId: _userId!,
      pickupAddress:
          "Mevcut Konum", // Should ideally be geocoded or selected from map
      dropoffAddress: _searchController.text,
      pickupLat: _currentPosition.latitude,
      pickupLng: _currentPosition.longitude,
      dropoffLat: _destinationPosition!.latitude,
      dropoffLng: _destinationPosition!.longitude,
      price: _estimatedPrice,
      distanceKm: _distance,
    );

    if (!mounted) return;

    if (response['success'] == true) {
      setState(() {
        _currentBookingId = int.tryParse(response['booking_id'].toString());
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Taksi çağrısı oluşturuldu! Sürücü aranıyor...'),
        ),
      );
      _startBookingStatusPolling();
    } else {
      setState(() {
        _rideState = RideState.routeSelected;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(response['message'] ?? 'Bir hata oluştu.')),
      );
    }
  }

  void _startBookingStatusPolling() {
    _statusPollingTimer?.cancel();
    _statusPollingTimer = Timer.periodic(const Duration(seconds: 3), (
      timer,
    ) async {
      if (_currentBookingId == null) {
        timer.cancel();
        return;
      }

      final statusData = await ApiService().getBookingStatus(
        _currentBookingId!,
      );
      if (!mounted) {
        timer.cancel();
        return;
      }

      if (statusData['success'] == true) {
        final booking = statusData['data'];
        final status = booking['status'];

        if (booking['driver_name'] != null) {
          setState(() {
            _driverName = booking['driver_name'];
            _driverCarModel = booking['car_model'] ?? "";
            _driverPlate = booking['plate_number'] ?? "";
          });
        }

        if (status == 'accepted' && _rideState == RideState.searching) {
          setState(() {
            _rideState = RideState.driverFound;
          });
        } else if (status == 'on_way' && _rideState != RideState.rideActive) {
          setState(() {
            _rideState = RideState.rideActive;
          });
        } else if (status == 'completed') {
          timer.cancel();
          if (mounted) {
            _showRatingDialog(_currentBookingId!);
          }
        } else if (status == 'cancelled') {
          timer.cancel();
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Sürücü yolculuğu iptal etti.")),
          );
          _resetRide();
        }

        // Update driver location if available
        if (booking['driver_lat'] != null && booking['driver_lng'] != null) {
          double lat = double.tryParse(booking['driver_lat'].toString()) ?? 0;
          double lng = double.tryParse(booking['driver_lng'].toString()) ?? 0;
          if (lat != 0 && lng != 0) {
            setState(() {
              _driverPosition = LatLng(lat, lng);
              _updateMarkers();
            });
          }
        }
      }
    });
  }

  void _showRatingDialog(int bookingId) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder:
          (context) => RatingDialog(
            bookingId: bookingId,
            onSubmitted: () {
              Navigator.of(context).pop(); // Close dialog
              _resetRide();
            },
          ),
    );
  }

  void _resetRide() async {
    _statusPollingTimer?.cancel();
    setState(() {
      _currentBookingId = null;
      _rideState = RideState.idle;
      _destinationPosition = null;
      _searchController.clear();
      _placePredictions = [];
      _polylines.clear();
      _updateMarkers();
      _panelController.close();
    });

    final GoogleMapController controller = await _controller.future;
    controller.animateCamera(CameraUpdate.newLatLng(_currentPosition));
  }

  Future<void> _cancelBooking() async {
    if (_currentBookingId == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Hata: Rezervasyon ID bulunamadı.')),
        );
      }
      _resetRide(); // Force reset if ID is missing but state is searching
      return;
    }

    // Show loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(child: CircularProgressIndicator()),
    );

    try {
      final result = await ApiService().updateBookingStatus(
        _currentBookingId!,
        'cancelled',
      );

      // Hide loading
      if (mounted) {
        Navigator.of(context).pop();
      }

      if (result['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Yolculuk iptal edildi.')),
          );
        }
        _resetRide();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(result['message'] ?? 'İptal edilemedi.')),
          );
        }
      }
    } catch (e) {
      // Hide loading
      if (mounted) {
        Navigator.of(context).pop();
      }
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Bağlantı hatası: $e')));
      }
    }
  }

  Widget _buildPanel(ScrollController sc) {
    if (_rideState == RideState.idle) {
      return _buildIdlePanel(sc);
    } else if (_rideState == RideState.routeSelected) {
      return _buildRideSelectionPanel(sc);
    } else if (_rideState == RideState.searching) {
      return _buildSearchingPanel();
    } else if (_rideState == RideState.matching) {
      return _buildMatchingPanel();
    } else if (_rideState == RideState.driverFound) {
      return _buildDriverFoundPanel();
    } else if (_rideState == RideState.matchCompleting) {
      return _buildMatchCompletingPanel();
    } else {
      return _buildRideActivePanel();
    }
  }

  Widget _buildMatchCompletingPanel() {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 20),
          const CircularProgressIndicator(color: Colors.greenAccent),
          const SizedBox(height: 20),
          Text(
                "Eşleştirme Tamamlanıyor...",
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              )
              .animate(onPlay: (controller) => controller.repeat())
              .shimmer(duration: 1500.ms, color: Colors.grey),
          const SizedBox(height: 10),
          Text(
            "Sürücünüz ile bağlantı kuruluyor, lütfen bekleyin.",
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(color: Colors.grey),
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _buildIdlePanel(ScrollController sc) {
    return ListView(
      controller: sc,
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
      children: [
        Center(
          child: Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey[300],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ),
        const SizedBox(height: 24),
        Text(
          "Nereye gitmek istersiniz?",
          style: GoogleFonts.poppins(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: Colors.black87,
          ),
        ),
        const SizedBox(height: 20),
        // Search Input (Mock)
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: Colors.grey[100],
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              const Icon(Icons.search, color: Colors.black54),
              const SizedBox(width: 12),
              Text(
                "Varış noktası arayın",
                style: GoogleFonts.poppins(color: Colors.black54),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _buildRideSelectionPanel(ScrollController sc) {
    return ListView(
      controller: sc,
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
      children: [
        Center(
          child: Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey[800],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ),
        const SizedBox(height: 20),
        Text(
          "Yolculuk Detayları",
          style: GoogleFonts.poppins(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 20),

        // Single Vehicle Option (Minimalist Dark Design)
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: const Color(0xFF2C2C2C),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: const Color(0xFFFFD700).withValues(alpha: 0.3),
              width: 1,
            ),
          ),
          child: Column(
            children: [
              Row(
                children: [
                  Container(
                    width: 60,
                    height: 60,
                    decoration: BoxDecoration(
                      color: Colors.black,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.local_taxi,
                      color: Color(0xFFFFD700),
                      size: 32,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          "Yıldız Taksi",
                          style: GoogleFonts.poppins(
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    "₺${_estimatedPrice.toStringAsFixed(0)}",
                    style: GoogleFonts.poppins(
                      fontWeight: FontWeight.bold,
                      fontSize: 24,
                      color: const Color(0xFFFFD700),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Divider(color: Colors.white.withValues(alpha: 0.1)),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildRideStat(
                    Icons.directions_car,
                    "${_distance.toStringAsFixed(1)} km",
                    "Mesafe",
                  ),
                  _buildRideStat(
                    Icons.access_time,
                    "${(_distance * 3).toStringAsFixed(0)} dk",
                    "Süre",
                  ),
                  _buildRideStat(Icons.person, "4 Kişi", "Kapasite"),
                ],
              ),
            ],
          ),
        ),

        const SizedBox(height: 24),

        ElevatedButton(
          onPressed: _callVehicle,
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFFFFD700),
            padding: const EdgeInsets.symmetric(vertical: 18),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
            elevation: 0,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                "Yolculuğu Başlat",
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
              ),
              const SizedBox(width: 12),
              const Icon(Icons.arrow_forward, color: Colors.black),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildRideStat(IconData icon, String value, String label) {
    return Column(
      children: [
        Icon(icon, color: Colors.grey, size: 20),
        const SizedBox(height: 4),
        Text(
          value,
          style: GoogleFonts.poppins(
            fontWeight: FontWeight.w600,
            fontSize: 14,
            color: Colors.white,
          ),
        ),
        Text(
          label,
          style: GoogleFonts.poppins(color: Colors.grey[600], fontSize: 12),
        ),
      ],
    );
  }

  Widget _buildMatchingPanel() {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 20),
          const Icon(Icons.check_circle, color: Colors.greenAccent, size: 60),
          const SizedBox(height: 20),
          Text(
            "Sürücü Bulundu!",
            style: GoogleFonts.poppins(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 8),
          Text(
                "Sürücü ile eşleşiliyor...",
                style: GoogleFonts.poppins(fontSize: 16, color: Colors.grey),
              )
              .animate(onPlay: (controller) => controller.repeat(reverse: true))
              .fadeIn(duration: 600.ms),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Widget _buildSearchingPanel() {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const SizedBox(height: 20),
          const CircularProgressIndicator(color: Color(0xFFFFD700)),
          const SizedBox(height: 20),
          Text(
                "Yıldız Sürücü Aranıyor...",
                style: GoogleFonts.poppins(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              )
              .animate(onPlay: (controller) => controller.repeat())
              .shimmer(duration: 1500.ms, color: Colors.grey),
          const SizedBox(height: 10),
          Text(
            "Size en yakın Yıldız sürücüsü ile eşleştiriliyorsunuz.",
            textAlign: TextAlign.center,
            style: GoogleFonts.poppins(color: Colors.grey),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton(
              onPressed: _cancelBooking,
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
                side: BorderSide(color: Colors.red.withValues(alpha: 0.5)),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(
                "Aramayı İptal Et",
                style: GoogleFonts.poppins(
                  color: Colors.red,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDriverFoundPanel() {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey[800],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                "Sürücü Bulundu!",
                style: GoogleFonts.poppins(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              IconButton(
                icon: const Icon(Icons.message, color: Colors.white),
                onPressed: () {
                  if (_currentBookingId != null) {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder:
                            (context) => ChatScreen(
                              bookingId: _currentBookingId!,
                              senderType: 'user',
                              otherName: _driverName,
                            ),
                      ),
                    );
                  }
                },
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Driver Info Card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF2C2C2C),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: const Color(0xFFFFD700).withValues(alpha: 0.3),
              ),
            ),
            child: Row(
              children: [
                const CircleAvatar(
                  radius: 30,
                  backgroundColor: Colors.black,
                  child: Icon(Icons.person, size: 30, color: Color(0xFFFFD700)),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _driverName,
                        style: GoogleFonts.poppins(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      Row(
                        children: [
                          const Icon(Icons.star, color: Colors.amber, size: 16),
                          Text(
                            " $_driverRating",
                            style: GoogleFonts.poppins(
                              color: Colors.white70,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      _driverPlate,
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      _driverCarModel,
                      style: GoogleFonts.poppins(
                        color: Colors.grey,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Action Buttons
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: _cancelBooking,
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    side: BorderSide(color: Colors.red.withValues(alpha: 0.5)),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: Text(
                    "İptal Et",
                    style: GoogleFonts.poppins(
                      color: Colors.red,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
        ],
      ),
    );
  }

  Widget _buildRideActivePanel() {
    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey[800],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 20),
          Text(
            "Sürücü Yolda!",
            style: GoogleFonts.poppins(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.greenAccent,
            ),
          ),
          const SizedBox(height: 4),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                "2 dakika içinde yanınızda olacak",
                style: GoogleFonts.poppins(color: Colors.grey),
              ),
              IconButton(
                icon: const Icon(Icons.message, color: Colors.white),
                onPressed: () {
                  if (_currentBookingId != null) {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder:
                            (context) => ChatScreen(
                              bookingId: _currentBookingId!,
                              senderType: 'user',
                              otherName: _driverName,
                            ),
                      ),
                    );
                  }
                },
              ),
            ],
          ),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF2C2C2C),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
            ),
            child: Row(
              children: [
                const CircleAvatar(
                  radius: 30,
                  backgroundColor: Colors.black,
                  child: Icon(Icons.person, size: 30, color: Color(0xFFFFD700)),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _driverName,
                        style: GoogleFonts.poppins(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      Row(
                        children: [
                          const Icon(Icons.star, color: Colors.amber, size: 16),
                          Text(
                            " $_driverRating",
                            style: GoogleFonts.poppins(
                              color: Colors.white70,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      _driverPlate,
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      _driverCarModel,
                      style: GoogleFonts.poppins(
                        color: Colors.grey,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () {
                    if (_currentBookingId != null) {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder:
                              (context) => ChatScreen(
                                bookingId: _currentBookingId!,
                                senderType: 'user',
                                otherName: _driverName,
                              ),
                        ),
                      );
                    }
                  },
                  icon: const Icon(Icons.message, color: Colors.white),
                  label: const Text("Mesaj"),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: BorderSide(
                      color: Colors.white.withValues(alpha: 0.2),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.call, color: Colors.black),
                  label: const Text("Ara"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFFD700),
                    foregroundColor: Colors.black,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final panelHeightClosed = MediaQuery.of(context).size.height * 0.2;
    final panelHeightOpen = MediaQuery.of(context).size.height * 0.5;

    return Scaffold(
      body: Stack(
        children: [
          SlidingUpPanel(
            controller: _panelController,
            maxHeight: panelHeightOpen,
            minHeight:
                _rideState == RideState.idle
                    ? 0
                    : panelHeightClosed, // Hide panel in idle, show custom search
            parallaxEnabled: true,
            parallaxOffset: .5,
            color: const Color(0xFF1E1E1E), // Dark background for panel
            body: Stack(
              children: [
                GoogleMap(
                  mapType: MapType.normal,
                  style: _mapStyle,
                  initialCameraPosition: _initialPosition,
                  markers: _markers,
                  polylines: _polylines,
                  onMapCreated: (GoogleMapController controller) {
                    _controller.complete(controller);
                  },
                  myLocationEnabled: true,
                  myLocationButtonEnabled: false,
                  zoomControlsEnabled: false,
                  mapToolbarEnabled: false,
                  padding:
                      EdgeInsets
                          .zero, // Remove padding to hide Google logo behind the panel
                ),
              ],
            ),
            panelBuilder: (sc) => _buildPanel(sc),
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(24.0),
              topRight: Radius.circular(24.0),
            ),
          ),

          // Custom Search Bar (Always Visible at Top)
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E1E1E),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.05),
                      ),
                    ),
                    child: TextField(
                      controller: _searchController,
                      style: GoogleFonts.poppins(color: Colors.white),
                      decoration: InputDecoration(
                        hintText: "Nereye gitmek istersiniz?",
                        hintStyle: GoogleFonts.poppins(color: Colors.grey),
                        prefixIcon: const Icon(
                          Icons.search,
                          color: Colors.white70,
                        ),
                        suffixIcon:
                            _rideState != RideState.idle
                                ? IconButton(
                                  icon: const Icon(
                                    Icons.close,
                                    color: Colors.white70,
                                  ),
                                  onPressed: _resetRide,
                                )
                                : const Icon(Icons.mic, color: Colors.grey),
                        border: InputBorder.none,
                        enabledBorder: InputBorder.none,
                        focusedBorder: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 20,
                          vertical: 15,
                        ),
                      ),
                      onChanged: _onSearchChanged,
                    ),
                  ),

                  // Predictions List
                  if (_placePredictions.isNotEmpty)
                    Flexible(
                      child: Container(
                        margin: const EdgeInsets.only(top: 8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E1E1E),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: Colors.white.withValues(alpha: 0.05),
                          ),
                        ),
                        child: ListView.separated(
                          shrinkWrap: true,
                          padding: EdgeInsets.zero,
                          itemCount: _placePredictions.length,
                          separatorBuilder:
                              (context, index) => Divider(
                                height: 1,
                                color: Colors.white.withValues(alpha: 0.05),
                              ),
                          itemBuilder: (context, index) {
                            final prediction = _placePredictions[index];
                            return ListTile(
                              leading: const Icon(
                                Icons.location_on,
                                color: Colors.white70,
                              ),
                              title: Text(
                                prediction.description,
                                style: GoogleFonts.poppins(color: Colors.white),
                              ),
                              onTap: () => _onPredictionSelected(prediction),
                            );
                          },
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
