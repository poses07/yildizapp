import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'login_screen.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  bool _notificationsEnabled = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF121212),
      appBar: AppBar(
        title: Text(
          "Ayarlar",
          style: GoogleFonts.poppins(
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildSectionHeader("Genel"),
          _buildSettingItem(
            icon: Icons.notifications_outlined,
            title: "Bildirimler",
            trailing: Switch(
              value: _notificationsEnabled,
              onChanged: (val) {
                setState(() {
                  _notificationsEnabled = val;
                });
              },
              activeColor: const Color(0xFFFFD700),
              activeTrackColor: const Color(0xFFFFD700).withValues(alpha: 0.3),
            ),
          ),
          const SizedBox(height: 12),
          _buildSettingItem(
            icon: Icons.language,
            title: "Dil",
            subtitle: "Türkçe",
            onTap: () {},
          ),
          
          const SizedBox(height: 24),
          _buildSectionHeader("Yasal & Destek"),
          _buildSettingItem(
            icon: Icons.privacy_tip_outlined,
            title: "Gizlilik Politikası",
            onTap: () {},
          ),
          const SizedBox(height: 12),
          _buildSettingItem(
            icon: Icons.help_outline,
            title: "Yardım ve Destek",
            onTap: () {},
          ),
          
          const SizedBox(height: 24),
          _buildSectionHeader("Hesap"),
          _buildSettingItem(
            icon: Icons.logout,
            title: "Çıkış Yap",
            textColor: Colors.redAccent,
            iconColor: Colors.redAccent,
            onTap: () {
              // Logout logic
              Navigator.of(context).pushAndRemoveUntil(
                MaterialPageRoute(builder: (context) => const LoginScreen()),
                (route) => false,
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 8),
      child: Text(
        title,
        style: GoogleFonts.poppins(
          color: const Color(0xFFFFD700),
          fontSize: 14,
          fontWeight: FontWeight.w600,
          letterSpacing: 1,
        ),
      ),
    );
  }

  Widget _buildSettingItem({
    required IconData icon,
    required String title,
    String? subtitle,
    Widget? trailing,
    VoidCallback? onTap,
    Color? textColor,
    Color? iconColor,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF1E1E1E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
      ),
      child: ListTile(
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: (iconColor ?? Colors.white).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: iconColor ?? Colors.white70, size: 20),
        ),
        title: Text(
          title,
          style: GoogleFonts.poppins(
            color: textColor ?? Colors.white,
            fontWeight: FontWeight.w500,
            fontSize: 16,
          ),
        ),
        subtitle:
            subtitle != null
                ? Text(
                  subtitle,
                  style: GoogleFonts.poppins(color: Colors.grey, fontSize: 13),
                )
                : null,
        trailing:
            trailing ??
            (onTap != null
                ? const Icon(
                  Icons.arrow_forward_ios,
                  size: 16,
                  color: Colors.grey,
                )
                : null),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        onTap: onTap,
      ),
    );
  }
}
