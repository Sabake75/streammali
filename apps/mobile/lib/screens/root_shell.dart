import 'package:flutter/material.dart';

import '../services/auth_controller.dart';
import 'account_screen.dart';
import 'catalogue_screen.dart';
import 'favorites_screen.dart';
import 'library_screen.dart';
import 'login_screen.dart';

/// Barre de navigation persistante en bas d'écran (façon TikTok) à la place
/// de l'ancien tiroir de menu (hamburger). Seul "Accueil" est visible sans
/// compte — les 3 autres onglets exigent d'être connecté, comme c'était déjà
/// le cas pour ce qu'ils affichent (FavoritesScreen/LibraryScreen lèvent une
/// ApiException si le token est nul) ; on évite juste de les monter dans ce
/// cas plutôt que de laisser apparaître un message d'erreur générique.
class RootShell extends StatefulWidget {
  const RootShell({super.key});

  @override
  State<RootShell> createState() => _RootShellState();
}

class _RootShellState extends State<RootShell> {
  int _index = 0;

  static const _destinations = [
    (icon: Icons.home_outlined, selectedIcon: Icons.home, label: 'Accueil'),
    (icon: Icons.favorite_border, selectedIcon: Icons.favorite, label: 'Favoris'),
    (icon: Icons.video_library_outlined, selectedIcon: Icons.video_library, label: 'Bibliothèque'),
    (icon: Icons.person_outline, selectedIcon: Icons.person, label: 'Compte'),
  ];

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: AuthController.instance,
      builder: (context, _) {
        final authenticated = AuthController.instance.isAuthenticated;

        return Scaffold(
          body: IndexedStack(
            index: _index,
            children: [
              const CatalogueScreen(),
              authenticated ? const FavoritesScreen() : const _SignInRequired(),
              authenticated ? const LibraryScreen() : const _SignInRequired(),
              authenticated ? const AccountScreen() : const _SignInRequired(),
            ],
          ),
          bottomNavigationBar: NavigationBar(
            selectedIndex: _index,
            onDestinationSelected: (value) => setState(() => _index = value),
            destinations: [
              for (final d in _destinations)
                NavigationDestination(icon: Icon(d.icon), selectedIcon: Icon(d.selectedIcon), label: d.label),
            ],
          ),
        );
      },
    );
  }
}

class _SignInRequired extends StatelessWidget {
  const _SignInRequired();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.lock_outline, size: 48, color: Colors.grey.shade400),
                const SizedBox(height: 16),
                const Text('Connecte-toi pour voir ça.', textAlign: TextAlign.center),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (context) => const LoginScreen()),
                    );
                  },
                  child: const Text('Se connecter'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
