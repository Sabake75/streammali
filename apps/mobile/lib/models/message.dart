class MessageSender {
  final int id;
  final String name;
  final String role;

  const MessageSender({required this.id, required this.name, required this.role});

  factory MessageSender.fromJson(Map<String, dynamic> json) {
    return MessageSender(
      id: json['id'] as int,
      name: json['name'] as String,
      role: json['role'] as String,
    );
  }
}

class Message {
  final int id;
  final String body;
  final MessageSender sender;
  final DateTime createdAt;

  const Message({required this.id, required this.body, required this.sender, required this.createdAt});

  factory Message.fromJson(Map<String, dynamic> json) {
    return Message(
      id: json['id'] as int,
      body: json['body'] as String,
      sender: MessageSender.fromJson(json['sender'] as Map<String, dynamic>),
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}
