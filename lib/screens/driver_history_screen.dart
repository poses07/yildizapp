import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class DriverHistoryScreen extends StatelessWidget {
  const DriverHistoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // Mock Data for History
    final List<Map<String, dynamic>> history = [
      {
        "date": "30.01.2024 14:30",
        "from": "Kadıköy Rıhtım",
        "to": "Bağdat Caddesi",
        "price": "250 TL",
        "status": "completed"
      },
      {
        "date": "29.01.2024 18:45",
        "from": "Üsküdar Meydan",
        "to": "Çamlıca Tepesi",
        "price": "180 TL",
        "status": "completed"
      },
      {
        "date": "29.01.2024 09:15",
        "from": "Ataşehir",
        "to": "Sabiha Gökçen",
        "price": "450 TL",
        "status": "completed"
      },
    ];

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
      body: history.isEmpty
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
              itemCount: history.length,
              itemBuilder: (context, index) {
                final item = history[index];
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
                        color: Colors.green.withValues(alpha: 0.1),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.check, color: Colors.green),
                    ),
                    title: Text(
                      item['price'],
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
                                item['from'],
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
                                item['to'],
                                style: GoogleFonts.poppins(color: Colors.white),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          item['date'],
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
