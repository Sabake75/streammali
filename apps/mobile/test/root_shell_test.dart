import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:streammali_mobile/screens/root_shell.dart';

void main() {
  testWidgets('Bottom navigation shows all 4 tabs and gates guest access', (WidgetTester tester) async {
    await tester.pumpWidget(const MaterialApp(home: RootShell()));

    expect(find.text('Accueil'), findsOneWidget);
    expect(find.text('Favoris'), findsOneWidget);
    expect(find.text('Bibliothèque'), findsOneWidget);
    expect(find.text('Compte'), findsOneWidget);

    // No session loaded in this test — every tab but "Accueil" must gate
    // behind a sign-in prompt rather than attempt a real API call.
    await tester.tap(find.text('Favoris'));
    await tester.pump();
    expect(find.text('Connecte-toi pour voir ça.'), findsOneWidget);

    await tester.tap(find.text('Bibliothèque'));
    await tester.pump();
    expect(find.text('Connecte-toi pour voir ça.'), findsOneWidget);

    await tester.tap(find.text('Compte'));
    await tester.pump();
    expect(find.text('Connecte-toi pour voir ça.'), findsOneWidget);
  });
}
