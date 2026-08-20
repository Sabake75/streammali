import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/phone_number_field.dart';
import 'register_screen.dart';
import 'video_detail_screen.dart';

class LoginScreen extends StatefulWidget {
  final int? redirectVideoId;

  const LoginScreen({super.key, this.redirectVideoId});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _apiClient = ApiClient();

  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final result = await _apiClient.login(
        phone: _phoneController.text,
        password: _passwordController.text,
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
      appBar: AppBar(title: const Text('Connexion')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              PhoneNumberField(controller: _phoneController),
              const SizedBox(height: 12),
              TextFormField(
                controller: _passwordController,
                decoration: const InputDecoration(labelText: 'Mot de passe', border: OutlineInputBorder()),
                obscureText: true,
                validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
              ),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _submitting ? null : _submit,
                child: Text(_submitting ? 'Connexion…' : 'Se connecter'),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () {
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(
                      builder: (context) => RegisterScreen(redirectVideoId: widget.redirectVideoId),
                    ),
                  );
                },
                child: const Text("Pas de compte ? S'inscrire"),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
