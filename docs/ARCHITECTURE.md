# Architektura

## Przepływ danych

```
[Przeglądarka: Settings/CustomVmIcons]
        |  upload: PNG/JPG -> FileReader -> base64
        |  POST JSON + nagłówek X-Csrf-Token
        v
[include/VMIconsAjax.php]  (php-fpm, tylko root)
        |  walidacja typu (getimagesizefromstring), sanityzacja nazwy
        |  GD: konwersja do PNG 48x48 (crop lub contain), przezroczyste tło
        |--(1) zapis master --> /mnt/user/appdata/custom-vm-icons/<nazwa>.png
        |--(2) kopia live  --> /boot/config/plugins/dynamix.vm.manager/templates/images/<nazwa>.png
        v
[VM Manager (dynamix.vm.manager)] -- czyta /boot/config/... natively:
        - VMedit.php: glob() po katalogu -> lista ikon w pickerze
        - libvirt.php: domain_get_icon_url() -> URL ikony VM
        - nginx: serwuje przez symlink /usr/local/emhttp/boot -> /boot

[Start systemu]
        /boot/config/go -> sync_icons.sh --boot (w tle)
        |  czeka max ~5 min na zamontowanie /mnt/user
        |  kopiuje BRAKUJĄCE pliki appdata -> /boot
        v
        ikony przywrócone po restarcie
```

## Decyzje projektowe

### Master copy w appdata, nie na /boot

unRAID czyta ikony VM z `/boot/config/plugins/dynamix.vm.manager/templates/images/` — to natywny, niepatchowany mechanizm (picker w `VMedit.php` używa `glob()` po tym katalogu). `/boot` jest FAT — nie ma symlinków, a i pojemność pendrive'a bywa ograniczona. Dlatego **master** żyje w `/mnt/user/appdata/custom-vm-icons` (udział appdata: trwały, duży, backupowalny), a do `/boot` trafia tylko kopia robocza. Skasowanie ikony usuwa ją z obu miejsc.

### Sync „tylko brakujące", nie rsync pełny

`sync_icons.sh` kopiuje wyłącznie pliki, których brak w katalogu VM Managera — celowo: jeśli użytkownik nadpisze ikonę w appdata (np. nowszą wersją), plik na `/boot` jest odświeżany przez endpoint uploadu, a boot-sync nie zmienia nic przy restarcie. Pełną nadpisującą synchronizację robi przycisk „Synchronizuj do VM Managera".

### Upload przez JSON+base64, nie multipart/form-data

Na unRAID 7.3.2 multipart POST-y do php-fpm **wiszą bez odpowiedzi** (testowane również na oficjalnych endpointach systemu — to nie wina pluginu). Dlatego upload idzie wzorcem sprawdzonego pluginu supla-pwr-unraid: `fetch()` z JSON body (`{action, data, name, fit}`) i tokenem CSRF w nagłówku `X-Csrf-Token` — tak wymaga `local_prepend.php` unRAID, które przy JSON body widzi puste `$_POST` i czyta token z nagłówka.

### Konwersja do 48×48 przez GD

unRAID wyświetla ikony VM w rozmiarze 48×48. Wgrywanie oryginalnych rozmiarów marnowałoby miejsce na /boot i dawało niespójny render. Endpoint normalizuje każdy upload do PNG 48×48 na przezroczystym kwadracie, z dwoma trybami:
- `crop` — kadrowanie centralne (dla kwadratowych grafik),
- `contain` — cała grafika w kwadracie z zachowaniem proporcji (dla szerokich logotypów; margines przezroczysty).

Źródła ikon warto przed wgraniem opatrzyć marginesem (~7%), bo `contain` skaluje „na styk" — bez marginesu grafika dotyka krawędzi kafla.

### Bez patchowania plików systemowych

Plugin nie modyfikuje żadnego pliku unRAID — dokłada tylko własne pliki w `/usr/local/emhttp/plugins/custom-vm-icons/` (te przeżywają reboot bez re-instalacji, bo `upgradepkg` pomija tę samą wersję) i jeden wpis startowy w `/boot/config/go`. Usunięcie pluginu przywraca system do stanu sprzed instalacji.

### Bezpieczeństwo

- Endpoint AJAX sprawdza `$_SESSION['unraid_user'] === 'root'` (tylko prawdziwy root).
- CSRF walidowane globalnie przez `local_prepend.php` (auto_prepend_file) — plugin nie duplikuje sprawdzenia (token jest już usunięty z `$_POST`, zanim wykona się kod pluginu).
- Nazwy plików sanityzowane do `[a-z0-9][a-z0-9._-]{0,60}.png` — brak path traversal.
- Typ pliku weryfikowany od zawartości (`getimagesizefromstring`), nie od rozszerzenia; limit 2 MB.
