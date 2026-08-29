import 'dart:async';

import 'package:flutter/material.dart';

import '../screens/notifications_screen.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';

const _pollInterval = Duration(seconds: 45);

class NotificationBell extends StatefulWidget {
  const NotificationBell({super.key});

  @override
  State<NotificationBell> createState() => _NotificationBellState();
}

class _NotificationBellState extends State<NotificationBell> {
  final ApiClient _apiClient = ApiClient();
  int _unreadCount = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _load();
    _timer = Timer.periodic(_pollInterval, (_) => _load());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    try {
      final result = await _apiClient.fetchNotifications(token);
      if (mounted) setState(() => _unreadCount = result.unreadCount);
    } catch (_) {
      // Best-effort — the badge just doesn't update on failure.
    }
  }

  @override
  Widget build(BuildContext context) {
    return IconButton(
      icon: Badge(
        isLabelVisible: _unreadCount > 0,
        label: Text(_unreadCount > 9 ? '9+' : '$_unreadCount'),
        child: const Icon(Icons.notifications_outlined),
      ),
      tooltip: 'Notifications',
      onPressed: () async {
        await Navigator.of(context).push(
          MaterialPageRoute(builder: (context) => const NotificationsScreen()),
        );
        _load();
      },
    );
  }
}
