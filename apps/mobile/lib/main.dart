import 'package:flutter/material.dart';

import 'screens/catalogue_screen.dart';

void main() {
  runApp(const StreamMaliApp());
}

class StreamMaliApp extends StatelessWidget {
  const StreamMaliApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'StreamMali',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.amber),
        useMaterial3: true,
      ),
      home: const CatalogueScreen(),
    );
  }
}
