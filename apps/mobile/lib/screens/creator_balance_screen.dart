import 'package:flutter/material.dart';

import '../widgets/balance_and_payouts.dart';

class CreatorBalanceScreen extends StatelessWidget {
  const CreatorBalanceScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Solde et retraits')),
      body: const SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: EdgeInsets.all(16),
          child: BalanceAndPayouts(),
        ),
      ),
    );
  }
}
