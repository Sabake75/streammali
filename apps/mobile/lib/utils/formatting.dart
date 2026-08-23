String formatDuration(int? seconds) {
  if (seconds == null || seconds == 0) return 'Durée inconnue';

  final hours = seconds ~/ 3600;
  final minutes = ((seconds % 3600) / 60).round();

  if (hours == 0 && minutes == 0) return '$seconds s';
  if (hours == 0) return '$minutes min';
  return '$hours h ${minutes.toString().padLeft(2, '0')}';
}

String formatPrice(int price) => '$price FCFA';
