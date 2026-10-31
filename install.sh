#!/bin/bash
# Installs the Hestia "tile layout + Glass theme" on a server.
# Usage (as root):  bash install.sh [--set-theme] [--force]
#   --set-theme  make Glass the server default theme (otherwise choose it per user)
#   --force      install even if the Hestia version differs from the one this bundle was built for
set -euo pipefail

BUILT_FOR="1.10.5"
HESTIA=/usr/local/hestia
WEB=$HESTIA/web
SET_THEME=0
FORCE=0
for a in "$@"; do
	case "$a" in
		--set-theme) SET_THEME=1 ;;
		--force) FORCE=1 ;;
		*) echo "unknown option: $a"; exit 1 ;;
	esac
done

[ "$(id -u)" -eq 0 ] || { echo "Run as root (sudo bash install.sh)"; exit 1; }
[ -x "$HESTIA/bin/v-list-users" ] || { echo "Hestia Control Panel not found in $HESTIA"; exit 1; }

VER="$(grep -E "^VERSION=" "$HESTIA/conf/hestia.conf" | cut -d"'" -f2)"
if [ "$VER" != "$BUILT_FOR" ] && [ "$FORCE" -ne 1 ]; then
	echo "Hestia $VER is installed, this bundle was built for $BUILT_FOR."
	echo "Re-run with --force to install anyway (check the result afterwards)."
	exit 1
fi

HERE="$(cd "$(dirname "$0")/files" && pwd)"
BACKUP="/root/hestia-glass-backup-$(date +%Y%m%d-%H%M%S).tar.gz"
echo "[1/4] Backing up original web files to $BACKUP"
tar -czf "$BACKUP" -C "$HESTIA" web/index.php web/templates web/css 2>/dev/null || true

echo "[2/4] Copying layout and theme files"
cd "$HERE"
find . -type f | while read -r f; do
	install -D -m 644 -o root -g root "$f" "$WEB/$f"
done

echo "[3/4] Checking PHP syntax"
for f in inc/nav.php templates/includes/panel.php templates/includes/sidebar.php templates/pages/home.php home/index.php index.php templates/includes/css.php; do
	php -l "$WEB/$f" >/dev/null || { echo "PHP error in $f, restoring backup"; tar -xzf "$BACKUP" -C "$HESTIA"; exit 1; }
done

echo "[4/4] Theme"
if [ "$SET_THEME" -eq 1 ]; then
	"$HESTIA/bin/v-change-sys-config-value" THEME glass
	echo "Glass set as the server default theme."
else
	echo "Glass installed. Select it per user (Edit user > Theme) or re-run with --set-theme."
fi
echo "Done. Open the panel and check /home/. To undo: tar -xzf $BACKUP -C $HESTIA"
