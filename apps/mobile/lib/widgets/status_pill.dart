import 'package:flutter/material.dart';

/// Shared badge coloring for the small set of status values shown across the
/// creator area (payment status: pending/succeeded/failed; payout status:
/// pending/paid/rejected; video moderation status: pending/approved/
/// rejected) — "pending" means the same thing in all three, the rest don't
/// overlap, so one widget covers all of them. Mirrors the web equivalents
/// (statusBadgeClass in lib/format.ts, StatusBadge in CreatorPageClient.tsx)
/// for visual parity between the two apps.
class StatusPill extends StatelessWidget {
  final String value;
  final String label;

  const StatusPill({super.key, required this.value, required this.label});

  static const Map<String, Color> _backgrounds = {
    'succeeded': Color(0xFFDCFCE7),
    'paid': Color(0xFFDCFCE7),
    'approved': Color(0xFFDCFCE7),
    'pending': Color(0xFFFEF3C7),
    'failed': Color(0xFFFEE2E2),
    'rejected': Color(0xFFFEE2E2),
  };

  static const Map<String, Color> _foregrounds = {
    'succeeded': Color(0xFF166534),
    'paid': Color(0xFF166534),
    'approved': Color(0xFF166534),
    'pending': Color(0xFF92400E),
    'failed': Color(0xFF991B1B),
    'rejected': Color(0xFF991B1B),
  };

  @override
  Widget build(BuildContext context) {
    final background = _backgrounds[value] ?? Colors.grey.shade200;
    final foreground = _foregrounds[value] ?? Colors.grey.shade800;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(999)),
      child: Text(label, style: TextStyle(color: foreground, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}
