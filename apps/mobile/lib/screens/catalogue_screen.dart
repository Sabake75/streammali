import 'package:flutter/material.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../widgets/video_card.dart';
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

  @override
  void initState() {
    super.initState();
    _future = _apiClient.fetchVideos();
  }

  void _reload() {
    setState(() {
      _future = _apiClient.fetchVideos(category: _category, search: _search, page: _page);
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
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
                          ...videoCategories.map(
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
