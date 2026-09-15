import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../models/app_notification.dart';
import '../models/creator_stats.dart';
import '../models/creator_video.dart';
import '../models/message.dart';
import '../models/paginated_response.dart';
import '../models/payout.dart';
import '../models/review.dart';
import '../models/user.dart';
import '../models/video.dart';

class ApiException implements Exception {
  final String message;

  ApiException(this.message);

  @override
  String toString() => message;
}

class AuthResult {
  final String token;
  final StoredUser user;

  const AuthResult({required this.token, required this.user});
}

class PurchaseResult {
  final String paymentUrl;

  const PurchaseResult({required this.paymentUrl});
}

class VideoDownloadResult {
  final bool ready;
  final String? url;

  const VideoDownloadResult({required this.ready, this.url});
}

class ApiClient {
  /// Defaults to the real production API so a plain `flutter build` (CI's
  /// release APK/App Bundle included) ships something that actually works.
  /// For local development against a Laravel dev server, override with
  /// `flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api`
  /// (physical device over `adb reverse`) or `http://10.0.2.2:8000/api` on
  /// the Android emulator, where `localhost` refers to the emulator itself,
  /// not the host machine.
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.streammali.ml/api',
  );

  /// The CGU pages (terms of service) live on the web app, not natively in
  /// this app — same override caveat as [baseUrl] for local development.
  static const String webBaseUrl = String.fromEnvironment(
    'WEB_BASE_URL',
    defaultValue: 'https://streammali.ml',
  );

  Future<PaginatedResponse<Video>> fetchVideos({
    String? category,
    String? search,
    int page = 1,
    int? creatorId,
    String? sort,
  }) async {
    final query = <String, String>{};
    if (category != null && category.isNotEmpty) query['category'] = category;
    if (search != null && search.isNotEmpty) query['search'] = search;
    if (page > 1) query['page'] = page.toString();
    if (creatorId != null) query['creator_id'] = creatorId.toString();
    if (sort != null && sort.isNotEmpty && sort != 'recent') query['sort'] = sort;

    final uri = Uri.parse('$baseUrl/videos').replace(
      queryParameters: query.isEmpty ? null : query,
    );

    final response = await http.get(uri);

    if (response.statusCode != 200) {
      throw ApiException('Impossible de charger le catalogue (${response.statusCode}).');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return PaginatedResponse.fromJson(json, Video.fromJson);
  }

  Future<List<VideoCategory>> fetchCategories() async {
    final response = await http.get(Uri.parse('$baseUrl/categories'));

    if (response.statusCode != 200) {
      throw ApiException('Impossible de charger les catégories (${response.statusCode}).');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return (json['data'] as List)
        .map((entry) => VideoCategory.fromJson(entry as Map<String, dynamic>))
        .toList();
  }

  Future<Video?> fetchVideo(int id) async {
    final response = await http.get(Uri.parse('$baseUrl/videos/$id'));

    if (response.statusCode == 404) return null;
    if (response.statusCode != 200) {
      throw ApiException('Impossible de charger la vidéo (${response.statusCode}).');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return Video.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// Best-effort, never blocks or breaks the screen if it fails. Kept
  /// separate from fetchVideo so a future caching layer there can't
  /// silently swallow view increments (see the web client's RecordView).
  Future<void> recordVideoView(int id) async {
    try {
      await http.post(Uri.parse('$baseUrl/videos/$id/view'));
    } catch (_) {
      // ignore — view tracking is not critical to the page working
    }
  }

  Future<AuthResult> register({
    required String name,
    required String phone,
    required String password,
    required bool termsAccepted,
  }) {
    return _postAuth('/register', {
      'name': name,
      'phone': phone,
      'password': password,
      'terms_accepted': termsAccepted,
    });
  }

  Future<AuthResult> login({required String phone, required String password}) {
    return _postAuth('/login', {'phone': phone, 'password': password});
  }

  Future<void> logout(String token) async {
    await http
        .post(Uri.parse('$baseUrl/logout'), headers: {'Authorization': 'Bearer $token'})
        .catchError((_) => http.Response('', 0));
  }

  /// Raw JSON text — the account screen shows it directly (copy to
  /// clipboard) rather than writing a file, to avoid a file-system/share
  /// plugin dependency for a rarely-used export button.
  Future<String> exportAccountData(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/account/export'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    return response.body;
  }

  Future<void> deleteAccount(String token) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/account'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }
  }

  Future<PurchaseResult> purchaseVideo({
    required int videoId,
    required String payerMsisdn,
    required String token,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/videos/$videoId/purchase'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({'payer_msisdn': payerMsisdn}),
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return PurchaseResult(paymentUrl: json['payment_url'] as String);
  }

  /// Triggers/polls Cloudflare Stream's MP4 download generation for a
  /// purchased video (see App\Http\Controllers\Api\VideoDownloadController)
  /// — `ready: false` is a normal "still preparing" result, not an error.
  Future<VideoDownloadResult> requestVideoDownload({
    required int videoId,
    required String token,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/videos/$videoId/download'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return VideoDownloadResult(ready: json['status'] == 'ready', url: json['url'] as String?);
  }

  /// Streams straight to disk (not buffered in memory) — full videos can be
  /// several hundred MB, same reasoning as [uploadVideoFile] on the way in.
  Future<void> downloadFile({
    required String url,
    required String savePath,
    required void Function(double percent) onProgress,
  }) async {
    final client = http.Client();
    try {
      final response = await client.send(http.Request('GET', Uri.parse(url)));

      if (response.statusCode != 200) {
        throw ApiException('Échec du téléchargement (${response.statusCode}).');
      }

      final total = response.contentLength ?? 0;
      var received = 0;
      final sink = File(savePath).openWrite();
      try {
        await for (final chunk in response.stream) {
          received += chunk.length;
          sink.add(chunk);
          if (total > 0) onProgress(received / total * 100);
        }
      } finally {
        await sink.close();
      }
    } finally {
      client.close();
    }
  }

  Future<String> reportVideo({
    required int videoId,
    required String reason,
    required String token,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/videos/$videoId/report'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({'reason': reason}),
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return json['message'] as String;
  }

  Future<PaginatedResponse<Review>> fetchReviews(int videoId) async {
    final response = await http.get(Uri.parse('$baseUrl/videos/$videoId/reviews'));

    if (response.statusCode != 200) {
      throw ApiException('Impossible de charger les avis (${response.statusCode}).');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return PaginatedResponse.fromJson(json, Review.fromJson);
  }

  Future<Review> submitReview({
    required int videoId,
    required int rating,
    String? comment,
    required String token,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/videos/$videoId/reviews'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'rating': rating,
        if (comment != null && comment.isNotEmpty) 'comment': comment,
      }),
    );

    if (response.statusCode != 200 && response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return Review.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<bool> toggleFavorite({required int videoId, required String token}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/videos/$videoId/favorite'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return json['favorited'] as bool;
  }

  Future<PaginatedResponse<Video>> fetchMyFavorites(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/favorites'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return PaginatedResponse.fromJson(json, Video.fromJson);
  }

  Future<List<Video>> fetchRecommendedVideos() async {
    final response = await http.get(Uri.parse('$baseUrl/videos/recommended'));

    if (response.statusCode != 200) {
      throw ApiException('Impossible de charger les recommandations (${response.statusCode}).');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return (json['data'] as List).map((e) => Video.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<Video>> fetchFeaturedVideos() async {
    final response = await http.get(Uri.parse('$baseUrl/videos/featured'));

    if (response.statusCode != 200) {
      throw ApiException('Impossible de charger les vidéos en vedette (${response.statusCode}).');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return (json['data'] as List).map((e) => Video.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PaginatedResponse<Video>> fetchMyPurchases(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/purchases'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return PaginatedResponse.fromJson(json, Video.fromJson);
  }

  Future<List<CreatorVideo>> fetchMyVideos(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/creator/videos'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return (json['data'] as List)
        .map((item) => CreatorVideo.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<CreatorVideo> createVideo({
    required String token,
    required String title,
    String? description,
    required String category,
    int? price,
    int? durationSeconds,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/creator/videos'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({
        'title': title,
        if (description != null && description.isNotEmpty) 'description': description,
        'category': category,
        'price': ?price,
        'duration_seconds': ?durationSeconds,
      }),
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return CreatorVideo.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<String> createVideoUploadUrl({
    required int videoId,
    required String token,
    required int fileSize,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/creator/videos/$videoId/source'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({'file_size': fileSize}),
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return json['upload_url'] as String;
  }

  static const _tusResumableVersion = '1.0.0';
  // Cloudflare's recommended TUS chunk size — also a valid one (a multiple
  // of 256 KiB, required for every chunk but the file's last).
  static const _tusChunkSize = 50 * 1024 * 1024;

  /// Cloudflare Stream's one-time upload URL speaks the TUS resumable-upload
  /// protocol: the file goes up in chunks via PATCH, each carrying the byte
  /// offset it starts at — no 200MB cap like the old single-POST "Basic"
  /// upload, and no need for the account's secret API token (only creating
  /// the URL server-side needs that, see CloudflareStreamGateway). On a
  /// failed chunk, a HEAD request recovers the offset the server actually
  /// persisted before retrying, rather than assuming nothing landed — 3G/4G
  /// connections drop mid-request often enough that "the response never
  /// arrived" and "the server never got the bytes" aren't the same thing.
  Future<void> uploadVideoFile({
    required String uploadUrl,
    required String filePath,
    required void Function(double percent) onProgress,
  }) async {
    final file = File(filePath);
    final total = await file.length();
    var offset = 0;
    onProgress(0);
    while (offset < total) {
      offset = await _uploadTusChunkWithRetry(
        uploadUrl: uploadUrl,
        file: file,
        total: total,
        offset: offset,
        onProgress: onProgress,
      );
    }
    onProgress(100);
  }

  Future<int> _uploadTusChunkWithRetry({
    required String uploadUrl,
    required File file,
    required int total,
    required int offset,
    required void Function(double percent) onProgress,
  }) async {
    const retryDelays = [Duration.zero, Duration(seconds: 1), Duration(seconds: 3), Duration(seconds: 5)];

    var currentOffset = offset;
    Object? lastError;
    for (final delay in retryDelays) {
      if (delay > Duration.zero) {
        await Future.delayed(delay);
        try {
          currentOffset = await _fetchTusOffset(uploadUrl);
        } catch (err) {
          lastError = err;
          continue;
        }
      }
      try {
        return await _patchTusChunk(
          uploadUrl: uploadUrl,
          file: file,
          total: total,
          offset: currentOffset,
          onProgress: onProgress,
        );
      } catch (err) {
        lastError = err;
      }
    }
    throw ApiException(lastError.toString());
  }

  Future<int> _fetchTusOffset(String uploadUrl) async {
    final response = await http.head(Uri.parse(uploadUrl), headers: {'Tus-Resumable': _tusResumableVersion});
    final offset = int.tryParse(response.headers['upload-offset'] ?? '');
    if (response.statusCode < 200 || response.statusCode >= 300 || offset == null) {
      throw ApiException('Échec de l\'envoi du fichier vidéo (${response.statusCode}).');
    }
    return offset;
  }

  Future<int> _patchTusChunk({
    required String uploadUrl,
    required File file,
    required int total,
    required int offset,
    required void Function(double percent) onProgress,
  }) async {
    final end = offset + _tusChunkSize < total ? offset + _tusChunkSize : total;

    final uri = Uri.parse(uploadUrl);
    final request = http.StreamedRequest('PATCH', uri)
      ..headers['Tus-Resumable'] = _tusResumableVersion
      ..headers['Upload-Offset'] = offset.toString()
      ..headers['Content-Type'] = 'application/offset+octet-stream'
      ..contentLength = end - offset;

    var sent = 0;
    file.openRead(offset, end).listen(
      (bytes) {
        sent += bytes.length;
        onProgress((offset + sent) / total * 100);
        request.sink.add(bytes);
      },
      onDone: () => request.sink.close(),
      onError: request.sink.addError,
      cancelOnError: true,
    );

    final client = http.Client();
    try {
      final streamedResponse = await client.send(request);
      final response = await http.Response.fromStream(streamedResponse);
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw ApiException('Échec de l\'envoi du fichier vidéo (${response.statusCode}).');
      }
      return int.tryParse(response.headers['upload-offset'] ?? '') ?? end;
    } finally {
      client.close();
    }
  }

  Future<CreatorVideoStatus> fetchVideoSourceStatus({
    required int videoId,
    required String token,
  }) async {
    final response = await http.get(
      Uri.parse('$baseUrl/creator/videos/$videoId/source'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return CreatorVideoStatus.fromJson(json['source_status'] as Map<String, dynamic>);
  }

  Future<CreatorStats> fetchStats(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/creator/stats'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    return CreatorStats.fromJson(jsonDecode(response.body) as Map<String, dynamic>);
  }

  Future<CreatorBalance> fetchBalance(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/creator/balance'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    return CreatorBalance.fromJson(jsonDecode(response.body) as Map<String, dynamic>);
  }

  Future<List<Payout>> fetchMyPayouts(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/creator/payouts'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    // /creator/payouts serializes a raw Laravel paginator (pagination
    // fields at the root), unlike the other creator endpoints which nest
    // them under a "meta" key — a known minor API inconsistency.
    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return (json['data'] as List)
        .map((item) => Payout.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<Payout> requestPayout({
    required int amount,
    required String destinationMsisdn,
    required String token,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/creator/payouts'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({'amount': amount, 'destination_msisdn': destinationMsisdn}),
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    return Payout.fromJson(jsonDecode(response.body) as Map<String, dynamic>);
  }

  Future<List<Message>> fetchMyMessages(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/messages'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return (json['data'] as List)
        .map((item) => Message.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<Message> sendMessage({required String body, required String token}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/messages'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({'body': body}),
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    return Message.fromJson(jsonDecode(response.body) as Map<String, dynamic>);
  }

  Future<NotificationListResult> fetchNotifications(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/notifications'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return NotificationListResult(
      data: (json['data'] as List)
          .map((item) => AppNotification.fromJson(item as Map<String, dynamic>))
          .toList(),
      unreadCount: json['unread_count'] as int,
    );
  }

  Future<void> markNotificationRead({required String id, required String token}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/notifications/$id/read'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }
  }

  Future<void> markAllNotificationsRead(String token) async {
    final response = await http.post(
      Uri.parse('$baseUrl/notifications/read-all'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }
  }

  Future<AuthResult> registerCreator({
    required String name,
    required String phone,
    required String password,
    required String identityDocumentPath,
    required bool termsAccepted,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/register/creator'))
      ..fields['name'] = name
      ..fields['phone'] = phone
      ..fields['password'] = password
      ..fields['terms_accepted'] = termsAccepted.toString()
      ..files.add(await http.MultipartFile.fromPath('identity_document', identityDocumentPath));

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode != 200 && response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return AuthResult(
      token: json['token'] as String,
      user: StoredUser.fromJson(json['user'] as Map<String, dynamic>),
    );
  }

  /// Turns the signed-in viewer's own account into a creator account —
  /// same phone/id/purchase history, not a second disconnected account
  /// (registerCreator above always creates a brand-new user, which used
  /// to be the only option even for an already-logged-in viewer and just
  /// failed on the phone's uniqueness constraint).
  Future<StoredUser> upgradeToCreator({
    required String identityDocumentPath,
    required bool termsAccepted,
    required String token,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/creator/upgrade'))
      ..headers['Authorization'] = 'Bearer $token'
      ..fields['terms_accepted'] = termsAccepted.toString()
      ..files.add(await http.MultipartFile.fromPath('identity_document', identityDocumentPath));

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode != 200) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return StoredUser.fromJson(json['user'] as Map<String, dynamic>);
  }

  Future<AuthResult> _postAuth(String path, Map<String, dynamic> body) async {
    final response = await http.post(
      Uri.parse('$baseUrl$path'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(body),
    );

    if (response.statusCode != 200 && response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return AuthResult(
      token: json['token'] as String,
      user: StoredUser.fromJson(json['user'] as Map<String, dynamic>),
    );
  }

  String _extractErrorMessage(http.Response response) {
    try {
      final json = jsonDecode(response.body) as Map<String, dynamic>;
      final errors = json['errors'];
      if (errors is Map) {
        return errors.values.map((messages) => (messages as List).join(' ')).join(' ');
      }
      if (json['message'] is String) return json['message'] as String;
    } catch (_) {
      // response body wasn't JSON — fall through to the generic message below
    }
    return 'Une erreur est survenue (${response.statusCode}).';
  }
}
