import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/phone_number_field.dart';
import 'creator_screen.dart';

class RegisterCreatorScreen extends StatefulWidget {
  const RegisterCreatorScreen({super.key});

  @override
  State<RegisterCreatorScreen> createState() => _RegisterCreatorScreenState();
}

class _RegisterCreatorScreenState extends State<RegisterCreatorScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _apiClient = ApiClient();

  String? _identityDocumentPath;
  String? _identityDocumentName;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _pickIdentityDocument() async {
    final result = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
    );
    final path = result?.files.single.path;
    if (path == null) return;

    setState(() {
      _identityDocumentPath = path;
      _identityDocumentName = result!.files.single.name;
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (_identityDocumentPath == null) {
      setState(() => _error = "La pièce d'identité est requise.");
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final result = await _apiClient.registerCreator(
        name: _nameController.text,
        phone: _phoneController.text,
        password: _passwordController.text,
        identityDocumentPath: _identityDocumentPath!,
      );
      await AuthController.instance.setSession(result.token, result.user);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => const CreatorScreen()),
      );
    } catch (error) {
      setState(() {
        _error = error.toString();
        _submitting = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inscription créateur')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                "Une pièce d'identité est requise pour publier des vidéos sur StreamMali.",
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(labelText: 'Nom', border: OutlineInputBorder()),
                validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
              ),
              const SizedBox(height: 12),
              PhoneNumberField(controller: _phoneController),
              const SizedBox(height: 12),
              TextFormField(
                controller: _passwordController,
                decoration: const InputDecoration(
                  labelText: 'Mot de passe (8 caractères min.)',
                  border: OutlineInputBorder(),
                ),
                obscureText: true,
                validator: (value) =>
                    (value == null || value.length < 8) ? '8 caractères minimum' : null,
              ),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: _pickIdentityDocument,
                icon: const Icon(Icons.badge_outlined),
                label: Text(_identityDocumentName ?? "Choisir la pièce d'identité"),
              ),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _submitting ? null : _submit,
                child: Text(_submitting ? 'Création…' : 'Créer mon compte créateur'),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Déjà un compte ? Se connecter'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
