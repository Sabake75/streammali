import 'package:flutter/material.dart';

/// Mirrors the web app's design tokens (apps/web/src/app/globals.css) so
/// both platforms read as the same product. Palette inspired by paydunya.com:
/// deep navy blue as the brand tone (logo, buttons, dark hero), sky blue as
/// the secondary accent, on a very light blue-grey background. `orange*`
/// fields keep their names from the previous palette to avoid touching every
/// consuming widget — they now hold navy blue values.
abstract final class AppColors {
  static const orange700 = Color(0xFF0A1B33);
  static const orange600 = Color(0xFF0F2D52); // primary
  static const orange500 = Color(0xFF2F6FE0);
  static const orange400 = Color(0xFF548BE4);
  static const orange300 = Color(0xFF8CB4EB);
  static const orange100 = Color(0xFFDBE6F7);
  static const orange50 = Color(0xFFF5F7FB);
  static const accent600 = Color(0xFF0EA5E9); // accent
  static const dark = Color(0xFF0A1B33); // dark
  static const background = Color(0xFFF4F7FB);
  static const neutral900 = Color(0xFF171717);
  static const neutral300 = Color(0xFFD4D4D4);

  // Dark mode — mirrors apps/web/src/app/globals.css's
  // `@media (prefers-color-scheme: dark)` block and its neutral-800/900/950
  // border/surface pairing used throughout web's dark: classes.
  static const darkBackground = Color(0xFF081428);
  static const darkForeground = Color(0xFFEAF2FC);
  static const darkSurface = Color(0xFF0A0A0A);
  static const darkInputSurface = Color(0xFF171717);
  static const darkBorder = Color(0xFF262626);
  static const darkInputBorder = Color(0xFF404040);
}

/// Material 3's default bodyMedium is 14px — the ambient style every bare
/// `Text(...)` picks up without an explicit style. Bumped to 16px (the
/// recommended mobile minimum, readable in bright outdoor light) since it
/// silently undersizes most reading content in the app: descriptions,
/// messages, review comments, error text… Other fields stay null so
/// ThemeData still fills them in from its own Material defaults.
const _textTheme = TextTheme(bodyMedium: TextStyle(fontSize: 16));

class AppTheme {
  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(seedColor: AppColors.orange600).copyWith(
      primary: AppColors.orange600,
      onPrimary: Colors.white,
      secondary: AppColors.accent600,
      surface: Colors.white,
    );

    final inputBorder = OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: const BorderSide(color: AppColors.neutral300),
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      textTheme: _textTheme,
      scaffoldBackgroundColor: AppColors.background,
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.background,
        foregroundColor: AppColors.neutral900,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: TextStyle(
          color: AppColors.neutral900,
          fontSize: 20,
          fontWeight: FontWeight.w700,
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: const BorderSide(color: AppColors.neutral300),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: inputBorder,
        enabledBorder: inputBorder,
        focusedBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: AppColors.orange500, width: 2),
        ),
        // Always float the label above the field instead of only once it
        // has content — otherwise dropdowns/pre-filled fields (label
        // floated) sit next to empty text fields (label centered inside)
        // on the same form, which reads as inconsistent.
        floatingLabelBehavior: FloatingLabelBehavior.always,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.orange600,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.orange600,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.orange600,
          side: const BorderSide(color: AppColors.orange600),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(foregroundColor: AppColors.orange600),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.orange50,
        labelStyle: const TextStyle(color: AppColors.orange700, fontWeight: FontWeight.w600),
        side: BorderSide.none,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      ),
    );
  }

  static ThemeData dark() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: AppColors.orange500,
      brightness: Brightness.dark,
    ).copyWith(
      primary: AppColors.orange500,
      onPrimary: Colors.black,
      secondary: AppColors.accent600,
      surface: AppColors.darkSurface,
    );

    final inputBorder = OutlineInputBorder(
      borderRadius: BorderRadius.circular(10),
      borderSide: const BorderSide(color: AppColors.darkInputBorder),
    );

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: colorScheme,
      textTheme: _textTheme,
      scaffoldBackgroundColor: AppColors.darkBackground,
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.darkBackground,
        foregroundColor: AppColors.darkForeground,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: TextStyle(
          color: AppColors.darkForeground,
          fontSize: 20,
          fontWeight: FontWeight.w700,
        ),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: AppColors.darkSurface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: const BorderSide(color: AppColors.darkBorder),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.darkInputSurface,
        border: inputBorder,
        enabledBorder: inputBorder,
        focusedBorder: inputBorder.copyWith(
          borderSide: const BorderSide(color: AppColors.orange500, width: 2),
        ),
        floatingLabelBehavior: FloatingLabelBehavior.always,
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.orange500,
          foregroundColor: Colors.black,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.orange500,
          foregroundColor: Colors.black,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
          textStyle: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.orange400,
          side: const BorderSide(color: AppColors.orange400),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(foregroundColor: AppColors.orange400),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.orange700,
        labelStyle: const TextStyle(color: AppColors.orange300, fontWeight: FontWeight.w600),
        side: BorderSide.none,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      ),
    );
  }
}

/// The small vertical accent bar + bold title web uses to head every
/// catalogue section ("En vedette", "Catalogue", "Vidéos similaires"…).
class SectionHeading extends StatelessWidget {
  final String title;

  const SectionHeading({super.key, required this.title});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 6,
          height: 20,
          decoration: BoxDecoration(
            color: AppColors.orange600,
            borderRadius: BorderRadius.circular(999),
          ),
        ),
        const SizedBox(width: 8),
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
        ),
      ],
    );
  }
}
