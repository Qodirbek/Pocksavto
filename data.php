<?php
/**
 * TONPICK Bot - Professional Data Provider
 * Supports multiple currencies and automatic JSON/Log parsing.
 */
header('Content-Type: application/json; charset=utf-8');

// Barcha qidiriladigan asosiy papkalar (katta-kichik harflar inobatga olingan)
$rootDirs = ['ton', 'TON', 'doge', 'DOGE', 'tron', 'TRON', 'sol', 'SOL'];$accounts = [];

foreach ($rootDirs as$root) {
    $baseDir = __DIR__ . '/' .$root;
    if (!is_dir($baseDir)) continue;

    $folders = scandir($baseDir);
    foreach ($folders as$folder) {
        if ($folder === '.' \vert{}\vert{}$folder === '..') continue;
        
        $accPath = $baseDir . '/' .$folder;
        if (!is_dir($accPath)) continue;

        // Standart ma'lumotlar
        $accData = [
            'currency'  => strtoupper($root),
            'folder'    => $folder,
            'username'  => 'Kutilmoqda...',
            'balance'   => '0.000000000 TON',
            'api_bal'   => '0',
            'next_time' => '00:00:00'
        ];

        // 1. Agar bot o'zi `status.json` yozgan bo'lsa, birinchi shu o'qiladi
        $statusFile =$accPath . '/status.json';
        if (file_exists($statusFile)) {
            $json = json_decode(file_get_contents($statusFile), true);
            if ($json) {$accData = array_merge($accData,$json);
            }
        } 
        // 2. Agar `status.json` topilmasa, eski usulda `output.log` dan o'qib oladi
        else {
            $logFile =$accPath . '/output.log';
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);

                if (preg_match('/\[Username\s*\]\s*→\s*([^\r\n]+)/u', $logContent,$m)) {
                    $accData['username'] = trim($m[1]);
                }
                if (preg_match_all('/\[Balance\s*\]\s*→\s*([^\r\n]+)/u', $logContent,$m)) {
                    $accData['balance'] = trim(end($m[1]));
                }
                if (preg_match_all('/\[API Bal\s*\]\s*→\s*([^\r\n]+)/u', $logContent,$m)) {
                    $accData['api_bal'] = trim(end($m[1]));
                }
                if (preg_match_all('/Next:\s*([0-9]{2}:[0-9]{2}:[0-9]{2})/i', $logContent,$m)) {
                    $accData['next_time'] = trim(end($m[1]));
                }
            }
        }

        $accounts[] =$accData;
    }
}

echo json_encode($accounts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
