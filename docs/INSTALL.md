# Instalacja

## Instalacja z WebGUI (zalecana)

1. W WebGUI unRAID otwórz **Plugins → Install Plugin**.
2. Wklej adres pliku `.plg`:

   ```
   https://raw.githubusercontent.com/KlimasTech/custom-vm-icons/main/custom-vm-icons.plg
   ```

3. Kliknij **Install**. Instalator:
   - zainstaluje paczkę do `/usr/local/emhttp/plugins/custom-vm-icons/`,
   - utworzy katalogi `/mnt/user/appdata/custom-vm-icons` oraz katalog ikon VM Managera na `/boot`,
   - doda wpis synchronizacji przy starcie do `/boot/config/go`,
   - przywróci brakujące ikony (jeśli tablica jest zamontowana).
4. Gotowe — strona zarządzania: **Settings → Utilities → Custom VM Icons**.

## Instalacja ręczna (krok po kroku)

Jeśli nie chcesz instalować z URL (np. serwer bez dostępu do internetu):

1. Pobierz z [releases](https://github.com/KlimasTech/custom-vm-icons/releases) plik `custom-vm-icons-<wersja>.txz` oraz `custom-vm-icons.plg`.
2. Skopiuj oba pliki na serwer (np. przez SCP):

   ```bash
   scp custom-vm-icons-*.txz custom-vm-icons.plg root@<IP-unRAID>:/boot/config/plugins/custom-vm-icons/
   ```

3. Na serwerze zainstaluj paczkę:

   ```bash
   upgradepkg --install-new /boot/config/plugins/custom-vm-icons/custom-vm-icons-*.txz
   ```

4. Utwórz katalogi i zarejestruj sync przy starcie:

   ```bash
   mkdir -p /mnt/user/appdata/custom-vm-icons /boot/config/plugins/dynamix.vm.manager/templates/images
   chmod +x /usr/local/emhttp/plugins/custom-vm-icons/scripts/sync_icons.sh
   grep -q custom-vm-icons /boot/config/go || \
     sed -i '/\/usr\/local\/sbin\/emhttp/i ( /usr/local/emhttp/plugins/custom-vm-icons/scripts/sync_icons.sh --boot 2>\&1 ) \&' /boot/config/go
   ```

5. Odśwież WebGUI — strona pluginu pojawi się w **Settings → Utilities**.

## Użycie

1. **Settings → Utilities → Custom VM Icons**.
2. Wybierz plik PNG/JPG (max 2 MB), opcjonalnie wpisz nazwę (bez polskich znaków — zostaną znormalizowane).
3. Kliknij **Wgraj ikonę** — ikona pojawi się na liście ze statusem „aktywna".
4. Przy tworzeniu/edycji VM kliknij ikonkę domyślną — Twoje ikony będą widoczne obok wbudowanych.

## Aktualizacja

Zainstaluj nową wersję przez **Plugins → Install Plugin** (ten sam URL) — unRAID nadpisze paczkę, ikony w appdata pozostaną nietknięte.

## Usunięcie

**Plugins → Installed Plugins → custom-vm-icons → Remove**. Plugin usunie swój wpis z `/boot/config/go`; wgrane ikony **pozostają** w `/mnt/user/appdata/custom-vm-icons` i w katalogu ikon VM Managera (usuń je ręcznie, jeśli nie są już potrzebne).
