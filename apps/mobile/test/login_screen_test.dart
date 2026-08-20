import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:streammali_mobile/screens/login_screen.dart';

void main() {
  testWidgets('Login form shows validation errors on empty submit', (WidgetTester tester) async {
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));

    await tester.tap(find.text('Se connecter'));
    await tester.pump();

    expect(find.text('Champ requis'), findsOneWidget);
    expect(find.text('Code à 4 chiffres requis'), findsOneWidget);
  });
}
