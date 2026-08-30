import 'package:flutter/material.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

import 'screens/catalogue_screen.dart';
import 'services/auth_controller.dart';
import 'theme.dart';

/// Empty by default — no Sentry account exists for this project yet (audit
/// finding: no crash reporting anywhere, API or mobile). An empty DSN
/// leaves the SDK fully inert ("the SDK will not send any events" — see
/// sentry_options.dart). Activate with
/// `--dart-define=SENTRY_DSN=https://...`, same pattern as API_BASE_URL.
const _sentryDsn = String.fromEnvironment('SENTRY_DSN', defaultValue: '');

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AuthController.instance.loadFromStorage();

  await SentryFlutter.init(
    (options) {
      options.dsn = _sentryDsn;
      options.tracesSampleRate = 0;
    },
    appRunner: () => runApp(const StreamMaliApp()),
  );
}

class StreamMaliApp extends StatelessWidget {
  const StreamMaliApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'StreamMali',
      theme: AppTheme.light(),
      darkTheme: AppTheme.dark(),
      home: const CatalogueScreen(),
    );
  }
}
