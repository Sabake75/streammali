import 'package:flutter/material.dart';

import 'screens/catalogue_screen.dart';
import 'services/auth_controller.dart';

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
      theme: ThemeData(
        // ColorScheme.fromSeed's default primary tone is intentionally
        // low-chroma at warm hues (it produced a muddy olive-brown for
        // amber, and even a brownish tone for this orange) — override
        // primary/onPrimary explicitly to the web app's actual brand
        // orange (Tailwind orange-600) so buttons match across platforms,
        // while keeping the rest of the seeded scheme for harmony.
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFFEA580C)).copyWith(
          primary: const Color(0xFFEA580C),
          onPrimary: Colors.white,
        ),
        useMaterial3: true,
      ),
      home: const CatalogueScreen(),
    );
  }
}
