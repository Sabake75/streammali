import 'package:flutter/material.dart';

import '../services/api_client.dart';

/// Friendly "couldn't load, try again" state for a failed FutureBuilder —
/// swaps out raw exception text (`ClientException with SocketException:
/// Failed host lookup…`), meaningless to this app's target audience, for a
/// plain message and a retry button. [error] (typically `snapshot.error`)
/// lets an [ApiException] — whose message already distinguishes a timeout
/// from a plain HTTP error — replace the generic fallback text, so "server
/// too slow" doesn't look identical to "you're offline".
class ErrorRetryView extends StatelessWidget {
  final VoidCallback onRetry;
  final Object? error;

  const ErrorRetryView({super.key, required this.onRetry, this.error});

  @override
  Widget build(BuildContext context) {
    final err = error;
    final message = err is ApiException
        ? err.message
        : "Impossible de charger le contenu. Vérifie ta connexion et réessaie.";

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.wifi_off, size: 40, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton(onPressed: onRetry, child: const Text('Réessayer')),
          ],
        ),
      ),
    );
  }
}
