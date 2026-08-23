import 'dart:async';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_controller.dart';

class VideoUploadWidget extends StatefulWidget {
  final int videoId;
  final String initialStatus;
  final VoidCallback onStatusChange;

  const VideoUploadWidget({
    super.key,
    required this.videoId,
    required this.initialStatus,
    required this.onStatusChange,
  });

  @override
  State<VideoUploadWidget> createState() => _VideoUploadWidgetState();
}

class _VideoUploadWidgetState extends State<VideoUploadWidget> {
  final ApiClient _apiClient = ApiClient();
  late String _status;
  double _progress = 0;
  String? _error;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _status = widget.initialStatus;
    if (_status == 'processing') _startPolling();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 5), (_) async {
      final token = AuthController.instance.token;
      if (token == null) return;

      try {
        final status = await _apiClient.fetchVideoSourceStatus(videoId: widget.videoId, token: token);
        if (!mounted) return;
        setState(() => _status = status.value);
        if (status.value != 'processing') {
          _pollTimer?.cancel();
          widget.onStatusChange();
        }
      } catch (_) {
        // transient polling failure — try again next tick
      }
    });
  }

  Future<void> _pickAndUpload() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    final result = await FilePicker.pickFiles(type: FileType.video);
    final path = result?.files.single.path;
    if (path == null) return;

    setState(() {
      _error = null;
      _progress = 0;
    });

    try {
      final uploadUrl = await _apiClient.createVideoUploadUrl(videoId: widget.videoId, token: token);

      await _apiClient.uploadVideoFile(
        uploadUrl: uploadUrl,
        filePath: path,
        onProgress: (progress) {
          if (!mounted) return;
          setState(() => _progress = progress);
        },
      );

      if (!mounted) return;
      setState(() => _status = 'processing');
      _startPolling();
      widget.onStatusChange();
    } catch (err) {
      setState(() => _error = err.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_status == 'ready') {
      return const Text('Fichier vidéo prêt.', style: TextStyle(color: Colors.green));
    }

    if (_status == 'processing') {
      return Text(
        _progress > 0 && _progress < 100
            ? 'Envoi en cours… ${_progress.toStringAsFixed(0)}%'
            : 'Traitement en cours…',
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        OutlinedButton.icon(
          onPressed: _pickAndUpload,
          icon: const Icon(Icons.upload_file),
          label: Text(_status == 'failed' ? 'Réessayer' : 'Envoyer le fichier vidéo'),
        ),
        if (_progress > 0) Text('Envoi en cours… ${_progress.toStringAsFixed(0)}%'),
        if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
      ],
    );
  }
}
