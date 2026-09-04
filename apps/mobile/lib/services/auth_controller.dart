import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/user.dart';

const _tokenKey = 'streammali_token';
const _userKey = 'streammali_user';

/// Holds the current Sanctum Bearer token and user — same auth flow as the
/// Next.js frontend (apps/web/src/lib/auth-client.ts), so both clients hit
/// the same register/login/purchase endpoints.
///
/// The token (a Sanctum Bearer credential — full account access, not just a
/// display value) lives in flutter_secure_storage (Android Keystore / iOS
/// Keychain-backed), not shared_preferences: the latter is a plain XML/plist
/// file on disk, readable on a rooted device or via an unencrypted backup.
/// The user profile (name/phone — already shown in the UI, not a secret)
/// stays in shared_preferences, unchanged.
class AuthController extends ChangeNotifier {
  AuthController._();

  static final AuthController instance = AuthController._();

  static const _secureStorage = FlutterSecureStorage();

  String? _token;
  StoredUser? _user;

  String? get token => _token;
  StoredUser? get user => _user;
  bool get isAuthenticated => _token != null;

  Future<void> loadFromStorage() async {
    _token = await _secureStorage.read(key: _tokenKey);
    final prefs = await SharedPreferences.getInstance();
    final rawUser = prefs.getString(_userKey);
    _user = rawUser != null ? StoredUser.fromJson(jsonDecode(rawUser) as Map<String, dynamic>) : null;
    notifyListeners();
  }

  Future<void> setSession(String token, StoredUser user) async {
    _token = token;
    _user = user;
    await _secureStorage.write(key: _tokenKey, value: token);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    notifyListeners();
  }

  Future<void> clearSession() async {
    _token = null;
    _user = null;
    await _secureStorage.delete(key: _tokenKey);
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_userKey);
    notifyListeners();
  }
}
