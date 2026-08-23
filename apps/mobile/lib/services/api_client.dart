import 'dart:convert';

import 'package:http/http.dart' as http;

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

class ApiClient {
  /// Override with `flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api`
  /// on the Android emulator, where `localhost` refers to the emulator itself,
  /// not the host machine running the Laravel API.
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost:8000/api',
  );

  Future<PaginatedResponse<Video>> fetchVideos({
    String? category,
    String? search,
    int page = 1,
  }) async {
    final query = <String, String>{};
    if (category != null && category.isNotEmpty) query['category'] = category;
    if (search != null && search.isNotEmpty) query['search'] = search;
    if (page > 1) query['page'] = page.toString();

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
  }) {
    return _postAuth('/register', {'name': name, 'phone': phone, 'password': password});
  }

  Future<AuthResult> login({required String phone, required String password}) {
    return _postAuth('/login', {'phone': phone, 'password': password});
  }

  Future<void> logout(String token) async {
    await http
        .post(Uri.parse('$baseUrl/logout'), headers: {'Authorization': 'Bearer $token'})
        .catchError((_) => http.Response('', 0));
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
        if (price != null) 'price': price,
        if (durationSeconds != null) 'duration_seconds': durationSeconds,
      }),
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return CreatorVideo.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<String> createVideoUploadUrl({required int videoId, required String token}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/creator/videos/$videoId/source'),
      headers: {'Authorization': 'Bearer $token'},
    );

    if (response.statusCode != 201) {
      throw ApiException(_extractErrorMessage(response));
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return json['upload_url'] as String;
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
      Uri.parse('$baseUrl/creator/messages'),
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
      Uri.parse('$baseUrl/creator/messages'),
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

  Future<AuthResult> registerCreator({
    required String name,
    required String phone,
    required String password,
    required String identityDocumentPath,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/register/creator'))
      ..fields['name'] = name
      ..fields['phone'] = phone
      ..fields['password'] = password
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
