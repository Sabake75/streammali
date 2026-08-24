import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

/// Shows a CGU page from the web app inside the mobile app (rather than
/// handing off to an external browser) — the text itself still lives only
/// on the web app, this just renders it in place. Pops `true` if the user
/// taps "J'accepte les CGU", `false`/`null` otherwise.
class TermsWebViewScreen extends StatefulWidget {
  final String url;
  final String title;

  const TermsWebViewScreen({super.key, required this.url, required this.title});

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
          child: Row(
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
                  child: const Text("J'accepte les CGU"),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
