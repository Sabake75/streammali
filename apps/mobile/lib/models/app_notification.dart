/// The `data` payload shape differs by `type` — kept as a raw map rather
/// than a sealed class hierarchy, since there are only two shapes and both
/// are read by a single switch in NotificationsScreen (mirrors
/// apps/web/src/lib/types.ts's tagged-union AppNotification).
class AppNotification {
  final String id;
  final Map<String, dynamic> data;
  final bool read;
  final DateTime createdAt;

  const AppNotification({required this.id, required this.data, required this.read, required this.createdAt});

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] as String,
      data: json['data'] as Map<String, dynamic>,
      read: json['read'] as bool,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}

class NotificationListResult {
  final List<AppNotification> data;
  final int unreadCount;

  const NotificationListResult({required this.data, required this.unreadCount});
}
