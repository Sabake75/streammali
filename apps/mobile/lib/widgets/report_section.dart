import 'package:flutter/material.dart';

import '../screens/login_screen.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';

class ReportSection extends StatefulWidget {
  final int videoId;

  const ReportSection({super.key, required this.videoId});

  @override
  State<ReportSection> createState() => _ReportSectionState();
}

class _ReportSectionState extends State<ReportSection> {
  final ApiClient _apiClient = ApiClient();
  final _reasonController = TextEditingController();
  bool _open = false;
  bool _submitting = false;
  String? _error;
  String? _confirmation;

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final token = AuthController.instance.token;
    if (token == null || _reasonController.text.trim().isEmpty) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final message = await _apiClient.reportVideo(
        videoId: widget.videoId,
        reason: _reasonController.text,
        token: token,
      );
      setState(() => _confirmation = message);
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
          return TextButton(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (context) => LoginScreen(redirectVideoId: widget.videoId),
                ),
              );
            },
            child: const Text('Se connecter pour signaler ce contenu'),
          );
        }

        if (_confirmation != null) {
          return Text(_confirmation!, style: const TextStyle(color: Colors.grey));
        }

        if (!_open) {
          return TextButton(
            onPressed: () => setState(() => _open = true),
            child: const Text('Signaler ce contenu'),
          );
        }

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextField(
              controller: _reasonController,
              decoration: const InputDecoration(
                labelText: 'Pourquoi signaler cette vidéo ?',
              ),
              minLines: 2,
              maxLines: 4,
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            const SizedBox(height: 8),
            Row(
              children: [
                FilledButton(
                  onPressed: _submitting ? null : _submit,
                  child: Text(_submitting ? 'Envoi…' : 'Envoyer le signalement'),
                ),
                const SizedBox(width: 8),
                TextButton(
                  onPressed: () => setState(() => _open = false),
                  child: const Text('Annuler'),
                ),
              ],
            ),
          ],
        );
      },
    );
  }
}
