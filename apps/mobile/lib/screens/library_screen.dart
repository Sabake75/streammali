import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../models/paginated_response.dart';
import '../models/video.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../utils/formatting.dart';
import '../widgets/error_retry_view.dart';
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

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future.then((_) {}, onError: (_) {});
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes achats')),
      body: SafeArea(
        top: false,
        child: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder<PaginatedResponse<Video>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return ErrorRetryView(onRetry: _reload);
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
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(12),
            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
              maxCrossAxisExtent: 280,
              mainAxisExtent: 296,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
            ),
            itemCount: videos.length,
            itemBuilder: (context, index) {
              final video = videos[index];
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  VideoCard(
                    video: video,
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(builder: (context) => VideoDetailScreen(videoId: video.id)),
                      );
                    },
                  ),
                  if (video.purchase != null) ...[
                    const SizedBox(height: 6),
                    _PurchaseReceipt(purchase: video.purchase!),
                  ],
                ],
              );
            },
          );
        },
        ),
        ),
      ),
    );
  }
}

class _PurchaseReceipt extends StatelessWidget {
  final VideoPurchase purchase;

  const _PurchaseReceipt({required this.purchase});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              'Payé ${formatPrice(purchase.amount)} le ${formatDate(purchase.purchasedAt)}',
              style: Theme.of(context).textTheme.bodySmall,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          Tooltip(
            message: 'Copier la référence',
            child: InkWell(
              onTap: () async {
                await Clipboard.setData(ClipboardData(text: purchase.orderReference));
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Référence copiée'), duration: Duration(seconds: 1)),
                  );
                }
              },
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.copy,
                      size: 14,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      'Réf.',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).colorScheme.primary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
