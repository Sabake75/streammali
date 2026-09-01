import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

/// Shows a legal page (CGU, privacy policy…) from the web app inside the
/// mobile app (rather than handing off to an external browser) — the text
/// itself still lives only on the web app, this just renders it in place.
/// With [showAcceptButton] (the registration consent flow), pops `true` if
/// the user taps accept, `false`/`null` otherwise. Without it (e.g. reading
/// the privacy policy from the account menu), there's just a close button.
class TermsWebViewScreen extends StatefulWidget {
  final String url;
  final String title;
  final bool showAcceptButton;
  final String acceptLabel;

  const TermsWebViewScreen({
    super.key,
    required this.url,
    required this.title,
    this.showAcceptButton = true,
    this.acceptLabel = "J'accepte les CGU",
  });

  @override
  State<TermsWebViewScreen> createState() => _TermsWebViewScreenState();
}

class _TermsWebViewScreenState extends State<TermsWebViewScreen> {
  late final WebViewController _controller;
  double _progress = 0;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (progress) {
            if (mounted) setState(() => _progress = progress / 100);
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: Column(
        children: [
          if (_progress < 1) LinearProgressIndicator(value: _progress),
          Expanded(child: WebViewWidget(controller: _controller)),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: widget.showAcceptButton
              ? Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.of(context).pop(false),
                        child: const Text('Fermer'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton(
                        onPressed: () => Navigator.of(context).pop(true),
                        child: Text(widget.acceptLabel),
                      ),
                    ),
                  ],
                )
              : OutlinedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Fermer'),
                ),
        ),
      ),
    );
  }
}
