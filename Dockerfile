FROM alpine:latest

# 1. تثبيت الواجهة، خادم RDP، أدوات الشبكات، وأدوات التطوير الأساسية
RUN apk update && apk add --no-cache \
    xfce4 xfce4-terminal xfce4-settings \
    xrdp xvfb \
    dbus dbus-x11 \
    sudo bash nano vim curl wget git tzdata \
    python3 py3-pip nmap bind-tools \
    font-noto font-noto-arabic \
    setxkbmap \
    && rm -rf /var/cache/apk/*

# 2. إعداد كلمة المرور للمستخدم Root
RUN echo 'root:Mosap@123123' | chpasswd

# 3. إعداد XRDP وتحسين الأداء (تقليل جودة الألوان لضمان السرعة على Railway)
RUN xrdp-keygen xrdp auto && \
    sed -i 's/max_bpp=32/max_bpp=24/g' /etc/xrdp/xrdp.ini && \
    sed -i 's/crypt_level=high/crypt_level=none/g' /etc/xrdp/xrdp.ini && \
    echo "startxfce4" > /root/.xsession && chmod +x /root/.xsession

# 4. كتابة سكربت التشغيل التلقائي (هذا يحل مشكلة الشاشة السوداء وكراش DBus)
RUN echo '#!/bin/sh' > /start.sh && \
    echo 'mkdir -p /var/run/dbus' >> /start.sh && \
    echo 'dbus-uuidgen > /var/lib/dbus/machine-id' >> /start.sh && \
    echo 'dbus-daemon --system' >> /start.sh && \
    echo 'xrdp-sesman' >> /start.sh && \
    echo 'exec xrdp --nodaemon' >> /start.sh && \
    chmod +x /start.sh

# 5. فتح بورت RDP
EXPOSE 3389

# 6. تشغيل السكربت عند بدء الحاوية
CMD ["/start.sh"]
