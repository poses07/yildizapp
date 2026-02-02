import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../services/api_service.dart';

class DriverHistoryScreen extends StatefulWidget {
  final Map<String, dynamic>? driverData;

  const DriverHistoryScreen({super.key, this.driverData});

  @override
  State<DriverHistoryScreen> createState() => _DriverHistoryScreenState();
}

class _DriverHistoryScreenState extends State<DriverHistoryScreen> {
  List<dynamic> _history = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    if (widget.driverData == null) {
      setState(() => _isLoading = false);
      return;
    }

    final driverId = widget.driverData!['driver_details']?['id'] ?? widget.driverData!['id'];
    if (driverId != null) {
      final bookings = await ApiService().getMyBookings(driverId: int.parse(driverId.toString()));
      if (mounted) {
        setState(() {
          _history = bookings;
          _isLoading = false;
        });
      }
    } else {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1E1E1E),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        title: Text(
          "Geçmiş İşler",
          style: GoogleFonts.poppins(color: Colors.white),
        ),
        automaticallyImplyLeading: false,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFFFD700)))
          : _history.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.history, size: 80, color: Colors.white24),
                      const SizedBox(height: 16),
                      Text(
                        "Henüz geçmiş iş kaydı yok.",
                        style: GoogleFonts.poppins(color: Colors.white54),
                      ),
                    ],
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _history.length,
                  itemBuilder: (context, index) {
                    final item = _history[index];
                    // Data parsing
                    final price = item['price'] ?? '0';
                    final pickup = item['pickup_address'] ?? 'Bilinmiyor';
                    final dropoff = item['dropoff_address'] ?? 'Bilinmiyor';
                    final date = item['created_at'] ?? '';
                    final status = item['status'] ?? 'completed';

                    return Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: const Color(0xFF2C2C2C),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: ListTile(
                        contentPadding: const EdgeInsets.all(16),
                        leading: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: (status == 'cancelled') 
                                ? Colors.red.withValues(alpha: 0.1) 
                                : Colors.green.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            (status == 'cancelled') ? Icons.close : Icons.check, 
                            color: (status == 'cancelled') ? Colors.red : Colors.green
                          ),
                        ),
                        title: Text(
                          "$price TL",
                          style: GoogleFonts.poppins(
                            color: Colors.amber,
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                          ),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                const Icon(Icons.my_location, size: 14, color: Colors.white54),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    pickup,
                                    style: GoogleFonts.poppins(color: Colors.white70),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.location_on, size: 14, color: Colors.redAccent),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    dropoff,
                                    style: GoogleFonts.poppins(color: Colors.white),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              date,
                              style: GoogleFonts.poppins(
                                color: Colors.white38,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}
