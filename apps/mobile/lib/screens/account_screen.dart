import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../services/api_client.dart';
import '../services/auth_controller.dart';

/// "Mon compte" — mirrors apps/web/src/app/compte/page.tsx: self-service
/// data export and account deletion.
class AccountScreen extends StatefulWidget {
  const AccountScreen({super.key});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  final ApiClient _apiClient = ApiClient();
  bool _exporting = false;
  bool _deleting = false;

  Future<void> _export() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() => _exporting = true);
    try {
      final json = await _apiClient.exportAccountData(token);
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Mes données'),
          content: SizedBox(
            width: double.maxFinite,
            child: SingleChildScrollView(child: SelectableText(json)),
          ),
          actions: [
            TextButton(
              onPressed: () async {
                await Clipboard.setData(ClipboardData(text: json));
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Copié dans le presse-papiers.')),
                  );
                }
              },
              child: const Text('Copier'),
            ),
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Fermer'),
            ),
          ],
        ),
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  Future<void> _confirmDelete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer mon compte ?'),
        content: const Text(
          'Ton profil, ton numéro et ta pièce d\'identité sont supprimés définitivement '
          'et tu es déconnecté(e) partout. Cette action est irréversible.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(context).pop(false), child: const Text('Annuler')),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Supprimer', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() => _deleting = true);
    try {
      await _apiClient.deleteAccount(token);
      await AuthController.instance.clearSession();
      if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _deleting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = AuthController.instance.user;

    return Scaffold(
      appBar: AppBar(title: const Text('Mon compte')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (user != null)
            Text('${user.name} · ${user.phone}', style: Theme.of(context).textTheme.bodyMedium),
          const SizedBox(height: 24),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Télécharger mes données', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  const Text(
                    'Affiche tout ce que StreamMali détient sur toi (profil, achats, favoris, avis, '
                    'et pour un créateur : vidéos, revenus, retraits, messages).',
                  ),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: _exporting ? null : _export,
                    child: Text(_exporting ? 'Préparation…' : 'Télécharger mes données'),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Card(
            color: Colors.red.withValues(alpha: 0.05),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Supprimer mon compte',
                    style: TextStyle(fontWeight: FontWeight.bold, color: Colors.red),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Action irréversible. Un créateur doit d\'abord retirer son solde disponible, '
                    'sinon la suppression est refusée.',
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton(
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                    onPressed: _deleting ? null : _confirmDelete,
                    child: Text(_deleting ? 'Suppression…' : 'Supprimer mon compte'),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
