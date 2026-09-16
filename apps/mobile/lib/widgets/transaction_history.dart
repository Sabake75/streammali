import 'package:flutter/material.dart';

import '../models/transaction.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../utils/formatting.dart';
import 'status_pill.dart';

/// One row per vente (LedgerEntry côté API), distinct de "Historique des
/// demandes" dans BalanceAndPayouts (qui liste les demandes de retrait, pas
/// les ventes individuelles).
class TransactionHistory extends StatefulWidget {
  const TransactionHistory({super.key});

  @override
  State<TransactionHistory> createState() => _TransactionHistoryState();
}

class _TransactionHistoryState extends State<TransactionHistory> {
  final ApiClient _apiClient = ApiClient();
  int _page = 1;
  TransactionPage? _transactions;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  Future<void> _reload() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    try {
      final transactions = await _apiClient.fetchMyTransactions(token, page: _page);
      if (!mounted) return;
      setState(() => _transactions = transactions);
    } catch (_) {
      // transient load failure — the section just stays empty
    }
  }

  void _goToPage(int page) {
    setState(() {
      _page = page;
      _transactions = null;
    });
    _reload();
  }

  @override
  Widget build(BuildContext context) {
    if (_transactions != null && _transactions!.data.isEmpty && _page == 1) {
      return const SizedBox.shrink();
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Historique des transactions', style: Theme.of(context).textTheme.bodySmall),
            const SizedBox(height: 8),
            if (_transactions == null)
              const Text('Chargement…')
            else
              ..._transactions!.data.map(
                (transaction) => Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: Theme.of(context).dividerColor),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              transaction.videoTitle ?? 'Vidéo supprimée',
                              style: const TextStyle(fontWeight: FontWeight.w600),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          Text(_formatDate(transaction.createdAt), style: Theme.of(context).textTheme.bodySmall),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('${formatPrice(transaction.grossAmount)} brut'),
                          Row(
                            children: [
                              Text(
                                '${formatPrice(transaction.netAmount)} net',
                                style: TextStyle(fontWeight: FontWeight.w600, color: Theme.of(context).colorScheme.primary),
                              ),
                              if (transaction.statusValue != null) ...[
                                const SizedBox(width: 8),
                                StatusPill(value: transaction.statusValue!, label: transaction.statusLabel!),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            if (_transactions != null && _transactions!.lastPage > 1)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  TextButton(
                    onPressed: _page > 1 ? () => _goToPage(_page - 1) : null,
                    child: const Text('Précédent'),
                  ),
                  Text('Page $_page / ${_transactions!.lastPage}'),
                  TextButton(
                    onPressed: _page < _transactions!.lastPage ? () => _goToPage(_page + 1) : null,
                    child: const Text('Suivant'),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime date) =>
      '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
}
