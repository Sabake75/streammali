import 'package:flutter/material.dart';

import '../widgets/messaging.dart';

class CreatorMessagingScreen extends StatelessWidget {
  const CreatorMessagingScreen({super.key});

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
