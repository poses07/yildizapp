import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';
import 'driver_approval_screen.dart';

class DriverRegisterScreen extends StatefulWidget {
  const DriverRegisterScreen({super.key});

  @override
  State<DriverRegisterScreen> createState() => _DriverRegisterScreenState();
}

class _DriverRegisterScreenState extends State<DriverRegisterScreen> {
  final _fullNameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _carModelController = TextEditingController();
  final _plateNumberController = TextEditingController();
  bool _isLoading = false;

  void _register() async {
    if (_fullNameController.text.isEmpty ||
        _phoneController.text.isEmpty ||
        _carModelController.text.isEmpty ||
        _plateNumberController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lütfen tüm alanları doldurunuz.')),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    final response = await ApiService().driverRegister(
      _fullNameController.text,
      _phoneController.text,
      _carModelController.text,
      _plateNumberController.text,
    );

    if (mounted) {
      setState(() {
        _isLoading = false;
      });

      if (response['success'] == true) {
        // Navigate to approval screen
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (context) => const DriverApprovalScreen()),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['message'] ?? 'Kayıt başarısız'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('Sürücü Başvurusu'),
        backgroundColor: Colors.transparent,
        iconTheme: const IconThemeData(color: Colors.white),
        titleTextStyle: const TextStyle(color: Colors.white, fontSize: 20),
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            children: [
              const Icon(
                Icons.app_registration,
                size: 80,
                color: Color(0xFFFFD700),
              ).animate().scale(duration: 600.ms),
              const SizedBox(height: 32),
              _buildTextField(_fullNameController, 'Ad Soyad', Icons.person),
              const SizedBox(height: 16),
              _buildTextField(
                _phoneController,
                'Telefon Numarası',
                Icons.phone,
                inputType: TextInputType.phone,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                _carModelController,
                'Araç Modeli',
                Icons.directions_car,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                _plateNumberController,
                'Plaka',
                Icons.confirmation_number,
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _register,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFFD700),
                    foregroundColor: Colors.black,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child:
                      _isLoading
                          ? const CircularProgressIndicator(color: Colors.black)
                          : const Text("Başvuru Yap"),
                ),
              ),
            ].animate().fadeIn(delay: 300.ms).slideY(begin: 0.1, end: 0),
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(
    TextEditingController controller,
    String label,
    IconData icon, {
    bool obscureText = false,
    TextInputType? inputType,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: inputType,
      style: const TextStyle(color: Colors.white),
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: Colors.grey),
        labelStyle: const TextStyle(color: Colors.grey),
        enabledBorder: OutlineInputBorder(
          borderSide: const BorderSide(color: Colors.grey),
          borderRadius: BorderRadius.circular(8),
        ),
        focusedBorder: OutlineInputBorder(
          borderSide: const BorderSide(color: Color(0xFFFFD700)),
          borderRadius: BorderRadius.circular(8),
        ),
      ),
    );
  }
}
