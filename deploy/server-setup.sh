#!/usr/bin/env bash
# ===== QURBA: one-time Ubuntu server setup (22.04 / 24.04) =====
# Run as root:  sudo bash deploy/server-setup.sh
set -euo pipefail

read -rp "Domain for Qurba (e.g. qurba.app): " DOMAIN
read -rp "GitHub repo SSH URL (e.g. git@github.com:you/qurba.git): " REPO
APP_DIR=/var/www/qurba
APP_USER=qurba
DB_NAME=qurba
DB_USER=qurba_app
DB_PASS=$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-28)

echo "==> Packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y software-properties-common curl git unzip ufw fail2ban unattended-upgrades nginx mysql-server
if ! apt-cache show php8.3-fpm >/dev/null 2>&1; then add-apt-repository -y ppa:ondrej/php && apt-get update -y; fi
apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath php8.3-gd
# Audio uploads in /admin → Audio library (nginx allows 20 MB too)
printf 'upload_max_filesize = 20M\npost_max_size = 25M\n' > /etc/php/8.3/fpm/conf.d/99-qurba.ini
if ! command -v composer >/dev/null; then curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; fi
if ! command -v node >/dev/null || [ "$(node -v | cut -c2- | cut -d. -f1)" -lt 20 ]; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs
fi

echo "==> Automatic security updates + firewall"
dpkg-reconfigure -f noninteractive unattended-upgrades
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
systemctl enable --now fail2ban

echo "==> App user + folder"
id -u $APP_USER >/dev/null 2>&1 || adduser --disabled-password --gecos "" $APP_USER
usermod -aG www-data $APP_USER
mkdir -p $APP_DIR /var/backups/qurba
chown -R $APP_USER:www-data $APP_DIR /var/backups/qurba
chmod 750 /var/backups/qurba

echo "==> MySQL database + least-privilege user"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> Cloudflare real visitor IPs"
{
  for ip in $(curl -fsS https://www.cloudflare.com/ips-v4) $(curl -fsS https://www.cloudflare.com/ips-v6); do echo "set_real_ip_from $ip;"; done
  echo "real_ip_header CF-Connecting-IP;"
} > /etc/nginx/conf.d/cloudflare-realip.conf

echo "==> Nginx site"
mkdir -p /etc/ssl/qurba
sed "s/__DOMAIN__/${DOMAIN}/g" "$(dirname "$0")/nginx-qurba.conf" > /etc/nginx/sites-available/qurba
ln -sf /etc/nginx/sites-available/qurba /etc/nginx/sites-enabled/qurba
rm -f /etc/nginx/sites-enabled/default

echo "==> Queue worker + scheduler"
cp "$(dirname "$0")/qurba-queue.service" /etc/systemd/system/qurba-queue.service
systemctl daemon-reload
systemctl enable qurba-queue
( crontab -u $APP_USER -l 2>/dev/null | grep -v 'artisan schedule:run' ; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1" ) | crontab -u $APP_USER -

echo "==> Deploy key for GitHub"
sudo -u $APP_USER bash -c 'mkdir -p ~/.ssh && [ -f ~/.ssh/id_ed25519 ] || ssh-keygen -t ed25519 -N "" -f ~/.ssh/id_ed25519 -q'
sudo -u $APP_USER bash -c 'ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null'

cat <<DONE

================ SERVER READY ================
1) Add this as a READ-ONLY Deploy key in GitHub (repo > Settings > Deploy keys):
$(cat /home/$APP_USER/.ssh/id_ed25519.pub)

2) Put your Cloudflare Origin Certificate here (Cloudflare > SSL/TLS > Origin Server):
   /etc/ssl/qurba/origin.pem   and   /etc/ssl/qurba/origin.key
   Then:  nginx -t && systemctl reload nginx
   Cloudflare SSL mode: Full (strict)

3) Database (save this, it is shown once):
   DB_DATABASE=${DB_NAME}
   DB_USERNAME=${DB_USER}
   DB_PASSWORD=${DB_PASS}

4) First deploy:
   sudo -u ${APP_USER} git clone ${REPO} ${APP_DIR}
   sudo -u ${APP_USER} cp ${APP_DIR}/deploy/env.production.example ${APP_DIR}/.env   (then edit: domain + DB password)
   sudo -u ${APP_USER} bash ${APP_DIR}/deploy/deploy.sh --first
==============================================
DONE
