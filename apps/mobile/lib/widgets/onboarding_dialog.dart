import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../theme.dart';

const _seenKey = 'streammali_onboarding_seen';

/// Shown once per install (shared_preferences flag) — explains the
/// pay-per-view + free-preview + Mobile Money model to a first-time user,
/// mirrors apps/web/src/components/OnboardingModal.tsx. Called from
/// CatalogueScreen's initState via a post-frame callback, since showing a
/// dialog needs a BuildContext with a Navigator already mounted.
Future<void> showOnboardingIfNeeded(BuildContext context) async {
  final prefs = await SharedPreferences.getInstance();
  if (prefs.getBool(_seenKey) == true) return;
  if (!context.mounted) return;

  await showDialog<void>(
    context: context,
    builder: (context) => const _OnboardingDialog(),
  );

  await prefs.setBool(_seenKey, true);
}

class _OnboardingDialog extends StatelessWidget {
  const _OnboardingDialog();

  @override
  Widget build(BuildContext context) {
    return Dialog(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Bienvenue sur StreamMali', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 4),
            Text(
              'Le cinéma malien, à ta façon.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 16),
            const _Step(
              icon: Icons.visibility_outlined,
              title: 'Regarde avant d\'acheter',
              text: 'Chaque vidéo a un aperçu gratuit. Tu sais ce que tu achètes avant de payer.',
            ),
            const SizedBox(height: 12),
            const _Step(
              icon: Icons.sell_outlined,
              title: 'Un prix, pas d\'abonnement',
              text: 'Tu payes une fois par vidéo, au prix affiché sur sa fiche — aucun engagement mensuel.',
            ),
            const SizedBox(height: 12),
            const _Step(
              icon: Icons.smartphone_outlined,
              title: 'Mobile Money, accès immédiat',
              text: 'Orange Money, Moov Money… dès le paiement confirmé, la vidéo est débloquée.',
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Compris, je découvre'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Step extends StatelessWidget {
  final IconData icon;
  final String title;
  final String text;

  const _Step({required this.icon, required this.title, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 36,
          height: 36,
          decoration: const BoxDecoration(color: AppColors.orange50, shape: BoxShape.circle),
          child: Icon(icon, size: 18, color: AppColors.orange600),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w700)),
              Text(
                text,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.grey.shade600),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
