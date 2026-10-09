<?php
header('Content-Type: application/json; charset=utf-8');

$rootDirs = ['TON', 'ton', 'doge', 'DOGE', 'tron', 'TRON', 'sol', 'SOL'];
$accounts = [];

// Agar maxsus bitta akkauntning logini so'rasa (Real-time log uchun)
if (isset($_GET['log']) && isset($_GET['currency']) && isset($_GET['folder'])) {
    $curr = preg_replace('/[^a-zA-Z]/', '', $_GET['currency']);
    $fold = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['folder']);
    $logFile = __DIR__ . '/' . $curr . '/' . $fold . '/output.log';
    
    if (file_exists($logFile)) {
        // Oxirgi 50 ta qatorni yoki butun logni o'qish
        $content = file_get_contents($logFile);
        echo json_encode(['status' => 'success', 'log' => $content], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => 'error', 'log' => 'Hozircha log fayl topilmadi. Botni output.log bilan yurgizing!'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Barcha akkauntlarni yig'ish
foreach ($rootDirs as $root) {
    $baseDir = __DIR__ . '/' . $root;
    if (!is_dir($baseDir)) continue;

    $folders = scandir($baseDir);
    foreach ($folders as $folder) {
        if ($folder === '.' || $folder === '..') continue;
        
        $accPath = $baseDir . '/' . $folder;
        if (!is_dir($accPath)) continue;

        $accData = [
            'currency'  => strtoupper($root),
            'folder'    => $folder,
            'username'  => $folder . ' (Kutilmoqda)',
            'balance'   => '0.000000000 TON',
            'api_bal'   => '0',
            'next_time' => '00:00:00'
        ];

        // Status faylni tekshirish
        $statusFile = $accPath . '/status.json';
        if (file_exists($statusFile)) {
            $json = json_decode(file_get_contents($statusFile), true);
            if ($json) $accData = array_merge($accData, $json);
        }

        // Log fayldan ma'lumot olish (agar status bo'lmasa yoki to'ldirish uchun)
        $logFile = $accPath . '/output.log';
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            if (preg_match('/\[Username\s*\]\s*→\s*([^\r\n]+)/u', $logContent, $m)) {
                $accData['username'] = trim($m[1]);
            }
            if (preg_match_all('/\[Balance\s*\]\s*→\s*([^\r\n]+)/u', $logContent, $m)) {
                $accData['balance'] = trim(end($m[1]));
            }
            if (preg_match_all('/\[API Bal\s*\]\s*→\s*([^\r\n]+)/u', $logContent, $m)) {
                $accData['api_bal'] = trim(end($m[1]));
            }
            if (preg_match_all('/Next:\s*([0-9]{2}:[0-9]{2}:[0-9]{2})/i', $logContent, $m)) {
                $accData['next_time'] = trim(end($m[1]));
            }
        }

        $accounts[] = $accData;
    }
}

echo json_encode($accounts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
