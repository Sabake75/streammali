import 'package:flutter/material.dart';

import '../models/payout.dart';
import '../services/api_client.dart';
import '../services/auth_controller.dart';
import '../utils/formatting.dart';
import 'phone_number_field.dart';

class BalanceAndPayouts extends StatefulWidget {
  const BalanceAndPayouts({super.key});

  @override
  State<BalanceAndPayouts> createState() => _BalanceAndPayoutsState();
}

class _BalanceAndPayoutsState extends State<BalanceAndPayouts> {
  final ApiClient _apiClient = ApiClient();
  final _amountController = TextEditingController();
  final _destinationController = TextEditingController();

  CreatorBalance? _balance;
  List<Payout>? _payouts;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  @override
  void dispose() {
    _amountController.dispose();
    _destinationController.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    try {
      final balance = await _apiClient.fetchBalance(token);
      final payouts = await _apiClient.fetchMyPayouts(token);
      if (!mounted) return;
      setState(() {
        _balance = balance;
        _payouts = payouts;
      });
    } catch (_) {
      // transient load failure — the section just stays empty
    }
  }

  Future<void> _submit() async {
    final token = AuthController.instance.token;
    if (token == null) return;

    final amount = int.tryParse(_amountController.text);
    if (amount == null) {
      setState(() => _error = 'Montant invalide.');
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await _apiClient.requestPayout(
        amount: amount,
        destinationMsisdn: _destinationController.text,
        token: token,
      );
      if (!mounted) return;
      _amountController.clear();
      _destinationController.clear();
      await _reload();
    } catch (err) {
      if (!mounted) return;
      setState(() => _error = err.toString());
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_balance != null) ...[
              Text(
                formatPrice(_balance!.availableBalance),
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: Theme.of(context).colorScheme.primary,
                    ),
              ),
              const SizedBox(height: 2),
              Text(
                'disponible (retrait min. ${formatPrice(_balance!.minimumPayoutAmount)})',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.grey),
              ),
            ] else
              const Text('Chargement…'),
            const SizedBox(height: 16),
            TextField(
              controller: _amountController,
              decoration: const InputDecoration(labelText: 'Montant (FCFA)'),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 8),
            PhoneNumberField(controller: _destinationController, label: 'Numéro Mobile Money'),
            const SizedBox(height: 8),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: Text(_submitting ? 'Envoi…' : 'Demander un retrait'),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: Colors.red)),
            ],
            if (_payouts != null && _payouts!.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text('Historique des demandes', style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(height: 8),
              ..._payouts!.map(
                (payout) => Container(
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
                          Text(formatPrice(payout.amount), style: const TextStyle(fontWeight: FontWeight.w600)),
                          Text(payout.statusLabel, style: const TextStyle(fontWeight: FontWeight.bold)),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Text(payout.destinationMsisdn, style: Theme.of(context).textTheme.bodySmall),
                      if (payout.statusValue == 'rejected' && payout.rejectionReason != null) ...[
                        const SizedBox(height: 4),
                        Text(
                          'Motif du refus : ${payout.rejectionReason}',
                          style: const TextStyle(color: Colors.red),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
