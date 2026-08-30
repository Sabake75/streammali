import 'package:flutter/material.dart';

import '../models/review.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';

class ReviewSection extends StatefulWidget {
  final int videoId;
  final bool purchased;

  const ReviewSection({super.key, required this.videoId, required this.purchased});

  @override
  State<ReviewSection> createState() => _ReviewSectionState();
}

class _ReviewSectionState extends State<ReviewSection> {
  final ApiClient _apiClient = ApiClient();
  final _commentController = TextEditingController();
  List<Review>? _reviews;
  int _rating = 5;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  void _reload() {
    _apiClient.fetchReviews(widget.videoId).then((response) {
      if (mounted) setState(() => _reviews = response.data);
    }).catchError((_) {});
  }

  Future<void> _submit() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await _apiClient.submitReview(
        videoId: widget.videoId,
        rating: _rating,
        comment: _commentController.text,
        token: token,
      );
      _commentController.clear();
      _reload();
    } catch (error) {
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final reviews = _reviews;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Avis', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        if (widget.purchased) ...[
          Row(
            children: List.generate(5, (index) {
              final value = index + 1;
              return IconButton(
                onPressed: () => setState(() => _rating = value),
                tooltip: '$value étoile${value > 1 ? 's' : ''}',
                icon: Icon(
                  value <= _rating ? Icons.star : Icons.star_border,
                  color: Colors.amber,
                ),
              );
            }),
          ),
          TextField(
            controller: _commentController,
            decoration: const InputDecoration(
              labelText: 'Un commentaire (optionnel)',
            ),
            minLines: 2,
            maxLines: 4,
          ),
          if (_error != null) ...[
            const SizedBox(height: 8),
            Text(_error!, style: const TextStyle(color: Colors.red)),
          ],
          const SizedBox(height: 8),
          FilledButton(
            onPressed: _submitting ? null : _submit,
            child: Text(_submitting ? 'Envoi…' : 'Publier mon avis'),
          ),
          const SizedBox(height: 12),
        ],
        if (reviews == null) const Center(child: CircularProgressIndicator()),
        if (reviews != null && reviews.isEmpty)
          const Text('Aucun avis pour l\'instant.', style: TextStyle(color: Colors.grey)),
        ...?reviews?.map(
          (review) => Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(review.user.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                      Text('★' * review.rating, style: const TextStyle(color: Colors.amber)),
                    ],
                  ),
                  if (review.comment != null && review.comment!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(review.comment!),
                  ],
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}
