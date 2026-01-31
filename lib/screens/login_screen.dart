import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';
import 'home_screen.dart';
import 'driver_home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phoneController = TextEditingController();
  bool _isLoading = false;

  Future<void> _launchWhatsApp() async {
    // Replace with the actual support number
    const phoneNumber = "905555555555";
    final whatsappUrl = Uri.parse(
      "https://wa.me/$phoneNumber?text=Merhaba, Yıldız Taksi'ye üye olmak istiyorum.",
    );

    if (!await launchUrl(whatsappUrl, mode: LaunchMode.externalApplication)) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('WhatsApp açılamadı.')));
      }
    }
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

    final response = await ApiService().userLogin(_phoneController.text);

    if (mounted) {
      setState(() {
        _isLoading = false;
      });

      if (response['success'] == true) {
        final role = response['role'];
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
                        decoration: const InputDecoration(
                          labelText: 'Telefon Numarası',
                          prefixIcon: Icon(Icons.phone),
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

                      TextButton.icon(
                        onPressed: _launchWhatsApp,
                        icon: const Icon(
                          Icons.support_agent,
                          color: Colors.white70,
                        ),
                        label: const Text(
                          "Hesabınız yok mu? Kayıt olmak için iletişime geçin.",
                          style: TextStyle(color: Colors.white70),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ],
                  ).animate().fadeIn(delay: 700.ms).slideY(begin: 0.2, end: 0),

                  const SizedBox(height: 40),

                  // Registration / Support
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.white10),
                    ),
                    child: Column(
                      children: [
                        Text(
                          "Üye değil misiniz?",
                          style: GoogleFonts.poppins(color: Colors.white70),
                        ),
                        const SizedBox(height: 8),
                        TextButton.icon(
                          onPressed: _launchWhatsApp,
                          icon: const Icon(
                            Icons.chat,
                            color: Color(0xFF25D366),
                          ), // WhatsApp Green
                          label: Text(
                            "İletişime Geç",
                            style: GoogleFonts.poppins(
                              color: const Color(0xFF25D366),
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          style: TextButton.styleFrom(
                            backgroundColor: const Color(
                              0xFF25D366,
                            ).withValues(alpha: 0.1),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 24,
                              vertical: 12,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(30),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ).animate().fadeIn(delay: 900.ms),

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
