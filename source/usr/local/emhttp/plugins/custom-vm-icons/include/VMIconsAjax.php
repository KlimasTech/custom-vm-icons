<?php
/**
 * custom-vm-icons/include/VMIconsAjax.php
 * JSON endpoint (JSON in / JSON out) - wzorzec jak supla-pwr-unraid:
 * POST z JSON body + token CSRF w naglowku X-Csrf-Token (wymagany przez
 * local_prepend.php, ktore przy JSON body widzi puste $_POST).
 * Akcje: list, upload (base64), delete, sync, restore.
 * Master copy ikon: /mnt/user/appdata/custom-vm-icons (trwale)
 * Live copy: /boot/config/plugins/dynamix.vm.manager/templates/images
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Blad serwera: ' . $err['message']]);
    }
});

if (session_status() === PHP_SESSION_NONE) { session_start(); }
// tylko prawdziwy root unRAID
if (empty($_SESSION['unraid_user']) || $_SESSION['unraid_user'] !== 'root') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Tylko administrator (root) moze zarzadzac ikonami VM.']);
    exit;
}

define('CVI_APPDATA', '/mnt/user/appdata/custom-vm-icons');
define('CVI_BOOT', '/boot/config/plugins/dynamix.vm.manager/templates/images');

function cvi_json($arr) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    echo json_encode($arr);
    exit;
}

function cvi_input(): array {
    $raw = file_get_contents('php://input');
    $in = json_decode($raw, true);
    if (!is_array($in)) $in = $_POST;
    return is_array($in) ? $in : [];
}

function cvi_ensure_dirs() {
    if (!is_dir(CVI_APPDATA) && !@mkdir(CVI_APPDATA, 0755, true)) {
        cvi_json(['ok' => false, 'error' => 'Nie mozna utworzyc ' . CVI_APPDATA . ' - zamontowana tablica?']);
    }
    if (!is_dir(CVI_BOOT) && !@mkdir(CVI_BOOT, 0755, true)) {
        cvi_json(['ok' => false, 'error' => 'Nie mozna utworzyc ' . CVI_BOOT]);
    }
}

function cvi_list_dir($dir) {
    $out = [];
    foreach (glob($dir . '/*.png') as $f) {
        $out[basename($f)] = $f;
    }
    return $out;
}

function cvi_valid_name($name) {
    return is_string($name) && preg_match('/^[a-z0-9][a-z0-9._-]{0,60}\.png$/', $name) === 1;
}

function cvi_sync($onlyMissing = false) {
    $copied = 0;
    foreach (cvi_list_dir(CVI_APPDATA) as $name => $src) {
        $dst = CVI_BOOT . '/' . $name;
        if ($onlyMissing && is_file($dst)) continue;
        if (@copy($src, $dst)) $copied++;
    }
    return $copied;
}

$in = cvi_input();
$action = $in['action'] ?? 'list';

switch ($action) {

case 'list': {
    $appdata = is_dir(CVI_APPDATA) ? cvi_list_dir(CVI_APPDATA) : [];
    $boot = is_dir(CVI_BOOT) ? cvi_list_dir(CVI_BOOT) : [];
    $icons = [];
    foreach ($appdata as $name => $path) {
        $icons[] = [
            'name' => $name,
            'size' => round(filesize($path) / 1024, 1) . ' KB',
            'inBoot' => isset($boot[$name]),
        ];
    }
    usort($icons, function ($a, $b) { return strcmp($a['name'], $b['name']); });
    cvi_json(['ok' => true, 'icons' => $icons,
        'appdataCount' => count($appdata), 'bootCount' => count($boot)]);
}

case 'upload': {
    $dataB64 = preg_replace('/\s+/', '', (string)($in['data'] ?? ''));
    if ($dataB64 === '') cvi_json(['ok' => false, 'error' => 'Brak danych pliku.']);
    $bin = @base64_decode($dataB64, true);
    if ($bin === false || $bin === '') cvi_json(['ok' => false, 'error' => 'Nieprawidlowe dane (base64).']);
    if (strlen($bin) > 2 * 1024 * 1024) cvi_json(['ok' => false, 'error' => 'Plik wiekszy niz 2 MB.']);

    $info = @getimagesizefromstring($bin);
    if ($info === false) cvi_json(['ok' => false, 'error' => 'To nie jest prawidlowy plik graficzny.']);
    if (!in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
        cvi_json(['ok' => false, 'error' => 'Dozwolone tylko PNG i JPG.']);
    }

    // nazwa docelowa: podana przez usera albo z oryginalnej nazwy pliku
    $raw = trim((string)($in['name'] ?? ''));
    if ($raw === '') $raw = pathinfo((string)($in['origname'] ?? 'icon'), PATHINFO_FILENAME);
    $raw = strtolower($raw);
    $base = preg_replace('/[^a-z0-9._-]+/', '-', $raw);
    $base = preg_replace('/-+/', '-', trim($base, '-.'));
    if ($base === '' || $base === '.') $base = 'icon';
    if (strlen($base) > 50) $base = substr($base, 0, 50);
    $name = $base . '.png';
    if (!cvi_valid_name($name)) {
        cvi_json(['ok' => false, 'error' => 'Nieprawidlowa nazwa pliku wynikowego.']);
    }

    cvi_ensure_dirs();

    // GD: konwersja do PNG 48x48 na przezroczystym kwadracie.
    // fit="contain" (domyslny dla logotypow szerokich): caly obraz zmiescic
    // w 48x48 z zachowaniem proporcji. Wpp: klasyczne kwadratowe kadrowanie
    // ze srodka (crop).
    $srcImg = @imagecreatefromstring($bin);
    if (!$srcImg) cvi_json(['ok' => false, 'error' => 'Nie udalo sie otworzyc obrazu (GD).']);
    $w = imagesx($srcImg); $h = imagesy($srcImg);
    $dst = imagecreatetruecolor(48, 48);
    imagesavealpha($dst, true);
    $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $trans);
    $fit = (string)($in['fit'] ?? 'crop');
    if ($fit === 'contain' && $w !== $h) {
        $scale = min(48 / $w, 48 / $h);
        $dw = max(1, (int)round($w * $scale));
        $dh = max(1, (int)round($h * $scale));
        $dx = (int)(($dw < 48) ? ((48 - $dw) / 2) : 0);
        $dy = (int)(($dh < 48) ? ((48 - $dh) / 2) : 0);
        imagecopyresampled($dst, $srcImg, $dx, $dy, 0, 0, $dw, $dh, $w, $h);
    } else {
        $s = min($w, $h);
        imagecopyresampled($dst, $srcImg, 0, 0, (int)(($w - $s) / 2), (int)(($h - $s) / 2), 48, 48, $s, $s);
    }
    $outPath = CVI_APPDATA . '/' . $name;
    if (!@imagepng($dst, $outPath, 6)) {
        imagedestroy($srcImg); imagedestroy($dst);
        cvi_json(['ok' => false, 'error' => 'Zapis do appdata nieudany (uprawnienia/mount?).']);
    }
    imagedestroy($srcImg); imagedestroy($dst);
    @copy($outPath, CVI_BOOT . '/' . $name);
    cvi_json(['ok' => true, 'name' => $name, 'copiedToBoot' => is_file(CVI_BOOT . '/' . $name)]);
}

case 'delete': {
    $name = (string)($in['name'] ?? '');
    if (!cvi_valid_name($name)) cvi_json(['ok' => false, 'error' => 'Nieprawidlowa nazwa.']);
    $app = CVI_APPDATA . '/' . $name;
    $boot = CVI_BOOT . '/' . $name;
    if (!is_file($app) && !is_file($boot)) {
        cvi_json(['ok' => false, 'error' => 'Nie ma takiej ikony.']);
    }
    $okA = !is_file($app) || @unlink($app);
    $okB = !is_file($boot) || @unlink($boot);
    cvi_json(['ok' => ($okA && $okB), 'error' => ($okA && $okB) ? null : 'Czesciowe usuniecie - sprawdz logi.']);
}

case 'sync': {
    cvi_ensure_dirs();
    cvi_json(['ok' => true, 'copied' => cvi_sync(false)]);
}

case 'restore': {
    cvi_ensure_dirs();
    cvi_json(['ok' => true, 'copied' => cvi_sync(true)]);
}

default:
    cvi_json(['ok' => false, 'error' => 'Nieznana akcja.']);
}
