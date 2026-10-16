#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# One-time server bootstrap for a fresh Ubuntu 22.04 / 24.04 VM
# (e.g. Oracle Cloud "Always Free", Google Cloud e2-micro free tier, etc.)
#
# Run ON THE SERVER as root (or with sudo):
#   sudo bash bootstrap-server.sh
# ---------------------------------------------------------------------------
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
	echo "Please run with sudo:  sudo bash bootstrap-server.sh" >&2
	exit 1
fi

echo "==> Installing base packages"
apt-get update
apt-get install -y ca-certificates curl git ufw

echo "==> Installing Docker Engine + Compose plugin"
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
	> /etc/apt/sources.list.d/docker.list
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable --now docker

echo "==> Opening firewall for web traffic (80/443)"
if command -v ufw >/dev/null 2>&1; then
	ufw allow OpenSSH || true
	ufw allow 80/tcp || true
	ufw allow 443/tcp || true
	ufw --force enable || true
fi

echo
echo "Done. Docker version:"
docker --version
echo
echo "Next steps:"
echo "  1. git clone <your repo> ethiosis && cd ethiosis"
echo "  2. cd deploy && cp .env.example .env && nano .env   # set SITE_ADDRESS + passwords"
echo "  3. docker compose up -d --build"
echo "  4. Open https://<SITE_ADDRESS>/InstallDatabase.php  (then /index.php, admin/admin)"
