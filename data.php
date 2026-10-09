<?php
header('Content-Type: application/json; charset=utf-8');

$baseDir = __DIR__ . '/ton';
$accounts = [];

if (is_dir($baseDir)) {
    $folders = scandir($baseDir);
    foreach ($folders as $folder) {
        if ($folder === '.' || $folder === '..') continue;
        
        $accPath = $baseDir . '/' . $folder;
        if (is_dir($accPath)) {
            $logFile = $accPath . '/output.log';
            
            $accData = [
                'folder'    => $folder,
                'username'  => 'Kutilmoqda...',
                'balance'   => '0.000000000 TON',
                'api_bal'   => '0',
                'next_time' => '00:00:00'
            ];

            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);

                // Username ajratish: [Username  ] → shikishikibaba
                if (preg_match('/\[Username\s*\]\s*→\s*([^\r\n]+)/u', $logContent, $m)) {
                    $accData['username'] = trim($m[1]);
                }

                // So'nggi Balance ajratish: [Balance   ] → 0.004500000 TON
                if (preg_match_all('/\[Balance\s*\]\s*→\s*([^\r\n]+)/u', $logContent, $m)) {
                    $accData['balance'] = trim(end($m[1]));
                }

                // API Bal ajratish: [API Bal   ] → 1.11798
                if (preg_match_all('/\[API Bal\s*\]\s*→\s*([^\r\n]+)/u', $logContent, $m)) {
                    $accData['api_bal'] = trim(end($m[1]));
                }

                // Next vaqtini ajratish: Next: 00:55:43
                if (preg_match_all('/Next:\s*([0-9]{2}:[0-9]{2}:[0-9]{2})/i', $logContent, $m)) {
                    $accData['next_time'] = trim(end($m[1]));
                }
            }

            $accounts[] = $accData;
        }
    }
}

echo json_encode($accounts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
