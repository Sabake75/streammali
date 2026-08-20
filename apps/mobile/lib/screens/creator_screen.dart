import 'package:flutter/material.dart';

import '../models/creator_video.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../utils/formatting.dart';
import '../widgets/balance_and_payouts.dart';
import '../widgets/video_upload_widget.dart';
import 'register_creator_screen.dart';

class CreatorScreen extends StatefulWidget {
  const CreatorScreen({super.key});

  @override
  State<CreatorScreen> createState() => _CreatorScreenState();
}

class _CreatorScreenState extends State<CreatorScreen> {
  final ApiClient _apiClient = ApiClient();
  List<CreatorVideo>? _videos;
  String? _error;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  Future<void> _reload() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    try {
      final videos = await _apiClient.fetchMyVideos(token);
      if (!mounted) return;
      setState(() {
        _videos = videos;
        _error = null;
      });
    } catch (err) {
      if (!mounted) return;
      setState(() => _error = err.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Espace créateur')),
      body: ListenableBuilder(
        listenable: AuthController.instance,
        builder: (context, _) {
          final user = AuthController.instance.user;

          if (user == null) {
            return const Center(child: Text('Connecte-toi pour accéder à cet espace.'));
          }

          if (user.role != 'creator') {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text(
                      'Cet espace est réservé aux comptes créateur.',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton(
                      onPressed: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(builder: (context) => const RegisterCreatorScreen()),
                        );
                      },
                      child: const Text('Créer un compte créateur'),
                    ),
                  ],
                ),
              ),
            );
          }

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const BalanceAndPayouts(),
              const SizedBox(height: 24),
              _NewVideoForm(onCreated: _reload),
              const SizedBox(height: 24),
              Text('Mes vidéos', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
              if (_videos == null && _error == null) const Center(child: CircularProgressIndicator()),
              if (_videos != null && _videos!.isEmpty) const Text('Aucune vidéo pour l\'instant.'),
              ...?_videos?.map(
                (video) => Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(video.title, style: Theme.of(context).textTheme.titleMedium),
                                  Text(
                                    '${video.category.label} · ${formatDuration(video.durationSeconds)} · ${formatPrice(video.price)}',
                                    style: Theme.of(context).textTheme.bodySmall,
                                  ),
                                ],
                              ),
                            ),
                            Chip(label: Text(video.status.label)),
                          ],
                        ),
                        if (video.status.value == 'rejected' && video.rejectionReason != null) ...[
                          const SizedBox(height: 4),
                          Text(
                            'Motif du refus : ${video.rejectionReason}',
                            style: const TextStyle(color: Colors.red),
                          ),
                        ],
                        const SizedBox(height: 8),
                        VideoUploadWidget(
                          videoId: video.id,
                          initialStatus: video.sourceStatus.value,
                          onStatusChange: _reload,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _NewVideoForm extends StatefulWidget {
  final VoidCallback onCreated;

  const _NewVideoForm({required this.onCreated});

  @override
  State<_NewVideoForm> createState() => _NewVideoFormState();
}

class _NewVideoFormState extends State<_NewVideoForm> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _priceController = TextEditingController(text: '25');
  final ApiClient _apiClient = ApiClient();

  String _category = videoCategories.first.value;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _priceController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await _apiClient.createVideo(
        token: token,
        title: _titleController.text,
        description: _descriptionController.text,
        category: _category,
        price: int.tryParse(_priceController.text),
      );
      _titleController.clear();
      _descriptionController.clear();
      _priceController.text = '25';
      widget.onCreated();
    } catch (err) {
      setState(() => _error = err.toString());
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('Nouvelle vidéo', style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 12),
              TextFormField(
                controller: _titleController,
                decoration: const InputDecoration(labelText: 'Titre', border: OutlineInputBorder()),
                validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _descriptionController,
                decoration: const InputDecoration(labelText: 'Description', border: OutlineInputBorder()),
                maxLines: 2,
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                initialValue: _category,
                decoration: const InputDecoration(labelText: 'Catégorie', border: OutlineInputBorder()),
                items: videoCategories
                    .map((category) => DropdownMenuItem(value: category.value, child: Text(category.label)))
                    .toList(),
                onChanged: (value) => setState(() => _category = value ?? _category),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _priceController,
                decoration: const InputDecoration(labelText: 'Prix (FCFA)', border: OutlineInputBorder()),
                keyboardType: TextInputType.number,
              ),
              if (_error != null) ...[
                const SizedBox(height: 8),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _submitting ? null : _submit,
                child: Text(_submitting ? 'Création…' : 'Créer'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
