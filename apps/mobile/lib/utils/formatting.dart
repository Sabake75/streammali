import 'package:flutter/material.dart';

String formatDuration(int? seconds) {
  if (seconds == null || seconds == 0) return 'Durée inconnue';

  final hours = seconds ~/ 3600;
  final minutes = ((seconds % 3600) / 60).round();

  if (hours == 0 && minutes == 0) return '$seconds s';
  if (hours == 0) return '$minutes min';
  return '$hours h ${minutes.toString().padLeft(2, '0')}';
}

String formatPrice(int price) => '$price FCFA';

/// Categories are moderator-managed (dynamic, not a fixed enum), so colors
/// can't be hardcoded per known value — hashing the category's own value
/// into a fixed palette keeps the same category visually consistent
/// everywhere without needing to know the category list in advance. Mirrors
/// the same idea used on the web (apps/web/src/lib/format.ts).
const _categoryPalette = [
  [Color(0xFFBAE6FD), Color(0xFFF0F9FF)], // sky
  [Color(0xFFDDD6FE), Color(0xFFF5F3FF)], // violet
  [Color(0xFFFECDD3), Color(0xFFFFF1F2)], // rose
  [Color(0xFFA7F3D0), Color(0xFFECFDF5)], // emerald
  [Color(0xFFFDE68A), Color(0xFFFFFBEB)], // amber
  [Color(0xFF99F6E4), Color(0xFFF0FDFA)], // teal
];

List<Color> categoryColors(String value) {
  var hash = 0;
  for (final unit in value.codeUnits) {
    hash = (hash * 31 + unit) & 0x7fffffff;
  }
  return _categoryPalette[hash % _categoryPalette.length];
}
