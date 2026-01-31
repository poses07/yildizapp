import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:http/http.dart' as http;
import 'package:flutter_polyline_points/flutter_polyline_points.dart';

class DirectionsRoute {
  final List<LatLng> polylinePoints;
  final String distanceText;
  final String durationText;
  final int distanceValue;
  final int durationValue;

  DirectionsRoute({
    required this.polylinePoints,
    required this.distanceText,
    required this.durationText,
    required this.distanceValue,
    required this.durationValue,
  });
}

class DirectionsService {
  final String apiKey;

  DirectionsService(this.apiKey);

  Future<DirectionsRoute?> getRoute(LatLng origin, LatLng destination) async {
    try {
      final String url =
          'https://maps.googleapis.com/maps/api/directions/json'
          '?origin=${origin.latitude},${origin.longitude}'
          '&destination=${destination.latitude},${destination.longitude}'
          '&key=$apiKey'
          '&mode=driving'; // driving mode

      final response = await http.get(Uri.parse(url));
      debugPrint("Directions API Response Code: ${response.statusCode}");

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['status'] == 'OK' && (data['routes'] as List).isNotEmpty) {
          final route = data['routes'][0];
          final leg = route['legs'][0];

          final distanceText = leg['distance']['text'];
          final distanceValue = leg['distance']['value'];
          final durationText = leg['duration']['text'];
          final durationValue = leg['duration']['value'];

          final encodedPolyline = route['overview_polyline']['points'];
          final List<PointLatLng> result = PolylinePoints.decodePolyline(
            encodedPolyline,
          );

          final List<LatLng> latLngPoints =
              result
                  .map((point) => LatLng(point.latitude, point.longitude))
                  .toList();

          return DirectionsRoute(
            polylinePoints: latLngPoints,
            distanceText: distanceText,
            durationText: durationText,
            distanceValue: distanceValue,
            durationValue: durationValue,
          );
        } else {
          debugPrint("Directions API Status: ${data['status']}");
          if (data['error_message'] != null) {
            debugPrint("Directions API Error: ${data['error_message']}");
          }
        }
      }
    } catch (e) {
      debugPrint("Directions Service Error: $e");
    }
    return null;
  }
}
