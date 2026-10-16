# Deploying EthioSIS (alpha & beta)

EthioSIS is a **PHP 8 + MySQL/MariaDB** web application (a RosarioSIS fork). Deploying
it means putting those two things somewhere always reachable, behind HTTPS, with a
hostname you can share.

There are two stages in this guide:

| Stage | What | Cost | Always-on? | Good for |
| --- | --- | --- | --- | --- |
| **1. Tunnel** | Expose the copy already running on your PC | Free | No (your PC must be on) | Alpha today, demos |
| **2. VPS** | Run it on a small cloud server via Docker | Free (Oracle Cloud) | Yes | Beta, real users |

You can start with Stage 1 today and move to Stage 2 when beta needs to be reliable.

---

## Stage 0 — Commit and push your work (required for Stage 2)

A server clones the app from GitHub, so anything uncommitted will be missing there.

```powershell
cd "D:\Media\download\AI\projects\school system\school-management"
git add -A
git commit -m "EthioSIS: current work + deployment kit"
git push origin ethiopia-adaptation
```

---

## Stage 1 — Live in ~5 minutes, free, temporary address

Your PC already runs the stack (`tools\start-stack.ps1`) and already has `cloudflared`,
which creates a public **HTTPS** URL that points at `http://127.0.0.1:8080`.

### 1. Harden before you expose anything

The default login is `admin` / `admin`. Change it **first**:

1. Start the stack: `powershell -ExecutionPolicy Bypass -File tools\start-stack.ps1`
2. Open <http://127.0.0.1:8080/> and log in as `admin` / `admin`.
3. Go to **Users → (your admin account) → change password** and set a strong one.

Also let PHP serve more than one request at a time (its built-in server handles one
request per worker; you need more for several testers). Edit `tools\start-stack.ps1`
and set the workers env var before starting PHP:

```powershell
$env:PHP_CLI_SERVER_WORKERS = "8"
& $php -c $phpIni -S 127.0.0.1:8080 -t $docroot
```

### 2. Open the tunnel

In a **second** terminal (leave the stack running):

```powershell
cloudflared tunnel --url http://127.0.0.1:8080
```

It prints a line like:

```
+--------------------------------------------------------------+
|  https://calm-river-1234.trycloudflare.com                    |
+--------------------------------------------------------------+
```

That URL **is** your live, free, HTTPS address. Share it with alpha testers.

### 3. Caveats (why this is Stage 1 only)

- It only works while your PC is on and the tunnel is running.
- The URL changes every time you restart the tunnel — fine for a handful of invited
  testers, awkward once you share it widely.
- Your home upload speed is the limit.
- **Do not create `deploy/.env` while you are using Stage 1.** PHP's built-in server
  does not read `.htaccess`, so files under `deploy/` would be downloadable through
  the tunnel. `deploy/.env` is only for the Stage 2 Docker deploy.

When those become a problem, do Stage 2.

---

## Stage 2 — Always-on, still free (Oracle Cloud Always Free + Docker)

Oracle Cloud's **Always Free** tier gives a genuine always-on Ubuntu VM at no cost
(the AMD `VM.Standard.E2.1.Micro`, 1 GB RAM, is the easiest to get; the ARM `A1` /
4-core / 24 GB shape is better if you can grab one). Google Cloud's `e2-micro`
always-free instance works the same way. A card is required for signup verification
but is not charged.

Everything you need is in `deploy/`:

```
deploy/
  Dockerfile              # PHP 8.4 + required extensions, builds the app image
  docker-compose.yml      # app + MariaDB + Caddy (automatic HTTPS)
  Caddyfile               # reverse proxy / TLS
  entrypoint.sh           # generates config.inc.php from .env on first boot
  php.ini                 # recommended PHP settings
  .env.example            # copy to .env and edit
  bootstrap-server.sh     # one-time server setup (installs Docker)
  backup.sh               # gzipped mysqldump + rotation
  config.inc.php.example  # only for a manual (non-Docker) deploy
```

### 1. Create the free VM

1. Sign up at <https://www.oracle.com/cloud/free/> and create the free VM
   (Ubuntu 22.04 or 24.04).
2. In Networking → **VCN → Security Lists**, add **Ingress** rules for TCP **80**
   and TCP **443** from `0.0.0.0/0` (Oracle locks everything down by default).
