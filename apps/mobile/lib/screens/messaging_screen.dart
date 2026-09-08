import 'package:flutter/material.dart';

import '../widgets/messaging.dart';

/// Support channel with the moderation team — shared by creators (their
/// existing support channel) and viewers alike, not creator-specific.
class MessagingScreen extends StatelessWidget {
  const MessagingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Messagerie')),
      body: const SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Messaging(),
        ),
      ),
    );
  }
}
