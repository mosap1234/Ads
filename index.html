<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم بث اليوتيوب 24/7</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { background-color: #1e1e1e; border: 1px solid #333; border-radius: 12px; }
        .form-control, .form-select { background-color: #2a2a2a; border: 1px solid #444; color: #fff; }
        .form-control:focus, .form-select:focus { background-color: #333; color: #fff; border-color: #0d6efd; box-shadow: none; }
        .status-banner { border-radius: 8px; padding: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- كارد الحالة -->
                <div class="card shadow p-4 mb-4 text-center">
                    <h2 class="mb-3">🖥️ لوحة تحكم البث المتواصل 24/7</h2>
                    <p class="text-muted">تحكم ببث قنواتك وحمل فيديوهاتك مباشرة عبر السحاب</p>
                    <div id="statusBox" class="status-banner {% if streaming %}bg-success text-white{% else %}bg-danger text-white{% endif %} mb-3">
                        {% if streaming %} ● البث يعمل الآن حالياً {% else %} ○ البث متوقف حالياً {% endif %}
                    </div>
                </div>

                <!-- كارد تحميل فيديوهات من يوتيوب -->
                <div class="card shadow p-4 mb-4">
                    <h4 class="mb-3">📥 تحميل فيديو جديد من يوتيوب</h4>
                    <form id="downloadForm">
                        <div class="input-group">
                            <input type="url" class="form-control" name="youtube_url" placeholder="ضع رابط فيديو اليوتيوب هنا..." required>
                            <button type="button" id="btnDownload" onclick="actionDownload()" class="btn btn-success px-4">بدء التحميل</button>
                        </div>
                    </form>
                </div>

                <!-- كارد التحكم بالبث -->
                <div class="card shadow p-4">
                    <h4 class="mb-3">🚀 إعدادات البث المباشر</h4>
                    <form id="streamForm">
                        <div class="mb-3">
                            <label class="form-label">🔑 مفتاح بث يوتيوب (Stream Key):</label>
                            <input type="password" class="form-control" name="stream_key" placeholder="أدخل مفتاح البث هنا..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">🎬 اختر الفيديو للبث:</label>
                            <select class="form-select" name="video_file" required>
                                <option value="">-- اختر من الفيديوهات المرفوعة --</option>
                                {% for video in videos %}
                                    <option value="{{ video }}">{{ video }}</option>
                                {% endfor %}
                            </select>
                            {% if not videos %}
                                <small class="text-warning d-block mt-1">⚠️ لا توجد فيديوهات في المجلد حالياً. ضع رابطاً بالأعلى وحمله.</small>
                            {% endif %}
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                            <button type="button" onclick="actionStream('/start')" class="btn btn-primary btn-lg px-4" {% if streaming %}disabled{% endif %}>🚀 بدء البث</button>
                            <button type="button" onclick="actionStream('/stop')" class="btn btn-danger btn-lg px-4" {% if not streaming %}disabled{% endif %}>🛑 إيقاف البث</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        function actionStream(endpoint) {
            const form = document.getElementById('streamForm');
            const formData = new FormData(form);

            fetch(endpoint, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status !== "error") location.reload();
            })
            .catch(err => alert("حدث خطأ في الاتصال بالسيرفر."));
        }

        function actionDownload() {
            const form = document.getElementById('downloadForm');
            const btn = document.getElementById('btnDownload');
            const formData = new FormData(form);

            // تغيير شكل الزر ليوضح للمشاهد أن التحميل جاري
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> جاري التحميل...`;

            fetch('/download', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                // إعادة الزر لوضعه الطبيعي وتحديث الصفحة لظهور الفيديو الجديد في القائمة
                btn.disabled = false;
                btn.innerText = "بدء التحميل";
                if (data.status === "success") location.reload();
            })
            .catch(err => {
                alert("حدث خطأ أثناء التحميل.");
                btn.disabled = false;
                btn.innerText = "بدء التحميل";
            });
        }
    </script>
</body>
</html>
