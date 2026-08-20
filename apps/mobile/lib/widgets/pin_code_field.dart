import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class PinCodeField extends StatelessWidget {
  final TextEditingController controller;
  final String label;

  const PinCodeField({super.key, required this.controller, this.label = 'Code (4 chiffres)'});

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()),
      keyboardType: TextInputType.number,
      obscureText: true,
      maxLength: 4,
      inputFormatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(4)],
      buildCounter: (context, {required currentLength, required isFocused, maxLength}) => null,
      validator: (value) => (value == null || value.length != 4) ? 'Code à 4 chiffres requis' : null,
    );
  }
}
