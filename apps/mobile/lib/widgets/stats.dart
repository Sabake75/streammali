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
            Text('Statistiques', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
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
      else
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
      if (stats.videos.isNotEmpty) ...[
        const SizedBox(height: 16),
        Text('Par vidéo', style: Theme.of(context).textTheme.bodySmall),
        const SizedBox(height: 4),
        ...stats.videos.map(
          (video) => Padding(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Row(
              children: [
                Expanded(flex: 3, child: Text(video.title, overflow: TextOverflow.ellipsis)),
                Expanded(child: Text('${video.viewsCount} vues')),
                Expanded(child: Text('${video.purchasesCount} achats')),
                Expanded(child: Text(formatPrice(video.revenue))),
              ],
            ),
          ),
        ),
      ],
    ];
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
