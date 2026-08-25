import 'package:flutter/material.dart';

import '../models/message.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';

class Messaging extends StatefulWidget {
  const Messaging({super.key});

  @override
  State<Messaging> createState() => _MessagingState();
}

class _MessagingState extends State<Messaging> {
  final ApiClient _apiClient = ApiClient();
  final _bodyController = TextEditingController();

  List<Message>? _messages;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  @override
  void dispose() {
    _bodyController.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    try {
      final messages = await _apiClient.fetchMyMessages(token);
      if (!mounted) return;
      setState(() => _messages = messages);
    } catch (_) {
      // transient load failure — the section just stays empty
    }
  }

  Future<void> _submit() async {
    final token = AuthController.instance.token;
    if (token == null || _bodyController.text.trim().isEmpty) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await _apiClient.sendMessage(body: _bodyController.text, token: token);
      _bodyController.clear();
      await _reload();
    } catch (err) {
      setState(() => _error = err.toString());
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Messagerie avec la modération', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            if (_messages == null) const Text('Chargement…'),
            if (_messages != null && _messages!.isEmpty)
              const Text(
                "Aucun message pour l'instant. Pose une question à la modération ci-dessous.",
                style: TextStyle(color: Colors.grey),
              ),
            if (_messages != null && _messages!.isNotEmpty)
              ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 240),
                child: ListView.builder(
                  shrinkWrap: true,
                  itemCount: _messages!.length,
                  itemBuilder: (context, index) {
                    final message = _messages![index];
                    final isModerator = message.sender.role == 'moderator';
                    return Align(
                      alignment: isModerator ? Alignment.centerLeft : Alignment.centerRight,
                      child: Container(
                        margin: const EdgeInsets.symmetric(vertical: 4),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.7),
                        decoration: BoxDecoration(
                          color: isModerator ? Colors.grey.shade200 : Theme.of(context).colorScheme.primary,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              isModerator ? 'Modération' : message.sender.name,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: isModerator ? Colors.black54 : Colors.white70,
                              ),
                            ),
                            Text(
                              message.body,
                              style: TextStyle(color: isModerator ? Colors.black87 : Colors.white),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
            const SizedBox(height: 8),
            TextField(
              controller: _bodyController,
              decoration: const InputDecoration(
                labelText: 'Message',
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
              child: Text(_submitting ? 'Envoi…' : 'Envoyer'),
            ),
          ],
        ),
      ),
    );
  }
}
