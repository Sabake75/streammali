import 'package:flutter/material.dart';

import '../models/creator_video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../utils/formatting.dart';
import '../widgets/status_pill.dart';
import '../widgets/video_upload_widget.dart';
import '../theme.dart';
import 'creator_balance_screen.dart';
import 'login_screen.dart';
import 'messaging_screen.dart';
import 'creator_new_video_screen.dart';
import 'creator_stats_screen.dart';
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
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = 'Impossible de charger tes vidéos. Vérifie ta connexion et réessaie.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Espace créateur')),
      body: SafeArea(
        top: false,
        child: ListenableBuilder(
        listenable: AuthController.instance,
        builder: (context, _) {
          final user = AuthController.instance.user;

          if (user == null) {
            return _CreatorGateCard(
              subtitle: 'Connecte-toi ou crée un compte créateur pour commencer à être payé à chaque vue.',
              children: [
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => const RegisterCreatorScreen()),
                      );
                    },
                    child: const Text('Crée un compte créateur'),
                  ),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => const LoginScreen()),
                    );
                  },
                  child: const Text('Déjà un compte ? Connecte-toi'),
                ),
              ],
            );
          }

          if (user.role != 'creator') {
            return _CreatorGateCard(
              subtitle: "Cet espace est réservé aux comptes créateur (pièce d'identité requise).",
              children: [
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => const RegisterCreatorScreen()),
                      );
                    },
                    child: const Text('Devenir créateur'),
                  ),
                ),
              ],
            );
          }

          return RefreshIndicator(
            onRefresh: _reload,
            child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            children: [
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 1.5,
                children: [
                  _ActionTile(
                    icon: Icons.bar_chart,
                    label: 'Statistiques',
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => const CreatorStatsScreen()),
                    ),
                  ),
                  _ActionTile(
                    icon: Icons.account_balance_wallet_outlined,
                    label: 'Solde & retraits',
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => const CreatorBalanceScreen()),
                    ),
                  ),
                  _ActionTile(
                    icon: Icons.chat_bubble_outline,
                    label: 'Messagerie',
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => const MessagingScreen()),
                    ),
                  ),
                  _ActionTile(
                    icon: Icons.add_circle_outline,
                    label: 'Nouvelle vidéo',
                    highlight: true,
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => CreatorNewVideoScreen(onCreated: _reload)),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              const SectionHeading(title: 'Mes vidéos'),
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
                            StatusPill(value: video.status.value, label: video.status.label),
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
            ),
          );
        },
        ),
      ),
    );
  }
}

class _ActionTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool highlight;

  const _ActionTile({required this.icon, required this.label, required this.onTap, this.highlight = false});

  @override
  Widget build(BuildContext context) {
    final primary = Theme.of(context).colorScheme.primary;

    return Card(
      margin: EdgeInsets.zero,
      color: highlight ? primary : null,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: highlight ? Colors.white : primary),
              const SizedBox(height: 8),
              Text(
                label,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: highlight ? Colors.white : null,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Mirrors the redesigned auth gate on apps/web/src/app/creer/layout.tsx —
/// a card (not bare centered text) with the same hook headline and a CTA
/// button instead of a plain link, for whoever lands on this screen without
/// a creator account yet.
class _CreatorGateCard extends StatelessWidget {
  final String subtitle;
  final List<Widget> children;

  const _CreatorGateCard({required this.subtitle, required this.children});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 360),
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [AppColors.orange500, AppColors.accent600],
                      ),
                      borderRadius: BorderRadius.circular(40 * 0.28),
                    ),
                    child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 24),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Tes vidéos tournent déjà partout. Pourquoi tu ne gagnes rien ?',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    subtitle,
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.grey.shade600),
                  ),
                  const SizedBox(height: 20),
                  ...children,
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
