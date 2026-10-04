#!/usr/bin/env bash
# ===== QURBA: setup on a server that ALREADY hosts other sites, published through a Cloudflare Tunnel =====
# Safe for the other projects: no firewall changes, no default-site removal, the server's default PHP
# and Node stay as they are, an existing MySQL/MariaDB is reused.
# Run as root:  bash server-setup-shared.sh
set -euo pipefail

read -rp "Subdomain for Qurba (e.g. qurba.example.com): " DOMAIN
read -rp "GitHub repo SSH URL [git@github.com:taimoorali0/qurba.git]: " REPO
REPO=${REPO:-git@github.com:taimoorali0/qurba.git}
read -rp "Local port for Qurba (must be free) [8090]: " PORT
PORT=${PORT:-8090}
APP_DIR=/var/www/qurba
APP_USER=qurba
DB_NAME=qurba
DB_USER=qurba_app
DB_PASS=$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-28)
HERE="$(cd "$(dirname "$0")" && pwd)"

if ss -ltn | awk '{print $4}' | grep -qE "[:.]${PORT}$"; then
  echo "!! Port ${PORT} is already in use. Run again and choose another port."; exit 1
fi

export DEBIAN_FRONTEND=noninteractive
echo "==> PHP 8.3 for Qurba only (the server's default php stays the same)"
PREV_PHP=$(readlink -f /etc/alternatives/php 2>/dev/null || true)
apt-get update -y
apt-get install -y software-properties-common curl git unzip
if ! apt-cache show php8.3-fpm >/dev/null 2>&1; then add-apt-repository -y ppa:ondrej/php && apt-get update -y; fi
apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath php8.3-gd
if [ -n "$PREV_PHP" ] && [ -x "$PREV_PHP" ]; then update-alternatives --set php "$PREV_PHP" >/dev/null 2>&1 || true; fi
printf 'upload_max_filesize = 20M\npost_max_size = 25M\n' > /etc/php/8.3/fpm/conf.d/99-qurba.ini
systemctl enable --now php8.3-fpm
if ! command -v composer >/dev/null; then curl -sS https://getcomposer.org/installer | php8.3 -- --install-dir=/usr/local/bin --filename=composer; fi

echo "==> Nginx (installed only if missing; existing sites untouched)"
if ! command -v nginx >/dev/null; then
  apt-get install -y nginx
  # Fresh nginx: its own default site is not needed (the tunnel talks to Qurba's local port)
  rm -f /etc/nginx/sites-enabled/default
fi

echo "==> Database (reusing the existing MySQL/MariaDB if there is one)"
if ! command -v mysql >/dev/null; then apt-get install -y mysql-server; fi
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> App user + folder"
id -u $APP_USER >/dev/null 2>&1 || adduser --disabled-password --gecos "" $APP_USER
usermod -aG www-data $APP_USER
mkdir -p $APP_DIR /var/backups/qurba
chown -R $APP_USER:www-data $APP_DIR /var/backups/qurba
chmod 750 /var/backups/qurba

echo "==> Node 20 for the qurba user only (system Node is not changed)"
sudo -u $APP_USER bash -c 'export NVM_DIR="$HOME/.nvm"; [ -s "$NVM_DIR/nvm.sh" ] || curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash; . "$NVM_DIR/nvm.sh"; nvm install 20 >/dev/null; nvm alias default 20 >/dev/null; node -v'

echo "==> Nginx site on 127.0.0.1:${PORT}"
sed "s/__DOMAIN__/${DOMAIN}/g; s/__PORT__/${PORT}/g" "$HERE/nginx-qurba-tunnel.conf" > /etc/nginx/sites-available/qurba
ln -sf /etc/nginx/sites-available/qurba /etc/nginx/sites-enabled/qurba
nginx -t && systemctl reload nginx

echo "==> Queue worker + scheduler (PHP 8.3)"
sed 's#/usr/bin/php #/usr/bin/php8.3 #' "$HERE/qurba-queue.service" > /etc/systemd/system/qurba-queue.service
systemctl daemon-reload
systemctl enable qurba-queue
( crontab -u $APP_USER -l 2>/dev/null | grep -v 'artisan schedule:run' ; echo "* * * * * cd $APP_DIR && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1" ) | crontab -u $APP_USER -
echo "qurba ALL=NOPASSWD: /bin/systemctl restart qurba-queue" > /etc/sudoers.d/qurba

echo "==> Deploy key for GitHub"
sudo -u $APP_USER bash -c 'mkdir -p ~/.ssh && [ -f ~/.ssh/id_ed25519 ] || ssh-keygen -t ed25519 -N "" -f ~/.ssh/id_ed25519 -q'
sudo -u $APP_USER bash -c 'ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null'

TUNNEL_CFG=$(ls /etc/cloudflared/config.yml /root/.cloudflared/config.yml /home/*/.cloudflared/config.yml 2>/dev/null | head -1 || true)

cat <<DONE

================ QURBA SERVER READY (shared) ================
Qurba answers locally on http://127.0.0.1:${PORT}  (only the tunnel can reach it)

1) GitHub: add this READ-ONLY Deploy key (repo > Settings > Deploy keys):
$(cat /home/$APP_USER/.ssh/id_ed25519.pub)

2) Database (save this, it is shown once):
   DB_DATABASE=${DB_NAME}   DB_USERNAME=${DB_USER}   DB_PASSWORD=${DB_PASS}

3) First deploy:
   sudo -u ${APP_USER} git clone ${REPO} ${APP_DIR}
   sudo -u ${APP_USER} cp ${APP_DIR}/deploy/env.production.example ${APP_DIR}/.env
   sudo -u ${APP_USER} sed -i "s/__DOMAIN__/${DOMAIN}/g; s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" ${APP_DIR}/.env
   sudo -u ${APP_USER} PHP_BIN=php8.3 bash ${APP_DIR}/deploy/deploy.sh --first
   sudo -u ${APP_USER} PHP_BIN=php8.3 bash ${APP_DIR}/deploy/import-content.sh

4) Cloudflare Tunnel: add Qurba to the SAME tunnel as your other projects.
   Tunnel config file found: ${TUNNEL_CFG:-"(not found - see Cloudflare dashboard: Zero Trust > Networks > Tunnels)"}
   - Config file: add this under "ingress:", ABOVE the final "- service: http_status:404" line:
       - hostname: ${DOMAIN}
         service: http://127.0.0.1:${PORT}
     then: systemctl restart cloudflared
   - Dashboard-managed tunnel: Zero Trust > Networks > Tunnels > your tunnel > Public Hostname > Add:
       Subdomain = $(echo "$DOMAIN" | cut -d. -f1), Domain = $(echo "$DOMAIN" | cut -d. -f2-), Service = HTTP  127.0.0.1:${PORT}
   - DNS: delete the old A record for ${DOMAIN}; the tunnel creates its own CNAME
     (config-file tunnels: cloudflared tunnel route dns <TUNNEL-NAME> ${DOMAIN})
=============================================================
DONE
