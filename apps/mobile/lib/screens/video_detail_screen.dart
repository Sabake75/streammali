import 'package:flutter/material.dart';

import '../models/video.dart';
import '../services/api_client.dart';
import '../utils/formatting.dart';
import '../widgets/purchase_section.dart';
import '../widgets/report_section.dart';
import '../widgets/review_section.dart';
import '../widgets/video_player_widget.dart';

class VideoDetailScreen extends StatefulWidget {
  final int videoId;

  const VideoDetailScreen({super.key, required this.videoId});

  @override
  State<VideoDetailScreen> createState() => _VideoDetailScreenState();
}

class _VideoDetailScreenState extends State<VideoDetailScreen> {
  final ApiClient _apiClient = ApiClient();
  late Future<Video?> _future;

  @override
  void initState() {
    super.initState();
    _future = _apiClient.fetchVideo(widget.videoId);
    _apiClient.recordVideoView(widget.videoId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(),
      body: FutureBuilder<Video?>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Erreur : ${snapshot.error}'));
          }

          final video = snapshot.data;
          if (video == null) {
            return const Center(child: Text('Vidéo introuvable.'));
          }

          final canWatchFull = (video.purchased ?? false) && video.playbackUrl != null;
          final canWatchPreview = !canWatchFull && video.previewPlaybackUrl != null;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: canWatchFull
                      ? VideoPlayerWidget(url: video.playbackUrl!)
                      : canWatchPreview
                      ? VideoPlayerWidget(url: video.previewPlaybackUrl!)
                      : AspectRatio(
                          aspectRatio: 16 / 9,
                          child: Container(
                            color: Theme.of(context).colorScheme.surfaceContainerHighest,
                            child: video.posterPath != null
                                ? Image.network(
                                    video.posterPath!,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) =>
                                        const Center(child: Text('Pas de jaquette')),
                                  )
                                : const Center(child: Text('Pas de jaquette')),
                          ),
                        ),
                ),
                if (canWatchPreview) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Aperçu — achète la vidéo pour la voir en entier.',
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.grey),
                  ),
                ],
                const SizedBox(height: 16),
                Chip(label: Text(video.category.label)),
                const SizedBox(height: 8),
                Text(video.title, style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 4),
                Text(
                  video.reviewsCount > 0
                      ? '${video.creator.name} · ${formatDuration(video.durationSeconds)} · ★ ${video.averageRating} (${video.reviewsCount} avis)'
                      : '${video.creator.name} · ${formatDuration(video.durationSeconds)}',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.grey),
                ),
                if (video.description != null && video.description!.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text(video.description!),
                ],
                const SizedBox(height: 20),
                Text(
                  formatPrice(video.price),
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 12),
                PurchaseSection(videoId: video.id),
                const SizedBox(height: 8),
                ReportSection(videoId: video.id),
                const SizedBox(height: 20),
                ReviewSection(videoId: video.id, purchased: video.purchased ?? false),
              ],
            ),
          );
        },
      ),
    );
  }
}
