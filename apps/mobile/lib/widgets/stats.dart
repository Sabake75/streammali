import 'package:flutter/material.dart';

import '../models/creator_stats.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../utils/formatting.dart';

class Stats extends StatefulWidget {
  const Stats({super.key});

  @override
  State<Stats> createState() => _StatsState();
}

class _StatsState extends State<Stats> {
  final ApiClient _apiClient = ApiClient();
  CreatorStats? _stats;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    try {
      final stats = await _apiClient.fetchStats(token);
      if (!mounted) return;
      setState(() => _stats = stats);
    } catch (err) {
      if (!mounted) return;
      setState(() => _error = err.toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
            if (_error == null && _stats == null) const Center(child: CircularProgressIndicator()),
            if (_stats != null) ..._buildContent(context, _stats!),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildContent(BuildContext context, CreatorStats stats) {
    final maxRevenue = stats.timeseries
        .map((point) => point.revenue)
        .fold<int>(1, (max, value) => value > max ? value : max);

    return [
      Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _StatBlock(label: 'Vues', value: '${stats.totals.views}'),
          _StatBlock(label: 'Achats', value: '${stats.totals.purchases}'),
          _StatBlock(label: 'Revenus', value: formatPrice(stats.totals.revenue)),
        ],
      ),
      const SizedBox(height: 16),
      Text('Revenus des 14 derniers jours', style: Theme.of(context).textTheme.bodySmall),
      const SizedBox(height: 8),
      if (stats.timeseries.every((point) => point.revenue == 0))
        SizedBox(
          height: 80,
          child: Center(
            child: Text(
              'Aucune vente sur cette période.',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.grey),
            ),
          ),
        )
      else ...[
        SizedBox(
          height: 80,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: stats.timeseries
                .map(
                  (point) => Expanded(
                    child: Tooltip(
                      message: '${point.date} : ${formatPrice(point.revenue)}',
                      child: Container(
                        margin: const EdgeInsets.symmetric(horizontal: 1),
                        height: 80 * (point.revenue / maxRevenue).clamp(0.05, 1.0),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.primary,
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(2)),
                        ),
                      ),
                    ),
                  ),
                )
                .toList(),
          ),
        ),
        // Bare bars with no reference point are unreadable — at minimum,
        // anchor the two ends of the 14-day window (mirrors the web
        // version, apps/web/src/components/creator/Stats.tsx).
        if (stats.timeseries.isNotEmpty) ...[
          const SizedBox(height: 4),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(_shortDate(stats.timeseries.first.date), style: Theme.of(context).textTheme.bodySmall),
              Text(_shortDate(stats.timeseries.last.date), style: Theme.of(context).textTheme.bodySmall),
            ],
          ),
        ],
      ],
      if (stats.videos.isNotEmpty) ...[
        const SizedBox(height: 20),
        Text('Par vidéo', style: Theme.of(context).textTheme.bodySmall),
        const SizedBox(height: 8),
        Table(
          columnWidths: const {0: FlexColumnWidth(2.2)},
          children: [
            TableRow(
              decoration: BoxDecoration(
                border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              children: [
                _tableHeaderCell(context, 'Titre'),
                _tableHeaderCell(context, 'Vues'),
                _tableHeaderCell(context, 'Achats'),
                _tableHeaderCell(context, 'Revenus'),
              ],
            ),
            for (final video in stats.videos)
              TableRow(
                decoration: BoxDecoration(
                  border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
                ),
                children: [
                  _tableCell(context, video.title, ellipsis: true),
                  _tableCell(context, '${video.viewsCount}'),
                  _tableCell(context, '${video.purchasesCount}'),
                  _tableCell(context, formatPrice(video.revenue)),
                ],
              ),
          ],
        ),
      ],
    ];
  }

  String _shortDate(String isoDate) {
    final parts = isoDate.split('-');
    if (parts.length != 3) return isoDate;
    return '${parts[2]}/${parts[1]}';
  }

  Widget _tableHeaderCell(BuildContext context, String label) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Text(
        label,
        style: Theme.of(context).textTheme.bodySmall?.copyWith(fontWeight: FontWeight.w700),
      ),
    );
  }

  Widget _tableCell(BuildContext context, String value, {bool ellipsis = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Text(
        value,
        maxLines: 1,
        overflow: ellipsis ? TextOverflow.ellipsis : TextOverflow.visible,
      ),
    );
  }
}

class _StatBlock extends StatelessWidget {
  final String label;
  final String value;

  const _StatBlock({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(value, style: Theme.of(context).textTheme.titleLarge),
        Text(label, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.grey)),
      ],
    );
  }
}
