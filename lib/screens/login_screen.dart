import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:country_code_picker/country_code_picker.dart';
import '../services/api_service.dart';
import 'home_screen.dart';
import 'driver_home_screen.dart';
import 'driver_register_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phoneController = TextEditingController();
  bool _isLoading = false;
  String _selectedCountryCode = '+90';

  void _showRegistrationDialog() {
    final nameController = TextEditingController();
    final phoneController = TextEditingController();
    final codeController = TextEditingController();
    String dialogCountryCode = '+90';
    bool isCodeSent = false;
    bool isLoading = false;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return Dialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(24),
              ),
              backgroundColor: const Color(0xFF1E1E1E),
              child: SingleChildScrollView(
                child: Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Header Icon
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFD700).withValues(alpha: 0.1),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.person_add_rounded,
                          color: Color(0xFFFFD700),
                          size: 32,
                        ),
                      ),
                      const SizedBox(height: 16),
                      
                      // Title
                      Text(
                        isCodeSent ? 'Doğrulama Kodu' : 'Yeni Üyelik',
                        style: GoogleFonts.poppins(
                          color: Colors.white,
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        isCodeSent 
                          ? 'Telefonunuza gönderilen 4 haneli kodu giriniz.' 
                          : 'Hızlıca kayıt olup yolculuğa başlayın.',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.poppins(
                          color: Colors.white54,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 24),

                      if (!isCodeSent) ...[
                        // Name Input
                        Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFF2C2C2C),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.white10),
                          ),
                          child: TextField(
                            controller: nameController,
                            style: const TextStyle(color: Colors.white),
                            decoration: const InputDecoration(
                              hintText: 'Ad Soyad',
                              hintStyle: TextStyle(color: Colors.white38),
                              prefixIcon: Icon(Icons.person_outline_rounded, color: Colors.white54),
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        
                        // Phone Input
                        Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFF2C2C2C),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.white10),
                          ),
                          child: TextField(
                            controller: phoneController,
                            keyboardType: TextInputType.phone,
                            style: const TextStyle(color: Colors.white),
                            decoration: InputDecoration(
                              hintText: '5XX XXX XX XX',
                              hintStyle: const TextStyle(color: Colors.white38),
                              border: InputBorder.none,
                              contentPadding: const EdgeInsets.symmetric(vertical: 16),
                              prefixIcon: CountryCodePicker(
                                onChanged: (country) {
                                  dialogCountryCode = country.dialCode ?? '+90';
                                },
                                initialSelection: 'TR',
                                favorite: const ['+90', 'TR'],
                                showCountryOnly: false,
                                showOnlyCountryWhenClosed: false,
                                alignLeft: false,
                                padding: EdgeInsets.zero,
                                textStyle: const TextStyle(color: Colors.white),
                                dialogBackgroundColor: const Color(0xFF1E1E1E),
                                dialogTextStyle: const TextStyle(color: Colors.white),
                                searchDecoration: const InputDecoration(
                                  hintText: 'Ülke Ara',
                                  hintStyle: TextStyle(color: Colors.white54),
                                  prefixIcon: Icon(Icons.search, color: Colors.white54),
                                  filled: true,
                                  fillColor: Color(0xFF2C2C2C),
                                  border: OutlineInputBorder(
                                    borderSide: BorderSide.none,
                                    borderRadius: BorderRadius.all(Radius.circular(10.0)),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ] else ...[
                        // Code Input
                        Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFF2C2C2C),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.white10),
                          ),
                          child: TextField(
                            controller: codeController,
                            keyboardType: TextInputType.number,
                            maxLength: 4,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              letterSpacing: 12,
                              fontWeight: FontWeight.bold,
                            ),
                            textAlign: TextAlign.center,
                            decoration: const InputDecoration(
                              counterText: '',
                              hintText: '----',
                              hintStyle: TextStyle(color: Colors.white12, letterSpacing: 12),
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.symmetric(vertical: 16),
                            ),
                          ),
                        ),
                      ],

                      const SizedBox(height: 32),

                      // Action Button
                      SizedBox(
                        width: double.infinity,
                        height: 56,
                        child: ElevatedButton(
                          onPressed: isLoading
                              ? null
                              : () async {
                                  if (!isCodeSent) {
                                    // Send Code Logic
                                    if (nameController.text.isEmpty || phoneController.text.isEmpty) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(content: Text('Lütfen tüm alanları doldurun.')),
                                      );
                                      return;
                                    }

                                    setState(() => isLoading = true);
                                    final res = await ApiService().sendOtp(
                                      nameController.text,
                                      '$dialogCountryCode${phoneController.text}',
                                    );
                                    setState(() => isLoading = false);

                                    if (res['success'] == true) {
                                      setState(() => isCodeSent = true);
                                    } else {
                                      // ignore: use_build_context_synchronously
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(
                                          content: Text(res['message'] ?? 'Hata oluştu'),
                                          backgroundColor: Colors.red,
                                        ),
                                      );
                                    }
                                  } else {
                                    // Verify Code Logic
                                    if (codeController.text.length != 4) {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(content: Text('Lütfen 4 haneli kodu girin.')),
                                      );
                                      return;
                                    }

                                    setState(() => isLoading = true);
                                    final res = await ApiService().verifyOtp(
                                      '$dialogCountryCode${phoneController.text}',
                                      codeController.text,
                                    );
                                    setState(() => isLoading = false);

                                    if (res['success'] == true) {
                                      // Save User Data
                                      final prefs = await SharedPreferences.getInstance();
                                      await prefs.setInt('user_id', res['data']['id']);
                                      await prefs.setString('user_name', res['data']['name'] ?? '');
                                      await prefs.setString('user_phone', res['data']['phone']);
                                      await prefs.setString('user_role', 'customer');
                                      if (res['data']['profile_photo'] != null) {
                                        await prefs.setString('user_photo', res['data']['profile_photo']);
                                      }

                                      // ignore: use_build_context_synchronously
                                      Navigator.pop(context); // Close dialog
                                      // ignore: use_build_context_synchronously
                                      Navigator.of(context).pushReplacement(
                                        MaterialPageRoute(builder: (context) => const HomeScreen()),
                                      );
                                      // ignore: use_build_context_synchronously
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(
                                          content: Text('Kayıt başarılı! Hoş geldiniz.'),
                                          backgroundColor: Colors.green,
                                        ),
                                      );
                                    } else {
                                      // ignore: use_build_context_synchronously
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        SnackBar(
                                          content: Text(res['message'] ?? 'Hata oluştu'),
                                          backgroundColor: Colors.red,
                                        ),
                                      );
                                    }
                                  }
                                },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFFFD700),
                            foregroundColor: Colors.black,
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          child: isLoading
                              ? const SizedBox(
                                  height: 24,
                                  width: 24,
                                  child: CircularProgressIndicator(
                                    color: Colors.black,
                                    strokeWidth: 2,
                                  ),
                                )
                              : Text(
                                  isCodeSent ? 'Doğrula ve Başla' : 'Kodu Gönder',
                                  style: GoogleFonts.poppins(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                        ),
                      ),

                      const SizedBox(height: 16),

                      // Cancel Button
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: Text(
                          'Vazgeç',
                          style: GoogleFonts.poppins(
                            color: Colors.white54,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _login() async {
    if (_phoneController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Lütfen telefon numarası giriniz.')),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    final response = await ApiService().userLogin(
      '$_selectedCountryCode${_phoneController.text}',
    );

    if (mounted) {
      setState(() {
        _isLoading = false;
      });

      if (response['success'] == true) {
        // Save user data
        final prefs = await SharedPreferences.getInstance();
        if (response['data'] != null) {
          await prefs.setInt('user_id', response['data']['id']);
          await prefs.setString(
            'user_name',
            response['data']['full_name'] ?? '',
          );
          await prefs.setString('user_phone', response['data']['phone']);
          if (response['data']['profile_photo'] != null) {
            await prefs.setString(
              'user_photo',
              response['data']['profile_photo'],
            );
          }
        }

        final role = response['role'];
        await prefs.setString('user_role', role);

        if (!mounted) return;

        if (role == 'driver') {
          // Sürücü Ana Sayfasına Yönlendir
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
              builder:
                  (context) => DriverHomeScreen(driverData: response['data']),
            ),
          );
        } else if (role == 'customer') {
          // Müşteri Ana Sayfasına Yönlendir
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (context) => const HomeScreen()),
          );
        } else if (role == 'driver_expired') {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Sürücü abonelik süreniz dolmuştur.'),
              backgroundColor: Colors.red,
            ),
          );
        } else {
          // Bilinmeyen rol, müşteriye yönlendir
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (context) => const HomeScreen()),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['message'] ?? 'Giriş başarısız'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Theme.of(context).colorScheme.surface,
              const Color(0xFF000000),
            ],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Logo
                  Hero(
                    tag: 'app_logo',
                    child: Container(
                      height: 120,
                      width: 120,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: Theme.of(
                              context,
                            ).primaryColor.withValues(alpha: 0.3),
                            blurRadius: 20,
                            spreadRadius: 5,
                          ),
                        ],
                      ),
                      child: ClipOval(
                        child: Image.asset(
                          'assets/logo.png',
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) {
                            return Container(
                              color: Theme.of(context).primaryColor,
                              child: const Icon(
                                Icons.local_taxi,
                                size: 60,
                                color: Colors.black,
                              ),
                            );
                          },
                        ),
                      ),
                    ),
                  ).animate().scale(
                    duration: 600.ms,
                    curve: Curves.easeOutBack,
                  ),

                  const SizedBox(height: 32),

                  Text(
                    "Yıldız Taksi",
                    style: Theme.of(context).textTheme.displayLarge,
                  ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.3, end: 0),

                  const SizedBox(height: 8),

                  Text(
                    "Güvenli ve Konforlu Yolculuk",
                    style: Theme.of(context).textTheme.bodyLarge,
                  ).animate().fadeIn(delay: 500.ms),

                  const SizedBox(height: 48),

                  // Login Form
                  Column(
                    children: [
                      TextField(
                        controller: _phoneController,
                        keyboardType: TextInputType.phone,
                        style: const TextStyle(color: Colors.white),
                        decoration: InputDecoration(
                          labelText: 'Telefon Numarası',
                          prefixIcon: CountryCodePicker(
                            onChanged: (country) {
                              setState(() {
                                _selectedCountryCode =
                                    country.dialCode ?? '+90';
                              });
                            },
                            initialSelection: 'TR',
                            favorite: const ['+90', 'TR'],
                            showCountryOnly: false,
                            showOnlyCountryWhenClosed: false,
                            alignLeft: false,
                            textStyle: const TextStyle(color: Colors.white),
                            dialogBackgroundColor: const Color(0xFF1E1E1E),
                            dialogTextStyle: const TextStyle(
                              color: Colors.white,
                            ),
                            searchDecoration: const InputDecoration(
                              hintText: 'Ülke Ara',
                              hintStyle: TextStyle(color: Colors.white54),
                              prefixIcon: Icon(
                                Icons.search,
                                color: Colors.white54,
                              ),
                              filled: true,
                              fillColor: Color(0xFF2C2C2C),
                              border: OutlineInputBorder(
                                borderSide: BorderSide.none,
                                borderRadius: BorderRadius.all(
                                  Radius.circular(10.0),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _isLoading ? null : _login,
                          child:
                              _isLoading
                                  ? const SizedBox(
                                    height: 24,
                                    width: 24,
                                    child: CircularProgressIndicator(
                                      color: Colors.black,
                                      strokeWidth: 2,
                                    ),
                                  )
                                  : const Text("Giriş Yap"),
                        ),
                      ),
                      const SizedBox(height: 24),
                      TextButton(
                        onPressed: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder:
                                  (context) => const DriverRegisterScreen(),
                            ),
                          );
                        },
                        child: const Text(
                          'Sürücü Olmak İstiyorum',
                          style: TextStyle(
                            color: Color(0xFFFFD700),
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),

                      const SizedBox(height: 24),

                      Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Text(
                                "Hesabınız yok mu?",
                                style: TextStyle(color: Colors.white70),
                              ),
                              TextButton(
                                onPressed: _showRegistrationDialog,
                                child: const Text(
                                  "Hemen Kayıt Olun",
                                  style: TextStyle(
                                    color: Color(0xFFFFD700),
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          )
                          .animate()
                          .fadeIn(delay: 700.ms)
                          .slideY(begin: 0.2, end: 0),
                    ],
                  ),

                  const SizedBox(height: 24),
                  Text(
                    "v1.0.0",
                    style: GoogleFonts.poppins(
                      color: Colors.white24,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }
}
