#!/bin/bash
# custom-vm-icons/scripts/sync_icons.sh
# Synchronizuje ikony VM: /mnt/user/appdata/custom-vm-icons (trwale) -> /boot/.../templates/images
# --boot : tryb startu systemu - czeka az tablica/appdata bedzie zamontowana, potem przywraca brakujace.
APPDATA="/mnt/user/appdata/custom-vm-icons"
BOOTDIR="/boot/config/plugins/dynamix.vm.manager/templates/images"
LOG_TAG="custom-vm-icons"

mkdir -p "$BOOTDIR"

if [ "$1" = "--boot" ]; then
  # czekaj max ~5 min na montaz /mnt/user (appdata)
  i=0
  while [ $i -lt 60 ]; do
    if mountpoint -q /mnt/user 2>/dev/null && [ -d "$APPDATA" ]; then break; fi
    sleep 5
    i=$((i+1))
  done
fi

if [ ! -d "$APPDATA" ]; then
  [ "$1" = "--boot" ] && logger -t "$LOG_TAG" "katalog $APPDATA niedostepny - pomijam synchronizacje"
  exit 0
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
