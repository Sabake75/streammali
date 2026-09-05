import 'package:flutter/material.dart';

import '../widgets/stats.dart';

class CreatorStatsScreen extends StatelessWidget {
  const CreatorStatsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Statistiques')),
      body: const SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: Stats(),
        ),
      ),
    );
  }
}
