import 'package:flutter/material.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../theme.dart';
import '../widgets/app_logo.dart';
import '../widgets/hero_banner.dart';
import '../widgets/notification_bell.dart';
import '../widgets/onboarding_dialog.dart';
import '../widgets/video_card.dart';
import 'account_screen.dart';
import 'creator_screen.dart';
import 'favorites_screen.dart';
import 'library_screen.dart';
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
  String _sort = 'recent';
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
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) showOnboardingIfNeeded(context);
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    AuthController.instance.removeListener(_maybeLoadRecommended);
    super.dispose();
  }

  void _reload() {
    setState(() {
      _future = _apiClient.fetchVideos(category: _category, search: _search, page: _page, sort: _sort);
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
        title: const AppLogo(),
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
                  TextButton(
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => const AccountScreen()),
                      );
                    },
                    child: Text(user.name),
                  ),
                  const NotificationBell(),
                  IconButton(
                    icon: const Icon(Icons.favorite_border),
                    tooltip: 'Mes favoris',
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => const FavoritesScreen()),
                      );
                    },
                  ),
                  IconButton(
                    icon: const Icon(Icons.video_library_outlined),
                    tooltip: 'Mes achats',
                    onPressed: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => const LibraryScreen()),
                      );
                    },
                  ),
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
          const Padding(
            padding: EdgeInsets.fromLTRB(12, 12, 12, 0),
            child: HeroBanner(),
          ),
          if (_featured != null && _featured!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 20, 12, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SectionHeading(title: 'En vedette'),
                  const SizedBox(height: 8),
                  SizedBox(
                    height: 275,
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
            padding: const EdgeInsets.fromLTRB(12, 20, 12, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SectionHeading(title: 'Catalogue'),
                const SizedBox(height: 12),
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        TextField(
                          controller: _searchController,
                          decoration: const InputDecoration(
                            labelText: 'Recherche',
                            hintText: "Titre d'un film, clip, sketch…",
                            prefixIcon: Icon(Icons.search),
                          ),
                          onSubmitted: (value) {
                            _search = value;
                            _page = 1;
                            _reload();
                          },
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<String?>(
                                initialValue: _category,
                                decoration: const InputDecoration(labelText: 'Catégorie'),
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
                            const SizedBox(width: 10),
                            Expanded(
                              child: DropdownButtonFormField<String>(
                                initialValue: _sort,
                                decoration: const InputDecoration(labelText: 'Trier par'),
                                items: const [
                                  DropdownMenuItem(value: 'recent', child: Text('Plus récent')),
                                  DropdownMenuItem(value: 'popular', child: Text('Plus populaire')),
                                ],
                                onChanged: (value) {
                                  _sort = value ?? 'recent';
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
                ),
              ],
            ),
          ),
          if (_recommended != null && _recommended!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 20, 12, 0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SectionHeading(title: 'Recommandé pour vous'),
                  const SizedBox(height: 8),
                  SizedBox(
                    height: 275,
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
                            child: Icon(Icons.search_off, color: Colors.grey.shade400, size: 28),
                          ),
                          const SizedBox(height: 12),
                          const Text('Aucune vidéo ne correspond à ces critères.', textAlign: TextAlign.center),
                          if (_search.isNotEmpty || _category != null) ...[
                            const SizedBox(height: 8),
                            TextButton(
                              onPressed: () {
                                _searchController.clear();
                                setState(() {
                                  _search = '';
                                  _category = null;
                                  _page = 1;
                                });
                                _reload();
                              },
                              child: const Text('Réinitialiser les filtres'),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
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
