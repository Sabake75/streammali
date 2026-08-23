import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

/// Required "I accept the CGU" checkbox shown on both registration screens.
/// The CGU text itself lives on the web app (not duplicated natively here)
/// — tapping the link opens it in an external browser.
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

  Future<void> _openTerms() async {
    await launchUrl(Uri.parse(termsUrl), mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Checkbox(value: value, onChanged: (checked) => onChanged(checked ?? false)),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(top: 12),
            child: RichText(
              text: TextSpan(
                style: DefaultTextStyle.of(context).style,
                children: [
                  const TextSpan(text: "J'ai lu et j'accepte les "),
                  TextSpan(
                    text: linkLabel,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.primary,
                      fontWeight: FontWeight.w600,
                    ),
                    recognizer: TapGestureRecognizer()..onTap = _openTerms,
                  ),
                  if (trailingNote != null) TextSpan(text: ' $trailingNote'),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}
