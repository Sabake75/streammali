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
  final DateTime createdAt;

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
    required this.createdAt,
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
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}
