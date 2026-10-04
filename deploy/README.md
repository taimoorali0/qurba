# Qurba deployment (Ubuntu + Nginx + MySQL; main domain or subdomain, Let's Encrypt or Cloudflare)

## 0. Code
The code lives on GitHub (`taimoorali0/qurba`). Keep the repository **private**. `.env`, `vendor`, `node_modules`, `storage/app` and uploaded audio are never uploaded.

## Shared server with a Cloudflare Tunnel (other projects already running)
Use this instead of steps 1–3 when the server already hosts other sites published through a Cloudflare Tunnel.
It does not touch the firewall, other sites, the default PHP or Node, and reuses the existing MySQL/MariaDB.
```
scp -r E:\laragon\www\qurba\deploy root@SERVER-IP:/root/qurba-setup
ssh root@SERVER-IP
bash /root/qurba-setup/server-setup-shared.sh
```
It asks for the subdomain and a free local port (default 8090), then prints: the GitHub deploy key, the database
password, the first-deploy commands, and exactly what to add to your tunnel (config file or Zero Trust dashboard).
Delete any old A record for the subdomain; the tunnel uses its own CNAME.
Later updates: `sudo -u qurba PHP_BIN=php8.3 bash /var/www/qurba/deploy/deploy.sh`

## 1. DNS for your subdomain (do this first)
At your domain's DNS provider add an **A record**: name `qurba` (or whatever you like) → your server's IPv4 address.
That gives `qurba.yourdomain.com`. Wait until `ping qurba.yourdomain.com` shows the server IP (usually a few minutes).
Using Cloudflare? Add the same record there; orange cloud on = proxied.

## 2. Server setup (once, as root, Ubuntu 22.04 / 24.04)
Copy the `deploy` folder to the server from your laptop (works for a private repo), then run the setup:
```
scp -r E:\laragon\www\qurba\deploy root@SERVER-IP:/root/qurba-setup
ssh root@SERVER-IP
bash /root/qurba-setup/server-setup.sh
```
It asks for your **subdomain**, your GitHub repo SSH URL, and whether you use **Cloudflare**:
- **No Cloudflare (simplest):** it installs a free **Let's Encrypt** certificate and turns on HTTPS automatically (DNS must already point to the server).
- **Cloudflare:** put a Cloudflare Origin Certificate in `/etc/ssl/qurba/origin.pem` / `origin.key`, SSL mode **Full (strict)**.

It also installs Nginx, PHP 8.3, MySQL, Composer, Node 20, firewall, fail2ban, automatic security updates, the queue worker and scheduler,
and prints a **GitHub deploy key** and the **database password** (save it).

## 3. First deploy (as qurba)
Add the printed deploy key in GitHub → repo → Settings → Deploy keys (read-only). Then run the commands the setup printed:
```
sudo -u qurba git clone git@github.com:YOU/qurba.git /var/www/qurba
sudo -u qurba cp /var/www/qurba/deploy/env.production.example /var/www/qurba/.env
sudo -u qurba sed -i "s/__DOMAIN__/qurba.yourdomain.com/g; s/^DB_PASSWORD=.*/DB_PASSWORD=THE-PRINTED-PASSWORD/" /var/www/qurba/.env
sudo -u qurba bash /var/www/qurba/deploy/deploy.sh --first
sudo -u qurba bash /var/www/qurba/deploy/import-content.sh
```
Allow the queue restart without a password:
`echo "qurba ALL=NOPASSWD: /bin/systemctl restart qurba-queue" | sudo tee /etc/sudoers.d/qurba`

## 4. Admin
Register on the site, then: `sudo -u qurba php /var/www/qurba/artisan qurba:make-admin you@email.com`
Sign in at `/admin` and finish 2FA. Approve sources only after licences are confirmed.
Then in `/admin`: approve the tafsir and duas, and upload audio (adhan, 99 Names voices) in **Audio library** — uploads up to 20 MB.

## 5. Backups
Create `/home/qurba/.qurba-backup.env` (chmod 600) with `BACKUP_PASSPHRASE=...` (and optional `RCLONE_REMOTE=...`), keep the passphrase somewhere **off** the server, then add the cron line from `deploy/backup.sh`.

## 6. Every update
Laptop: `git add . && git commit -m "..." && git push`
Server: `sudo -u qurba bash /var/www/qurba/deploy/deploy.sh`

## 7. Before public launch
`sudo -u qurba php /var/www/qurba/artisan qurba:security-check` must show **Ready for launch**.
