import 'package:flutter/material.dart';

import '../theme.dart';

/// Condensed mobile version of the web catalogue's hero section (same
/// copy and gradient — apps/web/src/app/page.tsx) so the two apps open on
/// the same pitch instead of the Flutter default of a bare title bar.
class HeroBanner extends StatelessWidget {
  const HeroBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.orange700, AppColors.orange600, AppColors.orange500],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Le cinéma malien, à portée de Mobile Money.',
            style: TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.w800,
              height: 1.2,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Films, clips et sketchs de créateurs maliens, 100 FCFA la vidéo. '
            'Paiement Orange Money, accès immédiat.',
            style: TextStyle(color: Colors.white.withValues(alpha: 0.92), fontSize: 14, height: 1.4),
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: const [
              _HeroPill(icon: Icons.account_balance_wallet_outlined, label: '100 FCFA la vidéo'),
              _HeroPill(icon: Icons.smartphone, label: 'Orange Money'),
              _HeroPill(icon: Icons.movie_creation_outlined, label: 'Créateurs maliens'),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeroPill extends StatelessWidget {
  final IconData icon;
  final String label;

  const _HeroPill({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white, size: 15),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(color: Colors.white, fontSize: 12.5, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
