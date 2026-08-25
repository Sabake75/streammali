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
      _amountController.clear();
      _destinationController.clear();
      await _reload();
    } catch (err) {
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
            Text('Solde et retraits', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            if (_balance != null)
              Text(
                '${formatPrice(_balance!.availableBalance)} disponible (retrait min. ${formatPrice(_balance!.minimumPayoutAmount)})',
                style: Theme.of(context).textTheme.bodyMedium,
              )
            else
              const Text('Chargement…'),
            const SizedBox(height: 12),
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
              const SizedBox(height: 12),
              Text('Historique', style: Theme.of(context).textTheme.titleSmall),
              ..._payouts!.map(
                (payout) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    children: [
                      Expanded(child: Text(formatPrice(payout.amount))),
                      Expanded(child: Text(payout.destinationMsisdn)),
                      Text(payout.statusLabel, style: const TextStyle(fontWeight: FontWeight.bold)),
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
