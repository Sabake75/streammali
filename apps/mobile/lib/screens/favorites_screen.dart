import 'package:flutter/material.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/video_card.dart';
import 'video_detail_screen.dart';

/// "Mes favoris" — mirrors apps/web/src/app/favoris/page.tsx. The toggle
/// endpoint and button already existed (video detail screen); this list was
/// the missing half, same gap "Mes achats" had before it got one.
class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  final ApiClient _apiClient = ApiClient();
  late Future<PaginatedResponse<Video>> _future;
  List<Video>? _videos;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<PaginatedResponse<Video>> _load() {
    final token = AuthController.instance.token;
    if (token == null) throw ApiException('Vous devez être connecté.');
    return _apiClient.fetchMyFavorites(token);
  }

  Future<void> _remove(Video video) async {
    final token = AuthController.instance.token;
    if (token == null) return;

    setState(() => _videos = _videos?.where((v) => v.id != video.id).toList());
    try {
      await _apiClient.toggleFavorite(videoId: video.id, token: token);
    } catch (_) {
      // Best-effort — worst case the video reappears next time this screen loads.
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes favoris')),
      body: FutureBuilder<PaginatedResponse<Video>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text('Erreur : ${snapshot.error}', textAlign: TextAlign.center),
              ),
            );
          }

          _videos ??= snapshot.data!.data;
          final videos = _videos!;

          if (videos.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text('Aucun favori pour l\'instant.', textAlign: TextAlign.center),
              ),
            );
          }

          return GridView.builder(
            padding: const EdgeInsets.all(12),
            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
              maxCrossAxisExtent: 280,
              mainAxisExtent: 260,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemCount: videos.length,
            itemBuilder: (context, index) {
              final video = videos[index];
              return Stack(
                children: [
                  VideoCard(
                    video: video,
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => VideoDetailScreen(videoId: video.id)),
                      );
                    },
                  ),
                  Positioned(
                    top: 6,
                    left: 6,
                    child: Material(
                      color: Colors.white.withValues(alpha: 0.9),
                      shape: const CircleBorder(),
                      child: IconButton(
                        icon: const Icon(Icons.favorite, color: Colors.red, size: 20),
                        tooltip: 'Retirer des favoris',
                        onPressed: () => _remove(video),
                      ),
                    ),
                  ),
                ],
              );
            },
          );
        },
      ),
    );
  }
}
