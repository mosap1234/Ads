from flask import Flask, render_template, request, jsonify, session, Response, stream_with_context
import subprocess
import os
import signal
import sys

app = Flask(__name__)
app.secret_key = os.environ.get('FLASK_SECRET_KEY', 'super-secret-key-12345')

VIDEO_DIR = "/app/videos"
os.makedirs(VIDEO_DIR, exist_ok=True)
ffmpeg_process = None

# جلب كلمة المرور من متغيرات البيئة في Railway
ADMIN_PASSWORD = os.environ.get('ADMIN_PASSWORD', '')

def get_video_meta(filename):
    """جلب حجم ومدّة الفيديو باستخدام ffprobe"""
    path = os.path.join(VIDEO_DIR, filename)
    if not os.path.exists(path):
        return {"size": "0 MB", "duration": "00:00"}
    
    # حساب الحجم
    size_mb = round(os.path.getsize(path) / (1024 * 1024), 1)
    
    # حساب المدة عبر ffprobe
    try:
        cmd = f'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "{path}"'
        duration_sec = float(subprocess.check_output(cmd, shell=True).decode().strip())
        mins, secs = divmod(int(duration_sec), 60)
        hrs, mins = divmod(mins, 60)
        duration_str = f"{hrs:02d}:{mins:02d}:{secs:02d}" if hrs else f"{mins:02d}:{secs:02d}"
    except:
        duration_str = "--:--"
        
    return {"size": f"{size_mb} MB", "duration": duration_str}

@app.route('/')
def index():
    # التحقق من كلمة السر إذا كانت مفعّلة في السيرفر
    if ADMIN_PASSWORD and not session.get('logged_in'):
        return render_template('index.html', login_required=True)
        
    raw_videos = [f for f in os.listdir(VIDEO_DIR) if f.endswith(('.mp4', '.mkv', '.avi', '.mov'))]
    videos_with_meta = []
    for v in raw_videos:
        meta = get_video_meta(v)
        videos_with_meta.append({
            "name": v,
            "size": meta["size"],
            "duration": meta["duration"]
        })
        
    return render_template('index.html', videos=videos_with_meta, streaming=ffmpeg_process is not None, login_required=False)

@app.route('/login', methods=['POST'])
def login():
    password = request.form.get('password')
    if password == ADMIN_PASSWORD:
        session['logged_in'] = True
        return jsonify({"status": "success"})
    return jsonify({"status": "error", "message": "كلمة المرور غير صحيحة!"})

@app.route('/logout')
def logout():
    session.pop('logged_in', None)
    return redirect('/')

@app.route('/start', methods=['POST'])
def start_stream():
    global ffmpeg_process
    if ffmpeg_process is not None:
        return jsonify({"status": "error", "message": "البث يعمل بالفعل حالياً!"})

    stream_key = request.form.get('stream_key')
    video_file = request.form.get('video_file')
    loop_enabled = request.form.get('loop') == 'true' # استقبال إعداد التكرار من الواجهة

    if not stream_key or not video_file:
        return jsonify({"status": "error", "message": "الرجاء إدخال مفتاح البث واختيار ملف الفيديو."})

    video_path = os.path.join(VIDEO_DIR, video_file)
    rtmp_url = f"rtmp://a.rtmp.youtube.com/live2/{stream_key}"

    # ضبط أمر التكرار بناءً على رغبة المستخدم
    loop_flag = "-stream_loop -1 " if loop_enabled else ""
    cmd = f'ffmpeg -re {loop_flag}-i "{video_path}" -c:v copy -c:a copy -f flv "{rtmp_url}"'

    try:
        ffmpeg_process = subprocess.Popen(cmd, shell=True, preexec_fn=os.setsid)
        return jsonify({"status": "success", "message": "🚀 تم بدء البث بنجاح!"})
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

@app.route('/delete', methods=['POST'])
def delete_video():
    if ADMIN_PASSWORD and not session.get('logged_in'):
        return jsonify({"status": "error", "message": "غير مصرح لك"})
        
    video_file = request.form.get('video_file')
    if not video_file:
        return jsonify({"status": "error", "message": "اسم الملف غير صحيح."})
        
    try:
        path = os.path.join(VIDEO_DIR, video_file)
        if os.path.exists(path):
            os.remove(path)
            return jsonify({"status": "success", "message": "🗑️ تم حذف الملف بنجاح!"})
        return jsonify({"status": "error", "message": "الملف غير موجود أصلاً."})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})

@app.route('/download_progress')
def download_progress():
    """بث حي ومباشر لعملية التحميل سطر بسطر للشاشة عبر Server-Sent Events"""
    youtube_url = request.args.get('url')
    if not youtube_url:
        return Response("data: خطأ: الرابط فارغ\n\n", mimetype='text/event-stream')

    cookies_path = "/app/cookies.txt"
    cookies_flag = f'--cookies "{cookies_path}"' if os.path.exists(cookies_path) else ''

    # تشغيل التحميل مع تفعيل خيار خروج الأسطر الحية --newline
    cmd = f'yt-dlp {cookies_flag} --js-runtimes node --remote-components ejs:github --newline -P "{VIDEO_DIR}" -f "best[ext=mp4]/best" "{youtube_url}"'

    def generate():
        process = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
        for line in iter(process.stdout.readline, ''):
            if line:
                yield f"data: {line.strip()}\n\n"
        process.stdout.close()
        return_code = process.wait()
        if return_code == 0:
            yield "data: [DONE] تم التحميل بنجاح واكتملت العملية!\n\n"
        else:
            yield "data: [ERROR] فشل التحميل، تأكد من حماية الرابط أو الكوكيز.\n\n"

    return Response(stream_with_context(generate()), mimetype='text/event-stream')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
