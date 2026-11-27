#!/bin/bash
# custom-vm-icons/scripts/sync_icons.sh
# Synchronizuje ikony VM: /mnt/user/appdata/custom-vm-icons (trwale) -> /boot/.../templates/images
# --boot : tryb startu systemu (wywolywany z event/disks_mounted) - przywraca tylko brakujace.
#
# Nic nie jest tworzone pod /mnt/user, dopoki /mnt/user nie jest zamontowane (shfs).
# Wczesniej katalog powstawal na rootfs (RAM) przed startem tablicy, przez co /mnt/user
# nie byl pusty, gdy emhttp montowal na nim shfs.
APPDATA="/mnt/user/appdata/custom-vm-icons"
BOOTDIR="/boot/config/plugins/dynamix.vm.manager/templates/images"
LOG_TAG="custom-vm-icons"
# domyslne ikony dolaczone do pluginu (seedowane do APPDATA przy instalacji,
# tylko gdy uzytkownik nie wgral wlasnych o tej samej nazwie)
SCRIPTDIR="$(dirname "$0")"
DEFAULTS="$SCRIPTDIR/../defaults"

if ! mountpoint -q /mnt/user 2>/dev/null; then
  logger -t "$LOG_TAG" "/mnt/user nie jest zamontowane - pomijam synchronizacje"
  exit 0
fi

mkdir -p "$APPDATA" "$BOOTDIR"

# seed domyslnych ikon do katalogu trwalego (nie nadpisuj wlasnych)
if [ -d "$DEFAULTS" ]; then
  for d in "$DEFAULTS"/*.png; do
    [ -f "$d" ] || continue
    name="$(basename "$d")"
    [ -f "$APPDATA/$name" ] || cp -f "$d" "$APPDATA/$name"
  done
fi

copied=0
for f in "$APPDATA"/*.png; do
  [ -f "$f" ] || continue
  name="$(basename "$f")"
  if [ ! -f "$BOOTDIR/$name" ]; then
    if cp -f "$f" "$BOOTDIR/$name"; then
      copied=$((copied+1))
      logger -t "$LOG_TAG" "przywrocono ikone: $name"
    fi
  fi
done

[ "$copied" -gt 0 ] && logger -t "$LOG_TAG" "synchronizacja startowa: przywrocono $copied ikon"
exit 0
