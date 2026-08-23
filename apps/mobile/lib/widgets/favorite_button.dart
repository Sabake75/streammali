import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_controller.dart';

class FavoriteButton extends StatefulWidget {
  final int videoId;
  final bool initialFavorited;

  const FavoriteButton({super.key, required this.videoId, required this.initialFavorited});

  @override
  State<FavoriteButton> createState() => _FavoriteButtonState();
}

class _FavoriteButtonState extends State<FavoriteButton> {
  final ApiClient _apiClient = ApiClient();
  late bool _favorited = widget.initialFavorited;
  bool _submitting = false;

  Future<void> _toggle() async {
    final token = AuthController.instance.token;
    if (token == null || _submitting) return;

    setState(() => _submitting = true);

    try {
      final favorited = await _apiClient.toggleFavorite(videoId: widget.videoId, token: token);
      if (mounted) setState(() => _favorited = favorited);
    } catch (_) {
      // Best-effort — the button just doesn't update on failure.
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: AuthController.instance,
      builder: (context, _) {
        if (!AuthController.instance.isAuthenticated) {
          return const SizedBox.shrink();
        }

        return OutlinedButton.icon(
          onPressed: _submitting ? null : _toggle,
          icon: Icon(
            _favorited ? Icons.favorite : Icons.favorite_border,
            color: _favorited ? Colors.red : null,
          ),
          label: Text(_favorited ? 'Dans mes favoris' : 'Ajouter aux favoris'),
        );
      },
    );
  }
}
