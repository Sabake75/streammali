class CreatorVideoStats {
  final int id;
  final String title;
  final int viewsCount;
  final int purchasesCount;
  final int revenue;

  const CreatorVideoStats({
    required this.id,
    required this.title,
    required this.viewsCount,
    required this.purchasesCount,
    required this.revenue,
  });

  factory CreatorVideoStats.fromJson(Map<String, dynamic> json) {
    return CreatorVideoStats(
      id: json['id'] as int,
      title: json['title'] as String,
      viewsCount: json['views_count'] as int,
      purchasesCount: json['purchases_count'] as int,
      revenue: json['revenue'] as int,
    );
  }
}

class CreatorStatsTotals {
  final int views;
  final int purchases;
  final int revenue;

  const CreatorStatsTotals({required this.views, required this.purchases, required this.revenue});

  factory CreatorStatsTotals.fromJson(Map<String, dynamic> json) {
    return CreatorStatsTotals(
      views: json['views'] as int,
      purchases: json['purchases'] as int,
      revenue: json['revenue'] as int,
    );
  }
}

class RevenuePoint {
  final String date;
  final int revenue;

  const RevenuePoint({required this.date, required this.revenue});

  factory RevenuePoint.fromJson(Map<String, dynamic> json) {
    return RevenuePoint(date: json['date'] as String, revenue: json['revenue'] as int);
  }
}

class CreatorStats {
  final List<CreatorVideoStats> videos;
  final CreatorStatsTotals totals;
  final List<RevenuePoint> timeseries;

  const CreatorStats({required this.videos, required this.totals, required this.timeseries});

  factory CreatorStats.fromJson(Map<String, dynamic> json) {
    return CreatorStats(
      videos: (json['videos'] as List)
          .map((item) => CreatorVideoStats.fromJson(item as Map<String, dynamic>))
          .toList(),
      totals: CreatorStatsTotals.fromJson(json['totals'] as Map<String, dynamic>),
      timeseries: (json['timeseries'] as List)
          .map((item) => RevenuePoint.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }
}
