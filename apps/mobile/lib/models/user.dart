class StoredUser {
  final int id;
  final String name;
  final String phone;
  final String role;

  const StoredUser({
    required this.id,
    required this.name,
    required this.phone,
    required this.role,
  });

  factory StoredUser.fromJson(Map<String, dynamic> json) {
    return StoredUser(
      id: json['id'] as int,
      name: json['name'] as String,
      phone: json['phone'] as String,
      role: json['role'] as String,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'role': role,
      };
}
