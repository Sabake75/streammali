import 'package:chewie/chewie.dart';
import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart' as vp;

import 'error_retry_view.dart';

/// [url] is an HLS manifest URL (Cloudflare Stream's `playback.hls`, see
/// apps/api CloudflareStreamGateway), played natively by ExoPlayer/AVPlayer
/// under the hood. `autoPlay: false` — Chewie's own play button is the
/// tap-to-play affordance (keeps data usage opt-in, see cahier des charges'
/// "faible consommation de données" constraint).
class VideoPlayerWidget extends StatefulWidget {
  final String url;

  const VideoPlayerWidget({super.key, required this.url});

  @override
  State<VideoPlayerWidget> createState() => _VideoPlayerWidgetState();
}

class _VideoPlayerWidgetState extends State<VideoPlayerWidget> {
  late vp.VideoPlayerController _videoController;
  ChewieController? _chewieController;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  void _initialize() {
    _failed = false;
    _videoController = vp.VideoPlayerController.networkUrl(Uri.parse(widget.url));
    _videoController.initialize().then((_) {
      if (!mounted) return;
      setState(() {
        _chewieController = ChewieController(
          videoPlayerController: _videoController,
          autoPlay: false,
          looping: false,
        );
      });
    }).catchError((_) {
      if (!mounted) return;
      setState(() => _failed = true);
    });
  }

  void _retry() {
    _chewieController?.dispose();
    _videoController.dispose();
    setState(() => _chewieController = null);
    _initialize();
  }

  @override
  void dispose() {
    _chewieController?.dispose();
    _videoController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_failed) {
      return AspectRatio(
        aspectRatio: 16 / 9,
        child: ColoredBox(
          color: Colors.black,
          child: ErrorRetryView(onRetry: _retry),
        ),
      );
    }

    final chewieController = _chewieController;

    if (chewieController == null) {
      return const AspectRatio(
        aspectRatio: 16 / 9,
        child: ColoredBox(
          color: Colors.black,
          child: Center(child: CircularProgressIndicator()),
        ),
      );
    }

    return AspectRatio(
      aspectRatio: _videoController.value.aspectRatio,
      child: Chewie(controller: chewieController),
    );
  }
}
