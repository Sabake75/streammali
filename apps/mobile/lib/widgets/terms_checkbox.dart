import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';

import '../screens/terms_webview_screen.dart';
import '../theme.dart';

/// Required "I accept the CGU" checkbox shown on both registration screens.
/// The CGU text itself lives on the web app (not duplicated natively here)
/// — tapping the link opens it in an in-app WebView rather than handing off
/// to an external browser, so reading and accepting happens without leaving
/// the registration flow. Accepting there checks the box automatically.
class TermsCheckbox extends StatelessWidget {
  final bool value;
  final ValueChanged<bool> onChanged;
  final String termsUrl;
  final String linkLabel;
  final String? trailingNote;

  const TermsCheckbox({
    super.key,
    required this.value,
    required this.onChanged,
    required this.termsUrl,
    this.linkLabel = 'CGU',
    this.trailingNote,
  });

  Future<void> _openTerms(BuildContext context) async {
    final accepted = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (context) => TermsWebViewScreen(url: termsUrl, title: linkLabel),
      ),
    );
    if (accepted == true) onChanged(true);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      decoration: BoxDecoration(
        color: value ? AppColors.orange50 : Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: value ? AppColors.orange100 : AppColors.neutral300),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Checkbox(value: value, onChanged: (checked) => onChanged(checked ?? false)),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(top: 12, right: 8),
              child: RichText(
                text: TextSpan(
                  style: DefaultTextStyle.of(context).style,
                  children: [
                    const TextSpan(text: "J'ai lu et j'accepte les "),
                    TextSpan(
                      text: linkLabel,
                      style: const TextStyle(
                        color: AppColors.orange600,
                        fontWeight: FontWeight.w600,
                        decoration: TextDecoration.underline,
                      ),
                      recognizer: TapGestureRecognizer()..onTap = () => _openTerms(context),
                    ),
                    if (trailingNote != null) TextSpan(text: ' $trailingNote'),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
