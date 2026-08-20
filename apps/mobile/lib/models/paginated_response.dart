class PaginatedResponse<T> {
  final List<T> data;
  final int currentPage;
  final int lastPage;
  final int total;

  const PaginatedResponse({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory PaginatedResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJsonT,
  ) {
    final meta = json['meta'] as Map<String, dynamic>;

    return PaginatedResponse(
      data: (json['data'] as List)
          .map((item) => fromJsonT(item as Map<String, dynamic>))
          .toList(),
      currentPage: meta['current_page'] as int,
      lastPage: meta['last_page'] as int,
      total: meta['total'] as int,
    );
  }
}
