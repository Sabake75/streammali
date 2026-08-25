import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:streammali_mobile/main.dart';
import 'package:streammali_mobile/widgets/app_logo.dart';

void main() {
  testWidgets('Catalogue screen renders search and filter controls', (WidgetTester tester) async {
    await tester.pumpWidget(const StreamMaliApp());

    expect(find.byType(AppLogo), findsOneWidget);
    expect(find.byType(TextField), findsOneWidget);
    expect(find.text('Catégorie'), findsOneWidget);
  });
}
