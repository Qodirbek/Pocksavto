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
            $botFile = $accPath . '/bot.php';
            
            // Standart ma'lumotlar (agar fayl bo'lmasa)
            $accData = [
                'folder' => $folder,
                'username' => 'Aniqlanmagan',
                'balance' => '0.000000000 TON',
                'api_bal' => '0',
                'status' => 'Off',
                'next_time' => '00:00:00',
                'last_claim' => 'Hali olinmagan'
            ];

            // Agar har bir akk ichida ma'lumot saqlovchi JSON yoki PHP bo'lsa o'qiymiz
            // Masalan, har bir bot o'z holatini status.json ga yozib boradi
            $statusFile = $accPath . '/status.json';
            if (file_exists($statusFile)) {
                $json = json_decode(file_get_contents($statusFile), true);
                if ($json) {
                    $accData = array_merge($accData, $json);
                }
            }
            
            $accounts[] = $accData;
        }
    }
}

echo json_encode($accounts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
