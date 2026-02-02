import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:device_info_plus/device_info_plus.dart';

class ApiService {
  // Use 10.0.2.2 for Android Emulator, localhost for iOS simulator, or local IP for real device
  static String get _baseUrl {
    // Production URL (Canlı Sunucu)
    return 'http://wowcrazyviptransfer.com/api';

    /* Localhost Geliştirme Modu için:
    if (kDebugMode) {
      if (Platform.isAndroid) {
        return 'http://10.0.2.2/yildizapp/backend/api';
      } else if (Platform.isIOS) {
        return 'http://localhost/yildizapp/backend/api';
      }
    }
    */
  }

  static String get baseUrl => _baseUrl;

  Future<String?> _getDeviceId() async {
    try {
      final DeviceInfoPlugin deviceInfo = DeviceInfoPlugin();
      if (kIsWeb) return 'web-device-id';

      if (Platform.isAndroid) {
        final AndroidDeviceInfo androidInfo = await deviceInfo.androidInfo;
        return androidInfo.id;
      } else if (Platform.isIOS) {
        final IosDeviceInfo iosInfo = await deviceInfo.iosInfo;
        return iosInfo.identifierForVendor;
      }
    } catch (e) {
      debugPrint('Error getting device id: $e');
    }
    return null;
  }

  static const Map<String, String> _headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  Future<Map<String, dynamic>> getSettings() async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/get_settings.php'),
        headers: _headers,
      );

      if (response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        if (jsonResponse['success'] == true) {
          return jsonResponse['data'];
        }
      }
      debugPrint('API Error: ${response.statusCode} - ${response.body}');
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {};
  }

  Future<Map<String, dynamic>> driverLogin(
    String username,
    String password,
  ) async {
    try {
      final deviceId = await _getDeviceId();
      final response = await http.post(
        Uri.parse('$_baseUrl/driver_login.php'),
        headers: _headers,
        body: json.encode({
          'username': username,
          'password': password,
          'device_id': deviceId,
        }),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> userLogin(String phone, {String? name}) async {
    try {
      final deviceId = await _getDeviceId();
      final response = await http.post(
        Uri.parse('$_baseUrl/user_login.php'),
        headers: _headers,
        body: json.encode({
          'phone': phone,
          'name': name,
          'device_id': deviceId,
        }),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> driverRegister(
    String fullName,
    String phone,
    String carModel,
    String plateNumber,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/driver_register.php'),
        headers: _headers,
        body: json.encode({
          'full_name': fullName,
          'phone': phone,
          'car_model': carModel,
          'plate_number': plateNumber,
        }),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> sendOtp(String fullName, String phone) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/send_otp.php'),
        headers: _headers,
        body: json.encode({'full_name': fullName, 'phone': phone}),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> verifyOtp(String phone, String code) async {
    try {
      final deviceId = await _getDeviceId();
      final response = await http.post(
        Uri.parse('$_baseUrl/verify_otp.php'),
        headers: _headers,
        body: json.encode({
          'phone': phone,
          'code': code,
          'device_id': deviceId,
        }),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> createBooking({
    required int userId,
    required String pickupAddress,
    required String dropoffAddress,
    required double pickupLat,
    required double pickupLng,
    required double dropoffLat,
    required double dropoffLng,
    required double price,
    required double distanceKm,
  }) async {
    try {
      final response = await http
          .post(
            Uri.parse('$_baseUrl/create_booking.php'),
            headers: _headers,
            body: json.encode({
              'user_id': userId,
              'pickup_address': pickupAddress,
              'dropoff_address': dropoffAddress,
              'pickup_lat': pickupLat,
              'pickup_lng': pickupLng,
              'dropoff_lat': dropoffLat,
              'dropoff_lng': dropoffLng,
              'price': price,
              'distance_km': distanceKm,
            }),
          )
          .timeout(const Duration(seconds: 45));

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error (createBooking): $e');
    }
    return {
      'success': false,
      'message': 'Bağlantı zaman aşımına uğradı. Lütfen tekrar deneyin.',
    };
  }

  Future<List<dynamic>> getAvailableBookings({double? lat, double? lng}) async {
    try {
      String url = '$_baseUrl/get_available_bookings.php';
      if (lat != null && lng != null) {
        url += '?lat=$lat&lng=$lng';
      }

      final response = await http
          .get(Uri.parse(url))
          .timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        if (jsonResponse['success'] == true) {
          return jsonResponse['data'];
        }
      }
    } catch (e) {
      debugPrint('Connection Error (getAvailableBookings): $e');
    }
    return [];
  }

  Future<Map<String, dynamic>> acceptBooking(
    int bookingId,
    int driverId,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/accept_booking.php'),
        headers: _headers,
        body: json.encode({'booking_id': bookingId, 'driver_id': driverId}),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> updateDriverPhone(
    int driverId,
    String newPhone,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/update_driver_phone.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'driver_id': driverId, 'new_phone': newPhone}),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> updateBookingStatus(
    int bookingId,
    String status,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/update_booking_status.php'),
        headers: _headers,
        body: json.encode({'booking_id': bookingId, 'status': status}),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> getBookingStatus(int bookingId) async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/get_booking_status.php?booking_id=$bookingId'),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> updateDriverLocation(
    int driverId,
    double lat,
    double lng,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/update_driver_location.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'driver_id': driverId,
          'latitude': lat,
          'longitude': lng,
        }),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<List<dynamic>> getMyBookings({int? userId, int? driverId}) async {
    try {
      String url = '$_baseUrl/get_my_bookings.php?';
      if (userId != null) {
        url += 'user_id=$userId';
      } else if (driverId != null) {
        url += 'driver_id=$driverId';
      } else {
        return [];
      }

      final response = await http.get(Uri.parse(url));

      if (response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        if (jsonResponse['success'] == true) {
          return jsonResponse['data'];
        }
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
      return [];
    }
    return [];
  }

  Future<Map<String, dynamic>> uploadDriverPhoto(
    int driverId,
    File photo,
  ) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$_baseUrl/upload_driver_photo.php'),
      );
      request.fields['driver_id'] = driverId.toString();
      request.files.add(await http.MultipartFile.fromPath('photo', photo.path));

      var streamedResponse = await request.send();
      var response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> uploadProfilePhoto(
    int userId,
    File photo,
  ) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$_baseUrl/upload_profile_photo.php'),
      );
      request.fields['user_id'] = userId.toString();
      request.files.add(await http.MultipartFile.fromPath('photo', photo.path));

      var streamedResponse = await request.send();
      var response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> rateBooking({
    required int bookingId,
    required int rating,
    String? comment,
    String? tags,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/rate_booking.php'),
        body: json.encode({
          'booking_id': bookingId,
          'rating': rating,
          'comment': comment,
          'tags': tags,
        }),
        headers: {'Content-Type': 'application/json'},
      );
      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<Map<String, dynamic>> sendMessage({
    required int bookingId,
    required String senderType,
    required String message,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/send_message.php'),
        body: json.encode({
          'booking_id': bookingId,
          'sender_type': senderType,
          'message': message,
        }),
        headers: {'Content-Type': 'application/json'},
      );
      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }

  Future<List<dynamic>> getMessages(int bookingId) async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/get_messages.php?booking_id=$bookingId'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return [];
  }
}
