import 'package:flutter/material.dart';

import '../screens/register_creator_screen.dart';
import '../services/auth_controller.dart';
import '../theme.dart';

/// Mirrors apps/web/src/components/BecomeCreatorBanner.tsx — viewers only,
/// hidden for guests/creators/moderators alike.
class BecomeCreatorBanner extends StatelessWidget {
  const BecomeCreatorBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: AuthController.instance,
      builder: (context, _) {
        final user = AuthController.instance.user;
        if (user == null || user.role != 'viewer') return const SizedBox.shrink();

        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: AppColors.orange50,
            border: Border.all(color: AppColors.orange100),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              Expanded(
                child: RichText(
                  text: TextSpan(
                    style: TextStyle(fontSize: 13.5, color: Colors.grey.shade800),
                    children: const [
                      TextSpan(text: 'Tu fais aussi des vidéos ? '),
                      TextSpan(
                        text: 'Sois payé à chaque vue.',
                        style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.neutral900),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (context) => const RegisterCreatorScreen()),
                  );
                },
                child: const Text('Devenir créateur'),
              ),
            ],
          ),
        );
      },
    );
  }
}
