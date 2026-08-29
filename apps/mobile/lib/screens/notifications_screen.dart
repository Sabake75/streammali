import 'package:flutter/material.dart';

import '../models/app_notification.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../theme.dart';
import '../utils/formatting.dart';
import 'creator_screen.dart';
import 'video_detail_screen.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final ApiClient _apiClient = ApiClient();
  late Future<NotificationListResult> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<NotificationListResult> _load() {
    final token = AuthController.instance.token;
    if (token == null) throw ApiException('Vous devez être connecté.');
    return _apiClient.fetchNotifications(token);
  }

  Future<void> _markRead(AppNotification notification) async {
    final token = AuthController.instance.token;
    if (token == null || notification.read) return;
    await _apiClient.markNotificationRead(id: notification.id, token: token).catchError((_) {});
  }

  Future<void> _markAllRead() async {
    final token = AuthController.instance.token;
    if (token == null) return;
    await _apiClient.markAllNotificationsRead(token).catchError((_) {});
    setState(() => _future = _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(onPressed: _markAllRead, child: const Text('Tout marquer comme lu')),
        ],
      ),
      body: FutureBuilder<NotificationListResult>(
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

          final notifications = snapshot.data!.data;

          if (notifications.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text('Aucune notification pour l\'instant.', textAlign: TextAlign.center),
              ),
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: notifications.length,
            separatorBuilder: (context, index) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final notification = notifications[index];
              return _NotificationTile(
                notification: notification,
                onTap: () async {
                  await _markRead(notification);
                  if (!context.mounted) return;

                  final type = notification.data['type'] as String?;
                  if (type == 'video_status_changed') {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (context) => VideoDetailScreen(videoId: notification.data['video_id'] as int),
                      ),
                    );
                  } else {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => const CreatorScreen()),
                    );
                  }
                },
              );
            },
          );
        },
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  final AppNotification notification;
  final VoidCallback onTap;

  const _NotificationTile({required this.notification, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final data = notification.data;
    final type = data['type'] as String?;

    final String text;
    if (type == 'video_status_changed') {
      final title = data['video_title'] as String;
      final status = data['status'] as String;
      final reason = data['rejection_reason'] as String?;
      text = status == 'approved'
          ? 'Ta vidéo « $title » a été validée et est en ligne.'
          : 'Ta vidéo « $title » a été refusée${reason != null ? " : $reason" : "."}';
    } else {
      text = 'Nouveau message de la modération : « ${data['excerpt']} »';
    }

    return Card(
      margin: EdgeInsets.zero,
      color: notification.read ? null : AppColors.orange50,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(text, style: Theme.of(context).textTheme.bodyMedium),
                    const SizedBox(height: 4),
                    Text(
                      formatDate(notification.createdAt),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
              if (!notification.read)
                Container(
                  margin: const EdgeInsets.only(left: 8, top: 4),
                  width: 8,
                  height: 8,
                  decoration: const BoxDecoration(color: AppColors.orange600, shape: BoxShape.circle),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
