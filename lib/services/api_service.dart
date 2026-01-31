import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

class ApiService {
  // Use 10.0.2.2 for Android Emulator, localhost for iOS simulator, or local IP for real device
  static String get _baseUrl {
    if (kDebugMode) {
      if (Platform.isAndroid) {
        return 'http://10.0.2.2/yildizapp/backend/api';
      } else if (Platform.isIOS) {
        return 'http://localhost/yildizapp/backend/api';
      }
    }
    // Fallback or production URL
    return 'http://192.168.1.100/yildizapp/backend/api';
  }

  Future<Map<String, dynamic>> getSettings() async {
    try {
      final response = await http.get(Uri.parse('$_baseUrl/get_settings.php'));

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
      final response = await http.post(
        Uri.parse('$_baseUrl/driver_login.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'username': username, 'password': password}),
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
      final response = await http.post(
        Uri.parse('$_baseUrl/user_login.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'phone': phone, 'name': name}),
      );

      if (response.statusCode == 200) {
        return json.decode(response.body);
      }
    } catch (e) {
      debugPrint('Connection Error: $e');
    }
    return {'success': false, 'message': 'Bağlantı hatası'};
  }
}
