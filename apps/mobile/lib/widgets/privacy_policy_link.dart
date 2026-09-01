import 'package:flutter/material.dart';

import '../screens/terms_webview_screen.dart';
import '../services/api_client.dart';

/// Small link to the privacy policy (web app page, shown in-app via
/// [TermsWebViewScreen]) — used wherever we collect personal data (signup)
/// or from the account menu, since a store-listing URL alone isn't enough:
/// Play Store also expects it to be reachable from inside the app.
class PrivacyPolicyLink extends StatelessWidget {
  const PrivacyPolicyLink({super.key});

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: TextButton(
        style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: const Size(0, 32)),
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (context) => TermsWebViewScreen(
                url: '${ApiClient.webBaseUrl}/politique-de-confidentialite',
                title: 'Politique de confidentialité',
                showAcceptButton: false,
              ),
            ),
          );
        },
        child: const Text('Politique de confidentialité'),
      ),
    );
  }
}
