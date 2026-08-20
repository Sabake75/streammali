import 'package:country_code_picker/country_code_picker.dart' show codes;
import 'package:phone_numbers_parser/phone_numbers_parser.dart';

class CountryOption {
  final IsoCode isoCode;
  final String name;
  final String callingCode;

  const CountryOption({required this.isoCode, required this.name, required this.callingCode});
}

const IsoCode defaultCountry = IsoCode.ML;

final List<CountryOption> countries = codes
    .map((entry) {
      final code = entry['code'];
      if (code == null) return null;
      final isoCode = IsoCode.values.asNameMap()[code];
      if (isoCode == null) return null;
      return CountryOption(
        isoCode: isoCode,
        name: entry['name'] ?? code,
        callingCode: (entry['dial_code'] ?? '').replaceFirst('+', ''),
      );
    })
    .whereType<CountryOption>()
    .toList()
  ..sort((a, b) => a.name.compareTo(b.name));

final Map<IsoCode, int> _maxDigitsCache = {};

/// Expected national number length for this country, from phone_numbers_parser's
/// own metadata — never guessed. Brute-forces the public [PhoneNumber.isValidLength]
/// API (the package doesn't expose its length tables directly) since only mobile
/// numbers are expected in these forms, falling back to any type if mobile is empty.
int maxDigitsFor(IsoCode isoCode) {
  final cached = _maxDigitsCache[isoCode];
  if (cached != null) return cached;

  for (var length = 17; length >= 1; length--) {
    final candidate = PhoneNumber(isoCode: isoCode, nsn: '1' * length);
    if (candidate.isValidLength(type: PhoneNumberType.mobile)) {
      return _maxDigitsCache[isoCode] = length;
    }
  }
  for (var length = 17; length >= 1; length--) {
    final candidate = PhoneNumber(isoCode: isoCode, nsn: '1' * length);
    if (candidate.isValidLength()) {
      return _maxDigitsCache[isoCode] = length;
    }
  }
  return _maxDigitsCache[isoCode] = 15;
}

String callingCodeFor(IsoCode isoCode) => PhoneNumber(isoCode: isoCode, nsn: '').countryCode;

String composePhone(IsoCode isoCode, String digits) => '+${callingCodeFor(isoCode)}$digits';

class SplitPhone {
  final IsoCode isoCode;
  final String digits;

  const SplitPhone({required this.isoCode, required this.digits});
}

/// Best-effort split of a full phone string (e.g. from a prefilled value) into
/// country + digits, matching the longest calling-code prefix.
SplitPhone splitPhone(String phone) {
  CountryOption? match;
  for (final country in countries) {
    if (phone.startsWith('+${country.callingCode}')) {
      if (match == null || country.callingCode.length > match.callingCode.length) {
        match = country;
      }
    }
  }

  if (match != null) {
    return SplitPhone(isoCode: match.isoCode, digits: phone.substring(match.callingCode.length + 1));
  }

  return SplitPhone(isoCode: defaultCountry, digits: phone.replaceAll(RegExp(r'\D'), ''));
}
