import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/user.dart';

const _tokenKey = 'streammali_token';
const _userKey = 'streammali_user';

/// Holds the current Sanctum Bearer token and user, persisted with
/// shared_preferences — same auth flow as the Next.js frontend
/// (apps/web/src/lib/auth-client.ts), so both clients hit the same
/// register/login/purchase endpoints.
class AuthController extends ChangeNotifier {
  AuthController._();

  static final AuthController instance = AuthController._();

  String? _token;
  StoredUser? _user;

  String? get token => _token;
  StoredUser? get user => _user;
  bool get isAuthenticated => _token != null;

  Future<void> loadFromStorage() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
    final rawUser = prefs.getString(_userKey);
    _user = rawUser != null ? StoredUser.fromJson(jsonDecode(rawUser) as Map<String, dynamic>) : null;
    notifyListeners();
  }

  Future<void> setSession(String token, StoredUser user) async {
    _token = token;
    _user = user;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    notifyListeners();
  }

  Future<void> clearSession() async {
    _token = null;
    _user = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    notifyListeners();
  }
}
