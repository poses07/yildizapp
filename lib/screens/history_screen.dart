import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<dynamic> _bookings = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadBookings();
  }

  Future<void> _loadBookings() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getInt('user_id');

    if (userId != null) {
      final bookings = await ApiService().getMyBookings(userId: userId);
      if (mounted) {
        setState(() {
          _bookings = bookings;
          _isLoading = false;
        });
      }
    } else {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF121212),
      appBar: AppBar(
        title: Text(
          "Geçmiş Transferler",
          style: GoogleFonts.poppins(
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body:
          _isLoading
              ? const Center(
                child: CircularProgressIndicator(color: Color(0xFFFFD700)),
              )
              : _bookings.isEmpty
              ? Center(
                child: Text(
                  "Henüz bir yolculuğunuz bulunmuyor.",
                  style: GoogleFonts.poppins(color: Colors.white54),
                ),
              )
              : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: _bookings.length,
                itemBuilder: (context, index) {
                  final booking = _bookings[index];
                  return _buildHistoryItem(context, booking);
                },
              ),
    );
  }

  Widget _buildHistoryItem(BuildContext context, dynamic booking) {
    final pickup = booking['pickup_address'] ?? '';
    final dropoff = booking['dropoff_address'] ?? '';
    final price = booking['price'] ?? '0';
    final date = booking['created_at'] ?? '';
    final status = booking['status'] ?? 'pending';

    final driverName = booking['driver_name'];

    Color statusColor = Colors.orange;
    String statusText = "Bekleniyor";

    if (status == 'completed') {
      statusColor = Colors.green;
      statusText = "Tamamlandı";
    } else if (status == 'cancelled') {
      statusColor = Colors.red;
      statusText = "İptal Edildi";
    } else if (status == 'accepted') {
      statusColor = Colors.blue;
      statusText = "Kabul Edildi";
    } else if (status == 'on_way') {
      statusColor = Colors.blue;
      statusText = "Yolda";
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E1E1E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFF2C2C2C),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.history,
              color: Color(0xFFFFD700),
              size: 24,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  "$pickup -> $dropoff",
                  style: GoogleFonts.poppins(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  date,
                  style: GoogleFonts.poppins(color: Colors.grey, fontSize: 12),
                ),
                if (driverName != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    "Sürücü: $driverName",
                    style: GoogleFonts.poppins(
                      color: Colors.white70,
                      fontSize: 12,
                    ),
                  ),
                ],
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                "₺$price",
                style: GoogleFonts.poppins(
                  color: const Color(0xFFFFD700),
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              const SizedBox(height: 4),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  statusText,
                  style: GoogleFonts.poppins(color: statusColor, fontSize: 10),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
