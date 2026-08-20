import 'video.dart';

class CreatorVideoStatus {
  final String value;
  final String label;

  const CreatorVideoStatus({required this.value, required this.label});

  factory CreatorVideoStatus.fromJson(Map<String, dynamic> json) {
    return CreatorVideoStatus(
      value: json['value'] as String,
      label: json['label'] as String,
    );
  }
}

class CreatorVideo {
  final int id;
  final String title;
  final String? description;
  final VideoCategory category;
  final int price;
  final int? durationSeconds;
  final CreatorVideoStatus status;
  final String? rejectionReason;
  final CreatorVideoStatus sourceStatus;

  const CreatorVideo({
    required this.id,
    required this.title,
    this.description,
    required this.category,
    required this.price,
    this.durationSeconds,
    required this.status,
    this.rejectionReason,
    required this.sourceStatus,
  });

  factory CreatorVideo.fromJson(Map<String, dynamic> json) {
    return CreatorVideo(
      id: json['id'] as int,
      title: json['title'] as String,
      description: json['description'] as String?,
      category: VideoCategory.fromJson(json['category'] as Map<String, dynamic>),
      price: json['price'] as int,
      durationSeconds: json['duration_seconds'] as int?,
      status: CreatorVideoStatus.fromJson(json['status'] as Map<String, dynamic>),
      rejectionReason: json['rejection_reason'] as String?,
      sourceStatus: CreatorVideoStatus.fromJson(json['source_status'] as Map<String, dynamic>),
    );
  }
}
