# Instalacja

## Instalacja z WebGUI (zalecana)

1. W WebGUI unRAID otwórz **Plugins → Install Plugin**.
2. Wklej adres pliku `.plg`:

   ```
   https://raw.githubusercontent.com/KlimasTech/custom-vm-icons/main/custom-vm-icons.plg
   ```

3. Kliknij **Install**. Instalator:
   - zainstaluje paczkę do `/usr/local/emhttp/plugins/custom-vm-icons/`,
   - utworzy katalog ikon VM Managera na `/boot`,
   - zarejestruje synchronizację przy starcie jako zdarzenie emhttp `event/disks_mounted` (bez wpisu w `/boot/config/go`; stary wpis z wersji ≤ 2026.10.29a zostanie usunięty),
   - jeśli tablica działa: utworzy `/mnt/user/appdata/custom-vm-icons` i przywróci brakujące ikony. Przy zatrzymanej tablicy nic nie jest zapisywane pod `/mnt/user` — zrobi to zdarzenie po starcie tablicy.
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

4. Przy uruchomionej tablicy wykonaj pierwszą synchronizację (sync przy kolejnych startach robi zdarzenie `event/disks_mounted` z paczki — nie dopisuj niczego do `/boot/config/go`):

   ```bash
   chmod +x /usr/local/emhttp/plugins/custom-vm-icons/scripts/sync_icons.sh /usr/local/emhttp/plugins/custom-vm-icons/event/disks_mounted
   /usr/local/emhttp/plugins/custom-vm-icons/scripts/sync_icons.sh --boot
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

**Plugins → Installed Plugins → custom-vm-icons → Remove**. Plugin usunie ewentualny stary wpis z `/boot/config/go`; wgrane ikony **pozostają** w `/mnt/user/appdata/custom-vm-icons` i w katalogu ikon VM Managera (usuń je ręcznie, jeśli nie są już potrzebne).
