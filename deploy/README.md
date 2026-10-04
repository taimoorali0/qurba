# Qurba deployment (Ubuntu + Nginx + MySQL + Cloudflare)

## 0. Push the code to GitHub (from your laptop, once)
```
cd E:\laragon\www\qurba
git init
git add .
git commit -m "Qurba Phase 1"
git branch -M main
git remote add origin git@github.com:YOU/qurba.git
git push -u origin main
```
Make the repository **private**. `.env`, `vendor`, `node_modules` and `storage/app` are not uploaded.

## 1. Server setup (once, as root)
Copy the `deploy` folder to the server (or clone first), then:
```
sudo bash deploy/server-setup.sh
```
It installs Nginx, PHP 8.3, MySQL, Composer, Node 20, firewall, fail2ban and automatic security updates; creates the `qurba` user, a least-privilege database user, the queue worker and the scheduler; and prints a GitHub deploy key and the database password.

## 2. Cloudflare
- DNS: `A` record for the domain → server IP, **proxied** (orange cloud). Same for `www`.
- SSL/TLS → **Full (strict)**. Origin Server → Create certificate → save as `/etc/ssl/qurba/origin.pem` and `/etc/ssl/qurba/origin.key` → `nginx -t && systemctl reload nginx`.
- SSL/TLS → Edge Certificates → **Always Use HTTPS** on.

## 3. First deploy (as qurba)
```
sudo -u qurba git clone git@github.com:YOU/qurba.git /var/www/qurba
sudo -u qurba cp /var/www/qurba/deploy/env.production.example /var/www/qurba/.env
sudo -u qurba nano /var/www/qurba/.env          # domain + DB password
sudo -u qurba bash /var/www/qurba/deploy/deploy.sh --first
sudo -u qurba bash /var/www/qurba/deploy/import-content.sh
```
Allow the queue restart without a password:
`echo "qurba ALL=NOPASSWD: /bin/systemctl restart qurba-queue" | sudo tee /etc/sudoers.d/qurba`

## 4. Admin
Register on the site, then: `sudo -u qurba php /var/www/qurba/artisan qurba:make-admin you@email.com`
Sign in at `/admin` and finish 2FA. Approve sources only after licences are confirmed.

## 5. Backups
Create `/home/qurba/.qurba-backup.env` (chmod 600) with `BACKUP_PASSPHRASE=...` (and optional `RCLONE_REMOTE=...`), keep the passphrase somewhere **off** the server, then add the cron line from `deploy/backup.sh`.

## 6. Every update
Laptop: `git add . && git commit -m "..." && git push`
Server: `sudo -u qurba bash /var/www/qurba/deploy/deploy.sh`

## 7. Before public launch
`sudo -u qurba php /var/www/qurba/artisan qurba:security-check` must show **Ready for launch**.
