import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:phone_numbers_parser/phone_numbers_parser.dart';
import 'package:streammali_mobile/widgets/phone_number_field.dart';

/// Drives the country dropdown the same way a real tap-and-select would,
/// but by invoking its onChanged directly — the menu holds 245 items and
/// opening/scrolling/tapping one in a widget test is UI mechanics Flutter's
/// own DropdownButton already owns, not the composition logic under test here.
void selectCountry(WidgetTester tester, IsoCode isoCode) {
  final dropdown = tester.widget<DropdownButton<IsoCode>>(
    find.byWidgetPredicate((widget) => widget is DropdownButton<IsoCode>),
  );
  dropdown.onChanged!(isoCode);
}

void main() {
  testWidgets('caps digits to the selected country length and composes E.164', (tester) async {
    final controller = TextEditingController();
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(body: PhoneNumberField(controller: controller)),
      ),
    );

    // Default country is Mali, capped at 8 digits.
    await tester.enterText(find.byType(TextFormField), '650123459999');
    await tester.pump();
    expect(find.text('65012345'), findsOneWidget);
    expect(controller.text, '+22365012345');

    // Switch country to France: cap becomes 9, existing 8 digits untouched.
    selectCountry(tester, IsoCode.FR);
    await tester.pump();
    expect(controller.text, '+3365012345');

    // Typing past 9 digits under France still caps correctly.
    await tester.enterText(find.byType(TextFormField), '612345678999');
    await tester.pump();
    expect(find.text('612345678'), findsOneWidget);
    expect(controller.text, '+33612345678');
  });

  testWidgets('an external controller change (e.g. clear()) re-syncs the field', (tester) async {
    final controller = TextEditingController(text: '+22376123456');
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(body: PhoneNumberField(controller: controller)),
      ),
    );

    expect(find.text('76123456'), findsOneWidget);

    controller.clear();
    await tester.pump();
    expect(find.text('76123456'), findsNothing);
  });
}
