import 'package:flutter/material.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/video_card.dart';
import 'video_detail_screen.dart';

/// "Mes achats" — every video the current user has successfully bought,
/// mirrors apps/web/src/app/bibliotheque/page.tsx.
class LibraryScreen extends StatefulWidget {
  const LibraryScreen({super.key});

  @override
  State<LibraryScreen> createState() => _LibraryScreenState();
}

class _LibraryScreenState extends State<LibraryScreen> {
  final ApiClient _apiClient = ApiClient();
  late Future<PaginatedResponse<Video>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<PaginatedResponse<Video>> _load() {
    final token = AuthController.instance.token;
    if (token == null) throw ApiException('Vous devez être connecté.');
    return _apiClient.fetchMyPurchases(token);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes achats')),
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
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 56,
                      height: 56,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(Icons.video_library_outlined, color: Colors.grey.shade400, size: 28),
                    ),
                    const SizedBox(height: 12),
                    const Text('Aucun achat pour l\'instant.', textAlign: TextAlign.center),
                    const SizedBox(height: 8),
                    TextButton(
                      onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst),
                      child: const Text('Parcourir le catalogue'),
                    ),
                  ],
                ),
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
