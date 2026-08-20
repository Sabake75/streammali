import 'package:flutter/material.dart';

import '../models/video.dart';
import '../utils/formatting.dart';

class VideoCard extends StatelessWidget {
  final Video video;
  final VoidCallback onTap;

  const VideoCard({super.key, required this.video, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Container(
                color: Theme.of(context).colorScheme.surfaceContainerHighest,
                child: video.posterPath != null
                    ? Image.network(
                        video.posterPath!,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => const _PosterPlaceholder(),
                      )
                    : const _PosterPlaceholder(),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Chip(
                    label: Text(video.category.label, style: const TextStyle(fontSize: 12)),
                    visualDensity: VisualDensity.compact,
                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    video.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${video.creator.name} · ${formatDuration(video.durationSeconds)}',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    formatPrice(video.price),
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PosterPlaceholder extends StatelessWidget {
  const _PosterPlaceholder();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Text('Pas de jaquette', style: TextStyle(color: Colors.grey)),
    );
  }
}
