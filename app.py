from flask import Flask, render_template, request, jsonify
import subprocess
import os
import signal

app = Flask(__name__)
VIDEO_DIR = "/app/videos"
os.makedirs(VIDEO_DIR, exist_ok=True)
ffmpeg_process = None

@app.route('/')
def index():
    videos = [f for f in os.listdir(VIDEO_DIR) if f.endswith(('.mp4', '.mkv', '.avi', '.mov'))]
    return render_template('index.html', videos=videos, streaming=ffmpeg_process is not None)

@app.route('/start', methods=['POST'])
def start_stream():
    global ffmpeg_process
    if ffmpeg_process is not None:
        return jsonify({"status": "error", "message": "البث يعمل بالفعل حالياً!"})

    stream_key = request.form.get('stream_key')
    video_file = request.form.get('video_file')

    if not stream_key or not video_file:
        return jsonify({"status": "error", "message": "الرجاء إدخال مفتاح البث واختيار ملف الفيديو."})

    video_path = os.path.join(VIDEO_DIR, video_file)
    rtmp_url = f"rtmp://a.rtmp.youtube.com/live2/{stream_key}"

    cmd = f'ffmpeg -re -stream_loop -1 -i "{video_path}" -c:v copy -c:a copy -f flv "{rtmp_url}"'

    try:
        ffmpeg_process = subprocess.Popen(cmd, shell=True, preexec_fn=os.setsid)
        return jsonify({"status": "success", "message": "🚀 تم بدء البث بنجاح والتكرار تلقائي!"})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})

@app.route('/stop', methods=['POST'])
def stop_stream():
    global ffmpeg_process
    if ffmpeg_process is None:
        return jsonify({"status": "error", "message": "لا يوجد بث نشط لإيقافه."})

    try:
        os.killpg(os.getpgid(ffmpeg_process.pid), signal.SIGTERM)
        ffmpeg_process = None
        return jsonify({"status": "success", "message": "🛑 تم إيقاف البث بنجاح."})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})

@app.route('/download', methods=['POST'])
def download_video():
    youtube_url = request.form.get('youtube_url')
    if not youtube_url:
        return jsonify({"status": "error", "message": "الرجاء إدخال رابط يوتيوب صحيح."})

    # التحقق من وجود ملف الكوكيز لتمريره إلى أداة التحميل وتخطي حجب يوتيوب
    cookies_path = "/app/cookies.txt"
    cookies_flag = f'--cookies "{cookies_path}"' if os.path.exists(cookies_path) else ''

    cmd = f'yt-dlp {cookies_flag} -P "{VIDEO_DIR}" -f "bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]" --merge-output-format mp4 "{youtube_url}"'

    try:
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        if result.returncode == 0:
            return jsonify({"status": "success", "message": "📥 تم تحميل الفيديو بنجاح وحفظه في السيرفر! قم بتحديث الصفحة لاختياره."})
        else:
            return jsonify({"status": "error", "message": f"فشل التحميل من يوتيوب: {result.stderr}"})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
