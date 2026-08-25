import 'package:flutter/material.dart';

import 'screens/catalogue_screen.dart';
import 'services/auth_controller.dart';
import 'theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AuthController.instance.loadFromStorage();
  runApp(const StreamMaliApp());
}

class StreamMaliApp extends StatelessWidget {
  const StreamMaliApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'StreamMali',
      theme: AppTheme.light(),
      home: const CatalogueScreen(),
    );
  }
}
