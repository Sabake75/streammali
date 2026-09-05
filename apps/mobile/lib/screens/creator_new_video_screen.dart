import 'package:flutter/material.dart';

import '../widgets/new_video_form.dart';

class CreatorNewVideoScreen extends StatelessWidget {
  final VoidCallback onCreated;

  const CreatorNewVideoScreen({super.key, required this.onCreated});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Nouvelle vidéo')),
      body: SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: NewVideoForm(onCreated: onCreated),
        ),
      ),
    );
  }
}
