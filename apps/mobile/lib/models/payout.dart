class CreatorBalance {
  final int availableBalance;
  final int minimumPayoutAmount;

  const CreatorBalance({required this.availableBalance, required this.minimumPayoutAmount});

  factory CreatorBalance.fromJson(Map<String, dynamic> json) {
    return CreatorBalance(
      availableBalance: json['available_balance'] as int,
      minimumPayoutAmount: json['minimum_payout_amount'] as int,
    );
  }
}

class Payout {
  final int id;
  final int amount;
  final String destinationMsisdn;
  final String statusValue;
  final String statusLabel;
  final String? rejectionReason;

  const Payout({
    required this.id,
    required this.amount,
    required this.destinationMsisdn,
    required this.statusValue,
    required this.statusLabel,
    this.rejectionReason,
  });

  factory Payout.fromJson(Map<String, dynamic> json) {
    final status = json['status'] as Map<String, dynamic>;
    return Payout(
      id: json['id'] as int,
      amount: json['amount'] as int,
      destinationMsisdn: json['destination_msisdn'] as String,
      statusValue: status['value'] as String,
      statusLabel: status['label'] as String,
      rejectionReason: json['rejection_reason'] as String?,
    );
  }
}
