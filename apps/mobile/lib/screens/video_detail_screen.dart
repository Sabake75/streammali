import 'package:flutter/material.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../theme.dart';
import '../utils/formatting.dart';
import '../widgets/favorite_button.dart';
import '../widgets/purchase_section.dart';
import '../widgets/report_section.dart';
import '../widgets/review_section.dart';
import '../widgets/video_card.dart';
import '../widgets/video_player_widget.dart';

class VideoDetailScreen extends StatefulWidget {
  final int videoId;

  const VideoDetailScreen({super.key, required this.videoId});

  @override
  State<VideoDetailScreen> createState() => _VideoDetailScreenState();
}

class _VideoDetailScreenState extends State<VideoDetailScreen> with WidgetsBindingObserver {
  final ApiClient _apiClient = ApiClient();
  late Future<Video?> _future;
  Future<PaginatedResponse<Video>>? _similarFuture;
  Video? _lastLoadedVideo;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _loadVideo();
    _apiClient.recordVideoView(widget.videoId);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  void _loadVideo() {
    _future = _apiClient.fetchVideo(widget.videoId).then((video) {
      _lastLoadedVideo = video;
      if (video != null) {
        _similarFuture = _apiClient.fetchVideos(category: video.category.value);
      }
      return video;
    });
  }

  // Mobile Money payment happens outside the app (external browser/app) —
  // when the user comes back, re-check purchase status so a successful
  // payment unlocks the video without needing to leave and re-enter this
  // screen manually.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && _lastLoadedVideo?.purchased != true) {
      setState(_loadVideo);
    }
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
                FavoriteButton(videoId: video.id, initialFavorited: video.favorited ?? false),
                const SizedBox(height: 8),
                ReportSection(videoId: video.id),
                const SizedBox(height: 20),
                ReviewSection(videoId: video.id, purchased: video.purchased ?? false),
                const SizedBox(height: 20),
                if (_similarFuture != null)
                  FutureBuilder<PaginatedResponse<Video>>(
                    future: _similarFuture,
                    builder: (context, similarSnapshot) {
                      final similar = similarSnapshot.data?.data
                          .where((v) => v.id != video.id)
                          .take(6)
                          .toList();

                      if (similar == null || similar.isEmpty) return const SizedBox.shrink();

                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SectionHeading(title: 'Vidéos similaires'),
                          const SizedBox(height: 8),
                          SizedBox(
                            height: 275,
                            child: ListView.separated(
                              scrollDirection: Axis.horizontal,
                              itemCount: similar.length,
                              separatorBuilder: (context, index) => const SizedBox(width: 12),
                              itemBuilder: (context, index) {
                                final similarVideo = similar[index];
                                return SizedBox(
                                  width: 160,
                                  child: VideoCard(
                                    video: similarVideo,
                                    onTap: () {
                                      Navigator.of(context).pushReplacement(
                                        MaterialPageRoute(
                                          builder: (context) =>
                                              VideoDetailScreen(videoId: similarVideo.id),
                                        ),
                                      );
                                    },
                                  ),
                                );
                              },
                            ),
                          ),
                        ],
                      );
                    },
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}
