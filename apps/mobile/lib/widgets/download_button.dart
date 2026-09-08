import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../services/download_manager.dart';

/// Shown under a purchased video (video detail screen) — offline download,
/// mobile-only (see DownloadManager for why there's no web equivalent).
class DownloadButton extends StatefulWidget {
  final int videoId;

  const DownloadButton({super.key, required this.videoId});

  @override
  State<DownloadButton> createState() => _DownloadButtonState();
}

class _DownloadButtonState extends State<DownloadButton> {
  bool _checking = true;
  bool _downloaded = false;
  bool _downloading = false;
  double _progress = 0;
  String? _error;

  @override
  void initState() {
    super.initState();
    _checkStatus();
  }

  Future<void> _checkStatus() async {
    final downloaded = await DownloadManager.instance.isDownloaded(widget.videoId);
    if (!mounted) return;
    setState(() {
      _downloaded = downloaded;
      _checking = false;
    });
  }

  Future<void> _startDownload() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() {
      _downloading = true;
      _progress = 0;
      _error = null;
    });

    try {
      await DownloadManager.instance.download(
        videoId: widget.videoId,
        token: token,
        onProgress: (percent) {
          if (mounted) setState(() => _progress = percent);
        },
      );
      if (!mounted) return;
      setState(() {
        _downloading = false;
        _downloaded = true;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _downloading = false;
        _error = error.message;
      });
    }
  }

  Future<void> _delete() async {
    await DownloadManager.instance.delete(widget.videoId);
    if (!mounted) return;
    setState(() => _downloaded = false);
  }

  @override
  Widget build(BuildContext context) {
    if (_checking) return const SizedBox.shrink();

    if (_downloading) {
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          SizedBox(
            width: 16,
            height: 16,
            child: CircularProgressIndicator(
              strokeWidth: 2,
              value: _progress > 0 ? _progress / 100 : null,
            ),
          ),
          const SizedBox(width: 8),
          Text(
            _progress > 0 ? 'Téléchargement… ${_progress.toStringAsFixed(0)}%' : 'Préparation…',
          ),
        ],
      );
    }

    if (_downloaded) {
      return TextButton.icon(
        onPressed: _delete,
        icon: const Icon(Icons.download_done, size: 18),
        label: const Text('Téléchargée — supprimer'),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        TextButton.icon(
          onPressed: _startDownload,
          icon: const Icon(Icons.download_outlined, size: 18),
          label: const Text('Télécharger (hors ligne)'),
        ),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(left: 12, top: 2),
            child: Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error, fontSize: 12),
            ),
          ),
      ],
    );
  }
}
