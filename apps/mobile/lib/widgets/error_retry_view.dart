import 'package:flutter/material.dart';

/// Friendly "couldn't load, try again" state for a failed FutureBuilder —
/// swaps out raw exception text (`ClientException with SocketException:
/// Failed host lookup…`), meaningless to this app's target audience, for a
/// plain message and a retry button.
class ErrorRetryView extends StatelessWidget {
  final VoidCallback onRetry;

  const ErrorRetryView({super.key, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.wifi_off, size: 40, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            const Text(
              "Impossible de charger le contenu. Vérifie ta connexion et réessaie.",
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            OutlinedButton(onPressed: onRetry, child: const Text('Réessayer')),
          ],
        ),
      ),
    );
  }
}
