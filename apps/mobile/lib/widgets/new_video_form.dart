import 'dart:async';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';

class NewVideoForm extends StatefulWidget {
  final VoidCallback onCreated;

  const NewVideoForm({super.key, required this.onCreated});

  @override
  State<NewVideoForm> createState() => _NewVideoFormState();
}

enum _NewVideoPhase { form, creating, uploading, processing, ready, failed }

class _NewVideoFormState extends State<NewVideoForm> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController(text: '100');
  final ApiClient _apiClient = ApiClient();

  String _category = '';
  List<VideoCategory> _categories = [];
  String? _filePath;
  _NewVideoPhase _phase = _NewVideoPhase.form;
  double _progress = 0;
  String? _error;
  int? _videoId;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _apiClient.fetchCategories().then((categories) {
      if (mounted) {
        setState(() {
          _categories = categories;
          if (_category.isEmpty && categories.isNotEmpty) _category = categories.first.value;
        });
      }
    }).catchError((_) {});
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _priceController.dispose();
    _pollTimer?.cancel();
    super.dispose();
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.pickFiles(type: FileType.video);
    final path = result?.files.single.path;
    if (path != null && mounted) setState(() => _filePath = path);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_filePath == null) {
      setState(() => _error = 'Choisis un fichier vidéo.');
      return;
    }

    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() {
      _phase = _NewVideoPhase.creating;
      _error = null;
    });

    try {
      // The file always goes straight from the device to Cloudflare, never
      // through our API — but Cloudflare's upload URL is tied to a video
      // record that has to exist first, so this is unavoidably two calls
      // even though it's one form/one tap for the creator.
      final video = await _apiClient.createVideo(
        token: token,
        title: _titleController.text,
        description: _descriptionController.text,
        category: _category,
        price: int.tryParse(_priceController.text),
      );
      _videoId = video.id;

      final uploadUrl = await _apiClient.createVideoUploadUrl(videoId: video.id, token: token);

      if (!mounted) return;
      setState(() => _phase = _NewVideoPhase.uploading);

      await _apiClient.uploadVideoFile(
        uploadUrl: uploadUrl,
        filePath: _filePath!,
        onProgress: (progress) {
          if (mounted) setState(() => _progress = progress);
        },
      );

      if (!mounted) return;
      setState(() => _phase = _NewVideoPhase.processing);
      _startPolling(token);
      widget.onCreated();
    } catch (err) {
      if (!mounted) return;
      setState(() {
        _error = err.toString();
        _phase = _NewVideoPhase.form;
      });
    }
  }

  void _startPolling(String token) {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 5), (_) async {
      try {
        final status = await _apiClient.fetchVideoSourceStatus(videoId: _videoId!, token: token);
        if (!mounted) return;
        if (status.value == 'ready') {
          setState(() => _phase = _NewVideoPhase.ready);
          _pollTimer?.cancel();
        } else if (status.value == 'failed') {
          setState(() => _phase = _NewVideoPhase.failed);
          _pollTimer?.cancel();
        }
      } catch (_) {
        // transient polling failure — try again next tick
      }
    });
  }

  void _addAnother() {
    setState(() {
      _phase = _NewVideoPhase.form;
      _titleController.clear();
      _descriptionController.clear();
      _priceController.text = '100';
      _filePath = null;
      _progress = 0;
      _videoId = null;
      _error = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_phase != _NewVideoPhase.form) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(_titleController.text, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 8),
              if (_phase == _NewVideoPhase.creating) const Text('Création…'),
              if (_phase == _NewVideoPhase.uploading)
                Text('Envoi en cours… ${_progress.toStringAsFixed(0)}%'),
              if (_phase == _NewVideoPhase.processing) const Text('Traitement en cours…'),
              if (_phase == _NewVideoPhase.ready)
                const Text('Fichier vidéo prêt.', style: TextStyle(color: Colors.green)),
              if (_phase == _NewVideoPhase.failed)
                const Text('Échec du traitement.', style: TextStyle(color: Colors.red)),
              if (_phase == _NewVideoPhase.ready || _phase == _NewVideoPhase.failed) ...[
                const SizedBox(height: 8),
                TextButton(onPressed: _addAnother, child: const Text('Ajouter une autre vidéo')),
              ],
            ],
          ),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: _titleController,
                decoration: const InputDecoration(labelText: 'Titre'),
                validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _descriptionController,
                decoration: const InputDecoration(labelText: 'Description'),
                maxLines: 2,
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                initialValue: _category.isEmpty ? null : _category,
                decoration: const InputDecoration(labelText: 'Catégorie'),
                items: _categories
                    .map((category) => DropdownMenuItem(value: category.value, child: Text(category.label)))
                    .toList(),
                onChanged: (value) => setState(() => _category = value ?? _category),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _priceController,
                decoration: const InputDecoration(labelText: 'Prix (FCFA)'),
                keyboardType: TextInputType.number,
              ),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: _pickFile,
                icon: const Icon(Icons.upload_file),
                label: Text(_filePath == null ? 'Choisir le fichier vidéo' : 'Fichier sélectionné ✓'),
              ),
              if (_error != null) ...[
                const SizedBox(height: 8),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _phase == _NewVideoPhase.form ? _submit : null,
                child: Text(_phase == _NewVideoPhase.form ? 'Créer et envoyer' : 'Envoi…'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
