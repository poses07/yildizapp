import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import '../services/api_service.dart';
import 'login_screen.dart';

class DriverProfileScreen extends StatefulWidget {
  final Map<String, dynamic> driverData;

  const DriverProfileScreen({super.key, required this.driverData});

  @override
  State<DriverProfileScreen> createState() => _DriverProfileScreenState();
}

class _DriverProfileScreenState extends State<DriverProfileScreen> {
  late Map<String, dynamic> _currentDriverData;

  @override
  void initState() {
    super.initState();
    _currentDriverData = Map.from(widget.driverData);
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);

    if (pickedFile != null) {
      _uploadImage(File(pickedFile.path));
    }
  }

  Future<void> _uploadImage(File image) async {
    final driverId = _currentDriverData['driver_details']['id'];
    if (driverId == null) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(child: CircularProgressIndicator()),
    );

    final response = await ApiService().uploadDriverPhoto(driverId, image);

    if (!mounted) return;
    Navigator.pop(context); // Close loading dialog

    if (response['success'] == true) {
      setState(() {
        _currentDriverData['driver_details']['profile_photo'] =
            response['photo_url'];
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profil fotoğrafı güncellendi')),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(response['message'] ?? 'Hata oluştu')),
      );
    }
  }

  void _showEditPhoneDialog() {
    final phoneController = TextEditingController(
      text: _currentDriverData['phone'] ?? '',
    );
    bool isLoading = false;

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return AlertDialog(
              backgroundColor: const Color(0xFF2C2C2C),
              title: const Text(
                'Telefon Numarasını Güncelle',
                style: TextStyle(color: Colors.white),
              ),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextField(
                    controller: phoneController,
                    keyboardType: TextInputType.phone,
                    style: const TextStyle(color: Colors.white),
                    decoration: const InputDecoration(
                      labelText: 'Yeni Telefon Numarası',
                      labelStyle: TextStyle(color: Colors.white70),
                      enabledBorder: UnderlineInputBorder(
                        borderSide: BorderSide(color: Colors.white30),
                      ),
                    ),
                  ),
                  if (isLoading)
                    const Padding(
                      padding: EdgeInsets.only(top: 16),
                      child: CircularProgressIndicator(
                        color: Color(0xFFFFD700),
                      ),
                    ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text(
                    'İptal',
                    style: TextStyle(color: Colors.white54),
                  ),
                ),
                TextButton(
                  onPressed:
                      isLoading
                          ? null
                          : () async {
                            if (phoneController.text.isEmpty) return;

                            setState(() => isLoading = true);
                            final driverId =
                                _currentDriverData['driver_details']['id'];
                            final res = await ApiService().updateDriverPhone(
                              driverId,
                              phoneController.text,
                            );
                            setState(() => isLoading = false);

                            if (res['success'] == true) {
                              // ignore: use_build_context_synchronously
                              Navigator.pop(context);

                              // Update local state
                              this.setState(() {
                                _currentDriverData['phone'] =
                                    phoneController.text;
                              });

                              // ignore: use_build_context_synchronously
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text(
                                    'Telefon numarası güncellendi.',
                                  ),
                                  backgroundColor: Colors.green,
                                ),
                              );
                            } else {
                              // ignore: use_build_context_synchronously
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    res['message'] ?? 'Hata oluştu',
                                  ),
                                  backgroundColor: Colors.red,
                                ),
                              );
                            }
                          },
                  child: const Text(
                    'Güncelle',
                    style: TextStyle(
                      color: Color(0xFFFFD700),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final driverDetails = _currentDriverData['driver_details'] ?? {};
    final fullName = driverDetails['full_name'] ?? 'Bilinmiyor';
    final plateNumber = driverDetails['plate_number'] ?? '---';
    final carModel = driverDetails['car_model'] ?? '---';
    final subscriptionEndDate = driverDetails['subscription_end_date'];

    int daysLeft = 0;
    if (subscriptionEndDate != null) {
      final end = DateTime.tryParse(subscriptionEndDate);
      if (end != null) {
        daysLeft = end.difference(DateTime.now()).inDays;
      }
    }

    return Scaffold(
      backgroundColor: const Color(0xFF1E1E1E),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 20),
              Center(
                child: Column(
                  children: [
                    GestureDetector(
                      onTap: _pickImage,
                      child: Stack(
                        children: [
                          CircleAvatar(
                            radius: 50,
                            backgroundColor: Colors.amber,
                            backgroundImage:
                                _currentDriverData['driver_details']?['profile_photo'] !=
                                        null
                                    ? NetworkImage(
                                      '${ApiService.baseUrl.replaceAll('/api', '')}/${_currentDriverData['driver_details']['profile_photo']}',
                                    )
                                    : null,
                            child:
                                _currentDriverData['driver_details']?['profile_photo'] ==
                                        null
                                    ? const Icon(
                                      Icons.person,
                                      size: 50,
                                      color: Colors.black,
                                    )
                                    : null,
                          ),
                          Positioned(
                            bottom: 0,
                            right: 0,
                            child: Container(
                              padding: const EdgeInsets.all(4),
                              decoration: const BoxDecoration(
                                color: Colors.white,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.camera_alt,
                                size: 16,
                                color: Colors.black,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 15),
                    Text(
                      fullName,
                      style: GoogleFonts.poppins(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      plateNumber,
                      style: GoogleFonts.poppins(
                        fontSize: 16,
                        color: Colors.white70,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 40),

              // Subscription Info
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: const Color(0xFF2C2C2C),
                  borderRadius: BorderRadius.circular(15),
                  border: Border.all(
                    color: daysLeft < 3 ? Colors.red : Colors.green,
                    width: 1,
                  ),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.timer,
                      color: daysLeft < 3 ? Colors.red : Colors.green,
                      size: 30,
                    ),
                    const SizedBox(width: 15),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          "Abonelik Durumu",
                          style: GoogleFonts.poppins(
                            color: Colors.white70,
                            fontSize: 14,
                          ),
                        ),
                        Text(
                          "$daysLeft Gün Kaldı",
                          style: GoogleFonts.poppins(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Vehicle Info
              _buildInfoTile(Icons.directions_car, "Araç Modeli", carModel),
              const SizedBox(height: 10),
              _buildInfoTile(
                Icons.phone,
                "Telefon",
                _currentDriverData['phone'] ?? '-',
                isEditable: true,
                onEdit: _showEditPhoneDialog,
              ),

              const SizedBox(height: 40),

              // Settings / Actions
              const Text(
                "Ayarlar",
                style: TextStyle(
                  color: Colors.white54,
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 10),

              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.red.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.logout, color: Colors.red),
                ),
                title: const Text(
                  "Çıkış Yap",
                  style: TextStyle(color: Colors.white),
                ),
                onTap: () {
                  // Logout logic here (clear session, etc.)
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(
                      builder: (context) => const LoginScreen(),
                    ),
                    (route) => false,
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoTile(
    IconData icon,
    String title,
    String value, {
    bool isEditable = false,
    VoidCallback? onEdit,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
      decoration: BoxDecoration(
        color: const Color(0xFF2C2C2C),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(icon, color: Colors.amber, size: 24),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: GoogleFonts.poppins(
                    color: Colors.white70,
                    fontSize: 12,
                  ),
                ),
                Text(
                  value,
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          if (isEditable)
            IconButton(
              icon: const Icon(Icons.edit, color: Colors.white54, size: 20),
              onPressed: onEdit,
            ),
        ],
      ),
    );
  }
}
