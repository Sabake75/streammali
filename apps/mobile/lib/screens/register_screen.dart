import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/app_logo.dart';
import '../widgets/phone_number_field.dart';
import '../widgets/pin_code_field.dart';
import '../widgets/privacy_policy_link.dart';
import '../widgets/terms_checkbox.dart';
import 'register_creator_screen.dart';
import 'video_detail_screen.dart';

class RegisterScreen extends StatefulWidget {
  final int? redirectVideoId;

  const RegisterScreen({super.key, this.redirectVideoId});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _apiClient = ApiClient();

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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    if (!_termsAccepted) {
      setState(() => _error = "Merci d'accepter les CGU pour continuer.");
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final result = await _apiClient.register(
        name: _nameController.text,
        phone: _phoneController.text,
        password: _passwordController.text,
        termsAccepted: _termsAccepted,
      );
      await AuthController.instance.setSession(result.token, result.user);
      if (!mounted) return;
      _goBack();
    } catch (error) {
      setState(() {
        _error = error.toString();
        _submitting = false;
      });
    }
  }

  void _goBack() {
    if (widget.redirectVideoId != null) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => VideoDetailScreen(videoId: widget.redirectVideoId!)),
      );
    } else {
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inscription')),
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
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(labelText: 'Nom'),
                validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
              ),
              const SizedBox(height: 12),
              PhoneNumberField(controller: _phoneController),
              const SizedBox(height: 12),
              PinCodeField(controller: _passwordController),
              const SizedBox(height: 4),
              TermsCheckbox(
                value: _termsAccepted,
                onChanged: (value) => setState(() => _termsAccepted = value),
                termsUrl: '${ApiClient.webBaseUrl}/cgu-spectateur',
              ),
              const PrivacyPolicyLink(),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _submitting ? null : _submit,
                child: Text(_submitting ? 'Création…' : 'Créer mon compte'),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () {
                  Navigator.of(context).pop();
                },
                child: const Text('Déjà un compte ? Se connecter'),
              ),
              TextButton(
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (context) => const RegisterCreatorScreen()),
                  );
                },
                child: const Text('Tu es créateur ? Inscription créateur'),
              ),
            ],
          ),
        ),
        ),
      ),
    );
  }
}
