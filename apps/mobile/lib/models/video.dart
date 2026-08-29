class VideoCategory {
  final String value;
  final String label;

  const VideoCategory({required this.value, required this.label});

  factory VideoCategory.fromJson(Map<String, dynamic> json) {
    return VideoCategory(
      value: json['value'] as String,
      label: json['label'] as String,
    );
  }
}

class VideoCreator {
  final int id;
  final String name;

  const VideoCreator({required this.id, required this.name});

  factory VideoCreator.fromJson(Map<String, dynamic> json) {
    return VideoCreator(
      id: json['id'] as int,
      name: json['name'] as String,
    );
  }
}

/// The "reçu" detail — only present on GET /purchases ("Mes achats"),
/// absent everywhere else (catalogue, favorites, recommended…).
class VideoPurchase {
  final int amount;
  final DateTime purchasedAt;
  final String orderReference;

  const VideoPurchase({required this.amount, required this.purchasedAt, required this.orderReference});

  factory VideoPurchase.fromJson(Map<String, dynamic> json) {
    return VideoPurchase(
      amount: json['amount'] as int,
      purchasedAt: DateTime.parse(json['purchased_at'] as String),
      orderReference: json['order_reference'] as String,
    );
  }
}

class Video {
  final int id;
  final String title;
  final String? description;
  final VideoCategory category;
  final String? posterPath;
  final int? durationSeconds;
  final int price;
  final VideoCreator creator;
  final bool? purchased;
  final bool? favorited;
  final String? playbackUrl;
  final String? previewPlaybackUrl;
  final double? averageRating;
  final int reviewsCount;
  final DateTime createdAt;
  final VideoPurchase? purchase;

  const Video({
    required this.id,
    required this.title,
    this.description,
    required this.category,
    this.posterPath,
    this.durationSeconds,
    required this.price,
    required this.creator,
    this.purchased,
    this.favorited,
    this.playbackUrl,
    this.previewPlaybackUrl,
    this.averageRating,
    this.reviewsCount = 0,
    required this.createdAt,
    this.purchase,
  });

  factory Video.fromJson(Map<String, dynamic> json) {
    return Video(
      id: json['id'] as int,
      title: json['title'] as String,
      description: json['description'] as String?,
      category: VideoCategory.fromJson(json['category'] as Map<String, dynamic>),
      posterPath: json['poster_path'] as String?,
      durationSeconds: json['duration_seconds'] as int?,
      price: json['price'] as int,
      creator: VideoCreator.fromJson(json['creator'] as Map<String, dynamic>),
      purchased: json['purchased'] as bool?,
      favorited: json['favorited'] as bool?,
      playbackUrl: json['playback_url'] as String?,
      previewPlaybackUrl: json['preview_playback_url'] as String?,
      averageRating: (json['average_rating'] as num?)?.toDouble(),
      reviewsCount: json['reviews_count'] as int? ?? 0,
      createdAt: DateTime.parse(json['created_at'] as String),
      purchase: json['purchase'] != null
          ? VideoPurchase.fromJson(json['purchase'] as Map<String, dynamic>)
          : null,
    );
  }
}
