import 'package:flutter/material.dart';

/// Motif géométrique inspiré du bogolan (tissu malien teint à la boue
/// fermentée) : chevrons, losange, points — même motif que le web
/// (apps/web/src/components/HeroPattern.tsx), peint ici via CustomPainter
/// plutôt qu'un asset SVG pour éviter une dépendance supplémentaire
/// (flutter_svg n'est pas dans pubspec.yaml).
class BogolanPattern extends StatelessWidget {
  const BogolanPattern({super.key});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(painter: _BogolanPainter(), size: Size.infinite);
  }
}

class _BogolanPainter extends CustomPainter {
  static const _tile = 80.0;
  static const _base = Color(0xFF3E1E09);
  static const _chevron = Color(0xFF5C2E0A);
  static const _diamond = Color(0xFF7A3F0B);
  static const _dot = Color(0xFF95520F);

  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawRect(Offset.zero & size, Paint()..color = _base);

    final chevronPaint = Paint()
      ..color = _chevron
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    final diamondPaint = Paint()..color = _diamond;
    final dotPaint = Paint()..color = _dot;

    for (double y = -_tile; y < size.height + _tile; y += _tile) {
      for (double x = -_tile; x < size.width + _tile; x += _tile) {
        _paintTile(canvas, Offset(x, y), chevronPaint, diamondPaint, dotPaint);
      }
    }
  }

  void _paintTile(Canvas canvas, Offset origin, Paint chevron, Paint diamond, Paint dot) {
    Path chevronPath(double baseY) => Path()
      ..moveTo(origin.dx, origin.dy + baseY)
      ..lineTo(origin.dx + 20, origin.dy + baseY - 20)
      ..lineTo(origin.dx + 40, origin.dy + baseY)
      ..lineTo(origin.dx + 60, origin.dy + baseY - 20)
      ..lineTo(origin.dx + 80, origin.dy + baseY);

    canvas.drawPath(chevronPath(20), chevron);
    canvas.drawPath(chevronPath(60), chevron);

    canvas.save();
    canvas.translate(origin.dx + 40, origin.dy + 40);
    canvas.rotate(0.7853981633974483); // 45°, matches the web SVG's rotate(45 40 40)
    canvas.drawRect(const Rect.fromLTWH(-4.5, -4.5, 9, 9), diamond);
    canvas.restore();

    canvas.drawCircle(Offset(origin.dx + 10, origin.dy + 70), 2.5, dot);
    canvas.drawCircle(Offset(origin.dx + 70, origin.dy + 10), 2.5, dot);
    canvas.drawCircle(Offset(origin.dx + 70, origin.dy + 70), 2.5, dot);
    canvas.drawCircle(Offset(origin.dx + 10, origin.dy + 10), 2.5, dot);
  }

  @override
  bool shouldRepaint(covariant _BogolanPainter oldDelegate) => false;
}
