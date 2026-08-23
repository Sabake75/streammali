import 'package:flutter/material.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/video_card.dart';
import 'creator_screen.dart';
import 'login_screen.dart';
import 'video_detail_screen.dart';

class CatalogueScreen extends StatefulWidget {
  const CatalogueScreen({super.key});

  @override
  State<CatalogueScreen> createState() => _CatalogueScreenState();
}

class _CatalogueScreenState extends State<CatalogueScreen> {
  final ApiClient _apiClient = ApiClient();
  final TextEditingController _searchController = TextEditingController();

  String? _category;
  String _search = '';
  int _page = 1;
  late Future<PaginatedResponse<Video>> _future;
  List<VideoCategory> _categories = [];
  List<Video>? _recommended;
  bool _recommendedRequested = false;
  List<Video>? _featured;

  @override
  void initState() {
    super.initState();
    _future = _apiClient.fetchVideos();
    _apiClient.fetchCategories().then((categories) {
      if (mounted) setState(() => _categories = categories);
    }).catchError((_) {});
    _apiClient.fetchFeaturedVideos().then((videos) {
      if (mounted) setState(() => _featured = videos);
    }).catchError((_) {});
    AuthController.instance.addListener(_maybeLoadRecommended);
    _maybeLoadRecommended();
  }

  @override
  void dispose() {
    _searchController.dispose();
    AuthController.instance.removeListener(_maybeLoadRecommended);
    super.dispose();
  }

  void _reload() {
    setState(() {
      _future = _apiClient.fetchVideos(category: _category, search: _search, page: _page);
    });
  }

  void _maybeLoadRecommended() {
    if (_recommendedRequested || !AuthController.instance.isAuthenticated) return;
    _recommendedRequested = true;

    _apiClient.fetchRecommendedVideos().then((videos) {
      if (mounted) setState(() => _recommended = videos);
    }).catchError((_) {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('StreamMali'),
        actions: [
          ListenableBuilder(
            listenable: AuthController.instance,
            builder: (context, _) {
              final user = AuthController.instance.user;

              if (user == null) {
                return TextButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => const LoginScreen()),
                    );
                  },
                  child: const Text('Connexion'),
                );
              }

              return Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(user.name),
                  IconButton(
                    icon: const Icon(Icons.video_call_outlined),
                    tooltip: 'Espace créateur',
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => const CreatorScreen()),
                      );
                    },
                  ),
                  IconButton(
                    icon: const Icon(Icons.logout),
                    tooltip: 'Déconnexion',
                    onPressed: () async {
                      final token = AuthController.instance.token;
                      if (token != null) {
                        await _apiClient.logout(token);
                      }
                      await AuthController.instance.clearSession();
                    },
                  ),
                ],
              );
            },
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Column(
        children: [
          if (_featured != null && _featured!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('En vedette', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  SizedBox(
                    height: 220,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: _featured!.length,
                      separatorBuilder: (context, index) => const SizedBox(width: 12),
                      itemBuilder: (context, index) {
                        final video = _featured![index];
                        return SizedBox(
                          width: 160,
                          child: VideoCard(
                            video: video,
                            onTap: () {
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (context) => VideoDetailScreen(videoId: video.id),
                                ),
                              );
                            },
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  decoration: const InputDecoration(
                    labelText: 'Recherche',
                    hintText: "Titre d'un film, clip, sketch…",
                    prefixIcon: Icon(Icons.search),
                    border: OutlineInputBorder(),
                  ),
                  onSubmitted: (value) {
                    _search = value;
                    _page = 1;
                    _reload();
                  },
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: DropdownButtonFormField<String?>(
                        initialValue: _category,
                        decoration: const InputDecoration(
                          labelText: 'Catégorie',
                          border: OutlineInputBorder(),
                        ),
                        items: [
                          const DropdownMenuItem(value: null, child: Text('Toutes')),
                          ..._categories.map(
                            (category) => DropdownMenuItem(
                              value: category.value,
                              child: Text(category.label),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          _category = value;
                          _page = 1;
                          _reload();
                        },
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          if (_recommended != null && _recommended!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Recommandé pour vous', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  SizedBox(
                    height: 220,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: _recommended!.length,
                      separatorBuilder: (context, index) => const SizedBox(width: 12),
                      itemBuilder: (context, index) {
                        final video = _recommended![index];
                        return SizedBox(
                          width: 160,
                          child: VideoCard(
                            video: video,
                            onTap: () {
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (context) => VideoDetailScreen(videoId: video.id),
                                ),
                              );
                            },
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          Expanded(
            child: FutureBuilder<PaginatedResponse<Video>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (snapshot.hasError) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text(
                        'Erreur : ${snapshot.error}',
                        textAlign: TextAlign.center,
                      ),
                    ),
                  );
                }

                final catalogue = snapshot.data!;

                if (catalogue.data.isEmpty) {
                  return const Center(child: Text('Aucune vidéo ne correspond à ces critères.'));
                }

                return Column(
                  children: [
                    Expanded(
                      child: GridView.builder(
                        padding: const EdgeInsets.all(12),
                        gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                          maxCrossAxisExtent: 280,
                          mainAxisExtent: 260,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                        ),
                        itemCount: catalogue.data.length,
                        itemBuilder: (context, index) {
                          final video = catalogue.data[index];
                          return VideoCard(
                            video: video,
                            onTap: () {
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (context) => VideoDetailScreen(videoId: video.id),
                                ),
                              );
                            },
                          );
                        },
                      ),
                    ),
                    if (catalogue.lastPage > 1)
                      Padding(
                        padding: const EdgeInsets.all(8),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            TextButton(
                              onPressed: catalogue.currentPage > 1
                                  ? () {
                                      _page = catalogue.currentPage - 1;
                                      _reload();
                                    }
                                  : null,
                              child: const Text('← Précédent'),
                            ),
                            const SizedBox(width: 16),
                            Text('Page ${catalogue.currentPage} / ${catalogue.lastPage}'),
                            const SizedBox(width: 16),
                            TextButton(
                              onPressed: catalogue.currentPage < catalogue.lastPage
                                  ? () {
                                      _page = catalogue.currentPage + 1;
                                      _reload();
                                    }
                                  : null,
                              child: const Text('Suivant →'),
                            ),
                          ],
                        ),
                      ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
