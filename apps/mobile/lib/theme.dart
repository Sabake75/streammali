import 'package:flutter/material.dart';

/// Mirrors the web app's design tokens (apps/web/src/app/globals.css) so
/// both platforms read as the same product. Palette "cinéma malien" : or
/// chaud comme couleur de marque (logo, boutons, hero) et rouge grenat en
/// accent secondaire, sur un fond crème chaud — remplace la palette
/// précédente empruntée à PayDunya (bleu marine/bleu ciel), qui lisait
/// "appli fintech" plutôt que "plateforme vidéo". `orange*` fields keep
/// their names from the previous palette to avoid touching every consuming
/// widget — they now hold gold values.
abstract final class AppColors {
  static const orange700 = Color(0xFF7A3F0B);
  static const orange600 = Color(0xFF95520F); // primary
  static const orange500 = Color(0xFFA66414);
  static const orange400 = Color(0xFFD98E26);
  static const orange300 = Color(0xFFE7AD55);
  static const orange100 = Color(0xFFFAE7C6);
  static const orange50 = Color(0xFFFEF5E7);
  static const accent600 = Color(0xFFBA1239); // accent
  static const dark = Color(0xFF241B10); // dark
  static const background = Color(0xFFFAF6F0);
  static const neutral900 = Color(0xFF171717);
  static const neutral300 = Color(0xFFD4D4D4);

  // Dark mode — mirrors apps/web/src/app/globals.css's
  // `@media (prefers-color-scheme: dark)` block and its neutral-800/900/950
  // border/surface pairing used throughout web's dark: classes.
  static const darkBackground = Color(0xFF14110C);
  static const darkForeground = Color(0xFFF6ECE0);
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
        // Material 3's default content padding (no isDense) made every
        // field in the app noticeably tall — reported directly ("champs
        // de saisie trop grands"). isDense shrinks the built-in vertical
        // padding; the explicit contentPadding keeps enough breathing room
        // for the always-floated label above without reverting to the
        // full default height.
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
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
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
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
