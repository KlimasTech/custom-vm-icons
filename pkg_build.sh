#!/bin/bash
# Buduje paczkę (.txz) z zawartości source/ dla pluginu custom-vm-icons.
# Uruchamiać w katalogu repo (na unRAID lub dowolnym Linuksie z tar+xz).
set -e

VERSION="${1:?Podaj wersję, np. ./pkg_build.sh 2026.10.28}"
NAME="custom-vm-icons"
BUILD_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$BUILD_DIR/source"
OUT_DIR="$BUILD_DIR/releases"
PKG_FILE="$OUT_DIR/${NAME}-${VERSION}.txz"

mkdir -p "$OUT_DIR"

if [ ! -d "$SRC_DIR/usr/local/emhttp/plugins/$NAME" ]; then
    echo "Brak $SRC_DIR/usr/local/emhttp/plugins/$NAME" >&2
    exit 1
fi

cd "$SRC_DIR"
tar cJf "$PKG_FILE" usr/

MD5=$(md5sum "$PKG_FILE" | awk '{print $1}')
echo ""
echo "Zbudowano: $PKG_FILE"
echo "MD5: $MD5"
echo ""
echo "Wklej powyższe MD5 do pliku ${NAME}.plg -> <!ENTITY packageMD5 \"$MD5\">"
