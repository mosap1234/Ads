FROM python:3.10-slim

# تثبيت ffmpeg وأدوات النظام الأساسية
RUN apt-get update && apt-get install -y ffmpeg curl && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# تثبيت الفلاسك وأداة تحميل اليوتيوب yt-dlp
RUN pip install flask yt-dlp

# إنشاء مجلدات القوالب والفيديوهات داخل الحاوية
RUN mkdir -p /app/templates /app/videos

# نسخ ملفات المشروع داخل الحاوية
COPY app.py /app/app.py
COPY templates/index.html /app/index.html

# فتح بورت الويب الخاص باللوحة
EXPOSE 8080

CMD ["python", "app.py"]
