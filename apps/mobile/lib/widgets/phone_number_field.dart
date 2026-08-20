import 'package:flutter/material.dart';
import 'package:phone_numbers_parser/phone_numbers_parser.dart';

import '../utils/phone.dart';

/// Country-code dropdown + digits-only input. [controller] always holds the
/// composed E.164 value (e.g. "+22376123456"), so call sites that used to
/// read a plain phone [TextEditingController] keep working unchanged.
class PhoneNumberField extends StatefulWidget {
  final TextEditingController controller;
  final String label;

  const PhoneNumberField({super.key, required this.controller, this.label = 'Téléphone'});

  @override
  State<PhoneNumberField> createState() => _PhoneNumberFieldState();
}

class _PhoneNumberFieldState extends State<PhoneNumberField> {
  late IsoCode _country;
  late final TextEditingController _digitsController;
  // The last value we wrote to widget.controller ourselves, so its listener
  // can tell our own writes apart from an external change (e.g. a parent
  // calling controller.clear() after a successful submit) without looping.
  String? _lastWritten;

  @override
  void initState() {
    super.initState();
    final split = splitPhone(widget.controller.text);
    _country = split.isoCode;
    _digitsController = TextEditingController(text: split.digits);
    _digitsController.addListener(_onDigitsChanged);
    widget.controller.addListener(_onExternalControllerChanged);
    _writeComposedValue(split.digits);
  }

  @override
  void dispose() {
    _digitsController.removeListener(_onDigitsChanged);
    widget.controller.removeListener(_onExternalControllerChanged);
    _digitsController.dispose();
    super.dispose();
  }

  void _writeComposedValue(String digits) {
    _lastWritten = composePhone(_country, digits);
    widget.controller.text = _lastWritten!;
  }

  void _onExternalControllerChanged() {
    if (widget.controller.text == _lastWritten) return;
    final split = splitPhone(widget.controller.text);
    setState(() => _country = split.isoCode);
    _digitsController.text = split.digits;
  }

  void _onDigitsChanged() {
    final maxDigits = maxDigitsFor(_country);
    final cleaned = _digitsController.text.replaceAll(RegExp(r'\D'), '');
    final capped = cleaned.length > maxDigits ? cleaned.substring(0, maxDigits) : cleaned;
    if (capped != _digitsController.text) {
      _digitsController.value = TextEditingValue(
        text: capped,
        selection: TextSelection.collapsed(offset: capped.length),
      );
      return; // this listener fires again with the corrected text
    }
    _writeComposedValue(capped);
  }

  void _onCountryChanged(IsoCode? next) {
    if (next == null || next == _country) return;
    setState(() {
      _country = next;
      final maxDigits = maxDigitsFor(_country);
      if (_digitsController.text.length > maxDigits) {
        _digitsController.text = _digitsController.text.substring(0, maxDigits);
      }
    });
    _writeComposedValue(_digitsController.text);
  }

  @override
  Widget build(BuildContext context) {
    final maxDigits = maxDigitsFor(_country);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 150,
          child: InputDecorator(
            decoration: const InputDecoration(labelText: 'Indicatif', border: OutlineInputBorder()),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<IsoCode>(
                value: _country,
                isExpanded: true,
                onChanged: _onCountryChanged,
                items: [
                  for (final country in countries)
                    DropdownMenuItem(
                      value: country.isoCode,
                      child: Text(
                        '${country.name} (+${country.callingCode})',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: TextFormField(
            controller: _digitsController,
            decoration: InputDecoration(labelText: widget.label, border: const OutlineInputBorder()),
            keyboardType: TextInputType.phone,
            maxLength: maxDigits,
            buildCounter: (context, {required currentLength, required isFocused, maxLength}) => null,
            validator: (value) => (value == null || value.isEmpty) ? 'Champ requis' : null,
          ),
        ),
      ],
    );
  }
}
