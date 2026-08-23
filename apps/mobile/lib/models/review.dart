class ReviewAuthor {
  final int id;
  final String name;

  const ReviewAuthor({required this.id, required this.name});

  factory ReviewAuthor.fromJson(Map<String, dynamic> json) {
    return ReviewAuthor(
      id: json['id'] as int,
      name: json['name'] as String,
    );
  }
}

class Review {
  final int id;
  final int rating;
  final String? comment;
  final ReviewAuthor user;
  final DateTime createdAt;

  const Review({
    required this.id,
    required this.rating,
    this.comment,
    required this.user,
    required this.createdAt,
  });

  factory Review.fromJson(Map<String, dynamic> json) {
    return Review(
      id: json['id'] as int,
      rating: json['rating'] as int,
      comment: json['comment'] as String?,
      user: ReviewAuthor.fromJson(json['user'] as Map<String, dynamic>),
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}
