<?php
// Render va UptimeRobot uchun oddiy Status Veb-Sayti

header("Content-Type: text/html; charset=UTF-8");
date_default_timezone_set("Asia/Tashkent");

?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TONPICK Bot Monitor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: #1e293b;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            text-align: center;
            max-width: 400px;
        }
        .status {
            display: inline-block;
            padding: 8px 16px;
            background: #22c55e;
            color: #fff;
            font-weight: bold;
            border-radius: 20px;
            margin-top: 15px;
        }
        .time {
            color: #94a3b8;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>🤖 TONPICK Bot Status</h2>
        <p>Veb-servis va monitoring muvaffaqiyatli ishlayapti.</p>
        <div class="status">● ONLINE</div>
        <div class="time">Server vaqti: <?php echo date("Y-m-d H:i:s"); ?></div>
    </div>
</body>
</html>
