import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../models/user.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/app_logo.dart';
import '../widgets/phone_number_field.dart';
import '../widgets/pin_code_field.dart';
import '../widgets/privacy_policy_link.dart';
import '../widgets/terms_checkbox.dart';
import 'creator_screen.dart';

/// Routes to the short upgrade form for an already-signed-in viewer (same
/// account, same phone/purchase history) or the full registration form
/// otherwise — mirrors apps/web/src/app/inscription-createur/RegisterCreatorPageClient.tsx.
class RegisterCreatorScreen extends StatelessWidget {
  const RegisterCreatorScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final user = AuthController.instance.user;
    if (user != null) return const _UpgradeToCreatorScreen();
    return const _FullRegistrationScreen();
  }
}

class _UpgradeToCreatorScreen extends StatefulWidget {
  const _UpgradeToCreatorScreen();

  @override
  State<_UpgradeToCreatorScreen> createState() => _UpgradeToCreatorScreenState();
}

class _UpgradeToCreatorScreenState extends State<_UpgradeToCreatorScreen> {
  final _apiClient = ApiClient();
  String? _identityDocumentPath;
  String? _identityDocumentName;
  bool _termsAccepted = false;
  bool _submitting = false;
  String? _error;

  Future<void> _pickIdentityDocument() async {
    final result = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
    );
    final path = result?.files.single.path;
    if (path == null || !mounted) return;

    setState(() {
      _identityDocumentPath = path;
      _identityDocumentName = result!.files.single.name;
    });
  }

  Future<void> _submit() async {
    if (_identityDocumentPath == null) {
      setState(() => _error = "La pièce d'identité est requise.");
      return;
    }

    if (!_termsAccepted) {
      setState(() => _error = "Merci d'accepter les CGU pour continuer.");
      return;
    }

    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final upgraded = await _apiClient.upgradeToCreator(
        identityDocumentPath: _identityDocumentPath!,
        termsAccepted: _termsAccepted,
        token: token,
      );
      await AuthController.instance.setSession(token, upgraded);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => const CreatorScreen()),
      );
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.toString();
        _submitting = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = AuthController.instance.user as StoredUser;

    return Scaffold(
      appBar: AppBar(title: const Text('Devenir créateur')),
      body: SafeArea(
        top: false,
        child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Center(child: AppLogo(badgeSize: 44, fontSize: 24)),
            const SizedBox(height: 28),
            Text('Connecté en tant que ${user.name}. Plus qu\'une pièce d\'identité pour publier tes vidéos.'),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: _pickIdentityDocument,
              icon: const Icon(Icons.badge_outlined),
              label: Text(_identityDocumentName ?? "Choisir la pièce d'identité"),
            ),
            const SizedBox(height: 4),
            TermsCheckbox(
              value: _termsAccepted,
              onChanged: (value) => setState(() => _termsAccepted = value),
              termsUrl: '${ApiClient.webBaseUrl}/cgu-createur',
              linkLabel: 'CGU créateur',
            ),
            const PrivacyPolicyLink(),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            const SizedBox(height: 20),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: Text(_submitting ? 'Passage en créateur…' : 'Devenir créateur'),
            ),
          ],
        ),
        ),
      ),
    );
  }
}

class _FullRegistrationScreen extends StatefulWidget {
  const _FullRegistrationScreen();

  @override
  State<_FullRegistrationScreen> createState() => _FullRegistrationScreenState();
}

class _FullRegistrationScreenState extends State<_FullRegistrationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _apiClient = ApiClient();

  String? _identityDocumentPath;
  String? _identityDocumentName;
  bool _termsAccepted = false;
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
    if (path == null || !mounted) return;

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

    if (!_termsAccepted) {
      setState(() => _error = "Merci d'accepter les CGU pour continuer.");
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
        termsAccepted: _termsAccepted,
      );
      await AuthController.instance.setSession(result.token, result.user);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => const CreatorScreen()),
      );
    } catch (error) {
      if (!mounted) return;
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
      body: SafeArea(
        top: false,
        child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Center(child: AppLogo(badgeSize: 44, fontSize: 24)),
              const SizedBox(height: 28),
              const Text(
                "Une pièce d'identité est requise pour publier des vidéos sur StreamMali.",
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(labelText: 'Nom'),
                validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
              ),
              const SizedBox(height: 12),
              PhoneNumberField(controller: _phoneController),
              const SizedBox(height: 12),
              PinCodeField(controller: _passwordController),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: _pickIdentityDocument,
                icon: const Icon(Icons.badge_outlined),
                label: Text(_identityDocumentName ?? "Choisir la pièce d'identité"),
              ),
              const SizedBox(height: 4),
              TermsCheckbox(
                value: _termsAccepted,
                onChanged: (value) => setState(() => _termsAccepted = value),
                termsUrl: '${ApiClient.webBaseUrl}/cgu-createur',
                linkLabel: 'CGU créateur',
              ),
              const PrivacyPolicyLink(),
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
      ),
    );
  }
}
