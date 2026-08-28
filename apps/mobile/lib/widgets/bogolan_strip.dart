import 'package:flutter/material.dart';

import '../theme.dart';

/// Thin repeating triangle band inspired by bogolan (Malian mud-cloth)
/// motifs — mirrors web's BogolanStrip.tsx. Used once, as a subtle nod to
/// local textile patterns rather than a generic template look.
class BogolanStrip extends StatelessWidget {
  const BogolanStrip({super.key});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 12,
      width: double.infinity,
      child: CustomPaint(painter: _BogolanPainter()),
    );
  }
}

class _BogolanPainter extends CustomPainter {
  static const double _tileWidth = 16;

  @override
  void paint(Canvas canvas, Size size) {
    final orangePaint = Paint()..color = AppColors.orange600.withValues(alpha: 0.85);
    final emeraldPaint = Paint()..color = AppColors.emerald600;

    final tileCount = (size.width / _tileWidth).ceil();
    for (var i = 0; i < tileCount; i++) {
      final x = i * _tileWidth;
      canvas.drawPath(
        Path()
          ..moveTo(x, size.height)
          ..lineTo(x + _tileWidth / 2, 0)
          ..lineTo(x + _tileWidth, size.height)
          ..close(),
        orangePaint,
      );
      canvas.drawPath(
        Path()
          ..moveTo(x + _tileWidth * 0.25, size.height)
          ..lineTo(x + _tileWidth / 2, size.height * 0.33)
          ..lineTo(x + _tileWidth * 0.75, size.height)
          ..close(),
        emeraldPaint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
