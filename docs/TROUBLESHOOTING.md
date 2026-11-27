# Rozwiązywanie problemów

| Objaw | Przyczyna | Rozwiązanie |
|---|---|---|
| Upload zwraca błąd „Udziały /mnt/user nie są zamontowane" lub „Nie można utworzyć /mnt/user/appdata/..." | tablica nie jest zamontowana | zamontuj tablicę (**Main → Array Start**), potem ponów upload |
| Ikona wgrana, ale nie widać jej w pickerze VM | kopia na `/boot` się nie zapisała | na stronie pluginu kliknij **Synchronizuj do VM Managera**; sprawdź status „aktywna" przy ikonie |
| Po restarcie zniknęły ikony z pickera | zdarzenie `disks_mounted` nie uruchomiło synchronizacji | sprawdź `grep custom-vm-icons /var/log/syslog` i czy istnieje `/usr/local/emhttp/plugins/custom-vm-icons/event/disks_mounted`; kliknij **Przywróć z appdata (brakujące)** |
| „Serwer zwrócił HTML zamiast JSON" | wygasła sesja WebGUI | odśwież stronę i zaloguj się ponownie |
| „Pusta odpowiedź serwera (HTTP ...)" | endpoint nieosiągalny / błąd PHP | sprawdź `tail /var/log/phplog`; zweryfikuj że plik `include/VMIconsAjax.php` istnieje |
| Ikona jest „przycięta" (dotyka krawędzi) | grafika źródłowa bez marginesu + tryb `crop`/`contain` na styk | wgraj ponownie z marginesem ~7% wokół treści |
| Nazwa pliku dziwnie zmodyfikowana | sanityzacja (małe litery, bez spacji/polskich znaków) | to celowe — picker VM wymaga bezpiecznych nazw; wpisz nazwę ręcznie w formularzu |

## Logi

- Synchronizacja przy starcie: `grep custom-vm-icons /var/log/syslog` (logger tag `custom-vm-icons`).
- Błędy PHP WebGUI: `/var/log/phplog`.

## Ręczna diagnostyka

```bash
# ile ikon widzi VM Manager:
ls /boot/config/plugins/dynamix.vm.manager/templates/images/*.png | wc -l

# stan master copy:
ls -la /mnt/user/appdata/custom-vm-icons/

# ręczny sync (tylko brakujące):
/usr/local/emhttp/plugins/custom-vm-icons/scripts/sync_icons.sh --boot

# zdarzenie startowe (od 2026.11.27; w /boot/config/go nie powinno byc wpisu):
ls -l /usr/local/emhttp/plugins/custom-vm-icons/event/disks_mounted
grep custom-vm-icons /boot/config/go   # powinno nic nie zwrocic
```
