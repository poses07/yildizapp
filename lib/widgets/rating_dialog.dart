import 'package:flutter/material.dart';
import '../services/api_service.dart';

class RatingDialog extends StatefulWidget {
  final int bookingId;
  final VoidCallback onSubmitted;

  const RatingDialog({
    super.key,
    required this.bookingId,
    required this.onSubmitted,
  });

  @override
  State<RatingDialog> createState() => _RatingDialogState();
}

class _RatingDialogState extends State<RatingDialog> {
  int _rating = 5;
  final TextEditingController _commentController = TextEditingController();
  final List<String> _selectedTags = [];
  bool _isLoading = false;

  final List<String> _quickTags = [
    "Kibar Sürücü",
    "Temiz Araç",
    "Güvenli Sürüş",
    "Hızlı Ulaşım",
    "Araç Kirli",
    "Kaba Sürücü",
    "Geç Kaldı",
    "Tehlikeli Sürüş",
  ];

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _submitRating() async {
    setState(() {
      _isLoading = true;
    });

    final tags = _selectedTags.join(',');
    final result = await ApiService().rateBooking(
      bookingId: widget.bookingId,
      rating: _rating,
      comment: _commentController.text,
      tags: tags,
    );

    if (!mounted) return;

    setState(() {
      _isLoading = false;
    });

    if (result['success'] == true) {
      widget.onSubmitted(); // Close dialog via callback
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Bir hata oluştu.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text("Yolculuğu Değerlendir", textAlign: TextAlign.center),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 10),
            // Star Rating
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(5, (index) {
                return IconButton(
                  onPressed: () {
                    setState(() {
                      _rating = index + 1;
                    });
                  },
                  icon: Icon(
                    index < _rating ? Icons.star : Icons.star_border,
                    color: Colors.amber,
                    size: 32,
                  ),
                );
              }),
            ),
            const SizedBox(height: 20),
            // Quick Tags
            Wrap(
              spacing: 8.0,
              runSpacing: 4.0,
              alignment: WrapAlignment.center,
              children: _quickTags.map((tag) {
                final isSelected = _selectedTags.contains(tag);
                return FilterChip(
                  label: Text(tag),
                  selected: isSelected,
                  onSelected: (bool selected) {
                    setState(() {
                      if (selected) {
                        _selectedTags.add(tag);
                      } else {
                        _selectedTags.remove(tag);
                      }
                    });
                  },
                  selectedColor: Colors.amber.withValues(alpha: 0.3),
                  checkmarkColor: Colors.amber,
                );
              }).toList(),
            ),
            const SizedBox(height: 20),
            // Comment Field
            TextField(
              controller: _commentController,
              decoration: const InputDecoration(
                hintText: "Yorumunuzu buraya yazın...",
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.all(12),
              ),
              maxLines: 3,
            ),
          ],
        ),
      ),
      actions: [
        if (_isLoading)
          const Center(child: CircularProgressIndicator())
        else
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _submitRating,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.amber,
                foregroundColor: Colors.black,
              ),
              child: const Text("Değerlendir"),
            ),
          ),
      ],
    );
  }
}
