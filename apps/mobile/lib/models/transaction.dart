class Transaction {
  final int id;
  final String? videoTitle;
  final int grossAmount;
  final int commissionAmount;
  final int netAmount;
  final String? statusValue;
  final String? statusLabel;
  final DateTime createdAt;

  const Transaction({
    required this.id,
    required this.videoTitle,
    required this.grossAmount,
    required this.commissionAmount,
    required this.netAmount,
    required this.statusValue,
    required this.statusLabel,
    required this.createdAt,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) {
    final status = json['status'] as Map<String, dynamic>?;
    return Transaction(
      id: json['id'] as int,
      videoTitle: json['video_title'] as String?,
      grossAmount: json['gross_amount'] as int,
      commissionAmount: json['commission_amount'] as int,
      netAmount: json['net_amount'] as int,
      statusValue: status?['value'] as String?,
      statusLabel: status?['label'] as String?,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}

class TransactionPage {
  final List<Transaction> data;
  final int currentPage;
  final int lastPage;

  const TransactionPage({required this.data, required this.currentPage, required this.lastPage});

  // Same raw-paginator shape as /api/creator/payouts (see fetchMyPayouts).
  factory TransactionPage.fromJson(Map<String, dynamic> json) {
    return TransactionPage(
      data: (json['data'] as List).map((item) => Transaction.fromJson(item as Map<String, dynamic>)).toList(),
      currentPage: json['current_page'] as int,
      lastPage: json['last_page'] as int,
    );
  }
}
