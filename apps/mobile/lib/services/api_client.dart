import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/paginated_response.dart';
import '../models/video.dart';

class ApiException implements Exception {
  final String message;

  ApiException(this.message);

  @override
  String toString() => message;
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

  Future<Video?> fetchVideo(int id) async {
    final response = await http.get(Uri.parse('$baseUrl/videos/$id'));

    if (response.statusCode == 404) return null;
    if (response.statusCode != 200) {
      throw ApiException('Impossible de charger la vidéo (${response.statusCode}).');
    }

    final json = jsonDecode(response.body) as Map<String, dynamic>;
    return Video.fromJson(json['data'] as Map<String, dynamic>);
  }
}
