import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../screens/login_screen.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';

class PurchaseSection extends StatefulWidget {
  final int videoId;

  const PurchaseSection({super.key, required this.videoId});

  @override
  State<PurchaseSection> createState() => _PurchaseSectionState();
}

class _PurchaseSectionState extends State<PurchaseSection> {
  final ApiClient _apiClient = ApiClient();
  late final TextEditingController _msisdnController;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _msisdnController = TextEditingController(text: AuthController.instance.user?.phone ?? '');
  }

  @override
  void dispose() {
    _msisdnController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final result = await _apiClient.purchaseVideo(
        videoId: widget.videoId,
        payerMsisdn: _msisdnController.text,
        token: token,
      );

      final launched = await launchUrl(
        Uri.parse(result.paymentUrl),
        mode: LaunchMode.externalApplication,
      );

      if (!launched) {
        throw ApiException("Impossible d'ouvrir la page de paiement Orange Money.");
      }
    } catch (error) {
      setState(() => _error = error.toString());
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
          return FilledButton(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (context) => LoginScreen(redirectVideoId: widget.videoId),
                ),
              );
            },
            child: const Text('Se connecter pour acheter'),
          );
        }

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextField(
              controller: _msisdnController,
              decoration: const InputDecoration(
                labelText: 'Numéro Orange Money',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 8),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: Text(_submitting ? 'Redirection…' : 'Acheter'),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
          ],
        );
      },
    );
  }
}
