# custom-vm-icons

Plugin do unRAID, który pozwala wgrywać **własne ikony maszyn wirtualnych** i pokazywać je w natywnym oknie wyboru ikony VM Managera — dokładnie tam, gdzie wbudowane ikony Windows/Linux. Powstał, bo unRAID nie daje żadnego interfejsu na dodanie własnej grafiki do VM, a ręczne kopiowanie plików na pendrive'a bootowego nie przeżywa porządków i nie ma podglądu.

Wgrane ikony żyją w trwałym katalogu `/mnt/user/appdata/custom-vm-icons` (przeżywają restart serwera), a przy każdym starcie systemu plugin automatycznie przywraca je do katalogu ikon VM Managera. Wszystko zarządzane ze strony w WebGUI.

## Szybki start

W WebGUI unRAID: **Plugins → Install Plugin** i wklej:

```
https://raw.githubusercontent.com/KlimasTech/custom-vm-icons/main/custom-vm-icons.plg
```

Po instalacji: **Settings → Utilities → Custom VM Icons** → wybierz plik PNG/JPG → **Wgraj ikonę**. Ikona od razu pojawi się przy tworzeniu/edycji VM (kliknij ikonkę domyślną przy edycji maszyny).

## Jak to działa

| Element | Ścieżka | Rola |
|---|---|---|
| Master copy ikon | `/mnt/user/appdata/custom-vm-icons/` | trwałe przechowywanie (udział appdata, przeżywa restart) |
| Live copy ikon | `/boot/config/plugins/dynamix.vm.manager/templates/images/` | stąd unRAID czyta ikony do pickera VM (natywny mechanizm, bez patchowania systemu) |
| Sync przy starcie | wpis w `/boot/config/go` → `scripts/sync_icons.sh --boot` | po zamontowaniu tablicy przywraca brakujące ikony z appdata do katalogu VM Managera |
| Strona ustawień | `Settings/CustomVmIcons` | upload, podgląd, usuwanie, ręczna synchronizacja |

Upload obsługuje PNG i JPG (max 2 MB); obraz jest automatycznie konwertowany przez GD do PNG 48×48 z przezroczystym tłem. Tryb dopasowania: `crop` (kadrowanie ze środka) albo `contain` (cała grafika mieści się w kwadracie — domyślne dla szerokich logotypów).

## Funkcje

- Wgrywanie ikon PNG/JPG z automatyczną normalizacją do 48×48
- Trwałe przechowywanie w appdata + automatyczny sync do VM Managera
- Automatyczne przywracanie brakujących ikon przy starcie systemu
- Podgląd zainstalowanych ikon, status synchronizacji, usuwanie (ikonka kosza)
- Sanityzacja nazw plików („Moja Ikona PROXMOX.png" → `moja-ikona-proxmox.png`)
- Endpoint tylko dla roota, CSRF wg standardów WebGUI (nagłówek `X-Csrf-Token`)

## Dokumentacja

| Plik | Zawartość |
|---|---|
| [docs/INSTALL.md](docs/INSTALL.md) | Instalacja (GUI i ręczna), aktualizacja, usunięcie |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Architektura, przepływ danych, decyzje projektowe |
| [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) | Diagnostyka problemów |

## Wymagania

- unRAID 6.9+ (testowane na 7.3.2)
- działający VM Manager (dynamix.vm.manager)
- udział appdata (tablica musi być zamontowana przy wgrywaniu)

## Licencja

MIT — zobacz [LICENSE](LICENSE).