3. Note the VM's **public IP** (e.g. `203.0.113.7`).

### 2. Free hostname (no domain needed)

`sslip.io` maps any name to the IP embedded in it. For IP `203.0.113.7` your hostname
is `203-0-113-7.sslip.io` (dots → dashes). That is the **free temporary address** —
Caddy will get a real Let's Encrypt certificate for it automatically.
(If you later buy a domain, just change `SITE_ADDRESS` and point an `A` record at the IP.)

### 3. Install Docker on the server

```bash
# on the server
sudo apt-get update && sudo apt-get install -y git
git clone https://github.com/joyeking/School-Management.git ethiosis
cd ethiosis
# The repository root IS the application root, so deploy/ is right here.
sudo bash deploy/bootstrap-server.sh
```

### 4. Configure and launch

```bash
cd ~/ethiosis/deploy
cp .env.example .env
nano .env        # set SITE_ADDRESS=203-0-113-7.sslip.io and strong DB passwords
docker compose up -d --build
```

First build takes a few minutes. Then open:

```
https://203-0-113-7.sslip.io/InstallDatabase.php
```

- This creates the database schema. Run it **once**.
- Then go to `https://203-0-113-7.sslip.io/index.php` and log in `admin` / `admin`.
- **Immediately** change the admin password and delete/rename the extra template users.

Check the app with `https://203-0-113-7.sslip.io/diagnostic.php` (as admin) — it should
report *"Your RosarioSIS installation is properly configured."*

### 5. Optional: load demo data

To give testers something to explore (Grade 9 courses + six students + schedules):

```bash
cd ~/ethiosis/deploy
docker compose exec -T db mariadb -uroot -p"$DB_ROOT_PASSWORD" rosariosis < sample-data.sql
```

Demo student logins are `abebe`, `tigist`, `dawit`, `hanna`, `yonas`, `selam` — all with
the password `student`.

### 6. Backups

```bash
cd ~/ethiosis/deploy
crontab -e
# add:
30 2 * * * cd /home/ubuntu/ethiosis/deploy && bash backup.sh >> backup.log 2>&1
```

Backups land in `deploy/backups/` (last 14 kept). Copy them off the server
(e.g. `scp`) now and then — a backup on the same disk is not a real backup.

### 7. Updating the app later

```bash
cd ~/ethiosis
git pull
cd deploy
docker compose up -d --build
```

---

## Hardening checklist (do before inviting beta users)

- [ ] Changed the `admin` password; removed/renamed unused template users.
- [ ] `DB_PASSWORD` and `DB_ROOT_PASSWORD` are long and random (never blank, never `root`).
- [ ] Site is served over **HTTPS only** (Stage 2: Caddy does this; it also redirects HTTP→HTTPS).
- [ ] MariaDB is **not** reachable from the internet (default in `docker-compose.yml` — it is not published).
- [ ] `ROSARIO_NOTIFY_ADDRESS` / `ROSARIO_ERRORS_ADDRESS` set so you hear about failures.
- [ ] Automatic backups running and copied off-box.
- [ ] Keep `deploy/.env` and `config.inc.php` out of git (already git-ignored).

## Inviting alpha and beta users

With **one instance** (your choice), access is controlled by accounts:

1. Log in as admin → **Users → Add a User** (or **Students → Add**).
2. Pick the right **profile**: `admin`, `teacher`, `parent`, or `student`.
   Profiles determine exactly what each person can see and do.
3. Give testers their username + a temporary password to change on first login.
4. Keep a short list of who is in alpha vs. beta so you can disable an account if
   something goes wrong. (When the group grows, split into two separate deployments —
   `alpha.` and `app.` subdomains with separate databases.)

## Known limits of this deployment

- **Ethiopian calendar is display-only** — dates are entered/stored as Gregorian
  (see `docs/ETHIOPIA.md`).
- **No PDF reports** unless you install `wkhtmltopdf` and set `$wkhtmltopdfPath`;
  otherwise reports render as HTML.
- **Email** relies on PHP `mail()`; add an SMTP plugin for reliable delivery.
- The Docker image has been written to match `diagnostic.php`'s requirements but has
  **not** been built in this environment — verify with `docker compose up --build`
  and a green `diagnostic.php` before sharing the link.
