import 'package:flutter/material.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../widgets/video_card.dart';
import 'video_detail_screen.dart';

/// "Toutes les vidéos de ce créateur" — a lightweight filtered grid, kept
/// separate from CatalogueScreen (the home screen) rather than reusing it
/// with a creator filter: this doesn't need a hero banner, "En vedette", or
/// "Recommandé pour vous", just this one creator's catalogue.
class CreatorVideosScreen extends StatefulWidget {
  final int creatorId;
  final String creatorName;

  const CreatorVideosScreen({super.key, required this.creatorId, required this.creatorName});

  @override
  State<CreatorVideosScreen> createState() => _CreatorVideosScreenState();
}

class _CreatorVideosScreenState extends State<CreatorVideosScreen> {
  final ApiClient _apiClient = ApiClient();
  late Future<PaginatedResponse<Video>> _future;

  @override
  void initState() {
    super.initState();
    _future = _apiClient.fetchVideos(creatorId: widget.creatorId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.creatorName)),
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

          final videos = snapshot.data!.data;

          if (videos.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text('Aucune vidéo pour l\'instant.', textAlign: TextAlign.center),
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
              return VideoCard(
                video: video,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (context) => VideoDetailScreen(videoId: video.id)),
                  );
                },
              );
            },
          );
        },
      ),
    );
  }
}
