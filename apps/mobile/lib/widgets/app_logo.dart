import 'package:flutter/material.dart';

import '../theme.dart';

/// Same mark as the web header (apps/web/src/app/layout.tsx): a rounded
/// orange-to-emerald badge with a play glyph, next to "Stream" + orange
/// "Mali". Kept as one widget so every screen's AppBar matches.
class AppLogo extends StatelessWidget {
  final double badgeSize;
  final double fontSize;

  const AppLogo({super.key, this.badgeSize = 32, this.fontSize = 20});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: badgeSize,
          height: badgeSize,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppColors.orange500, AppColors.emerald600],
            ),
            borderRadius: BorderRadius.circular(badgeSize * 0.28),
          ),
          child: Icon(Icons.play_arrow_rounded, color: Colors.white, size: badgeSize * 0.6),
        ),
        const SizedBox(width: 8),
        RichText(
          text: TextSpan(
            style: TextStyle(
              fontSize: fontSize,
              fontWeight: FontWeight.w800,
              color: isDark ? AppColors.darkForeground : AppColors.neutral900,
              letterSpacing: -0.3,
            ),
            children: [
              const TextSpan(text: 'Stream'),
              TextSpan(
                text: 'Mali',
                style: TextStyle(color: isDark ? AppColors.orange400 : AppColors.orange600),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
