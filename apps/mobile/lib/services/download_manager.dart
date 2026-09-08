import 'dart:convert';
import 'dart:io';

import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_client.dart';

/// Offline downloads for purchased videos — mobile-only, no web equivalent
/// (see App\Http\Controllers\Api\VideoDownloadController for why). Files
/// live in the app's own sandboxed support directory: invisible to other
/// apps, not scanned into the phone's shared media gallery, gone if the app
/// is uninstalled — never the plain Downloads folder a browser would use.
/// The index mapping video id to local path is device-local only (no
/// server sync): a download is inherently per-device.
class DownloadManager {
  DownloadManager._();

  static final DownloadManager instance = DownloadManager._();

  static const _prefsKey = 'offline_downloads';

  final ApiClient _apiClient = ApiClient();
  Map<int, String>? _index;

  Future<Map<int, String>> _loadIndex() async {
    final cached = _index;
    if (cached != null) return cached;

    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_prefsKey);
    if (raw == null) return _index = {};

    final decoded = jsonDecode(raw) as Map<String, dynamic>;
    return _index = decoded.map((key, value) => MapEntry(int.parse(key), value as String));
  }

  Future<void> _saveIndex(Map<int, String> index) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      _prefsKey,
      jsonEncode(index.map((key, value) => MapEntry(key.toString(), value))),
    );
  }

  /// Null if never downloaded, or if the file has since disappeared (the OS
  /// can reclaim app storage under pressure) — self-heals the index either way.
  Future<String?> localPathFor(int videoId) async {
    final index = await _loadIndex();
    final path = index[videoId];
    if (path == null) return null;

    if (!await File(path).exists()) {
      index.remove(videoId);
      await _saveIndex(index);
      return null;
    }

    return path;
  }

  Future<bool> isDownloaded(int videoId) async => (await localPathFor(videoId)) != null;

  /// Polls until Cloudflare has finished preparing the MP4 (can take a
  /// short while on the very first request for a given video), then
  /// streams it to local storage. Throws ApiException if it never becomes
  /// ready or the transfer fails.
  Future<void> download({
    required int videoId,
    required String token,
    required void Function(double percent) onProgress,
  }) async {
    String? url;

    for (var attempt = 0; attempt < 20; attempt++) {
      final result = await _apiClient.requestVideoDownload(videoId: videoId, token: token);
      if (result.ready) {
        url = result.url;
        break;
      }
      await Future.delayed(const Duration(seconds: 3));
    }

    if (url == null) {
      throw ApiException('Le fichier n\'est pas encore prêt — réessaie dans un instant.');
    }

    final dir = await getApplicationSupportDirectory();
    final path = '${dir.path}/video_$videoId.mp4';
    await _apiClient.downloadFile(url: url, savePath: path, onProgress: onProgress);

    final index = await _loadIndex();
    index[videoId] = path;
    await _saveIndex(index);
  }

  Future<void> delete(int videoId) async {
    final index = await _loadIndex();
    final path = index[videoId];
    if (path == null) return;

    final file = File(path);
    if (await file.exists()) await file.delete();

    index.remove(videoId);
    await _saveIndex(index);
  }
}
