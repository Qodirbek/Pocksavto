<?php
/**
 * TONPICK Professional Account Manager v2.0 (save.php)
 * - Chiroyli CLI interfeysi
 * - Mavjud akkauntlarni ko'rish va yangilash (Update)
 * - Yangi akkaunt qo'shish (Add)
 * - JSON konfiguratsiyani avtomatik to'g'ri formatda saqlash
 */

error_reporting(0);
date_default_timezone_set("UTC");

if (PHP_OS_FAMILY == "Linux") {
    define("n", "\n"); define("d", "\033[0m"); define("m", "\033[1;31m");
    define("h", "\033[1;32m"); define("k", "\033[1;33m"); define("c", "\033[1;36m");
    define("p", "\033[1;37m"); define("bg", "\033[44m");
} else {
    define("n", "\n"); define("d", ""); define("m", ""); define("h", "");
    define("k", ""); define("c", ""); define("p", ""); define("bg", "");
}

$cfgFile = "config_tonpick.json";

function banner() {
    system(PHP_OS_FAMILY == "Linux" ? "clear" : "cls");
    echo n . bg . p . str_pad(" TONPICK ACCOUNT MANAGER ", 46, " ", STR_PAD_BOTH) . d . n;
    echo c . "" . str_repeat("", 44) . "" . n;
    echo c . "" . p . "  Status : " . h . "Active & Ready" . str_repeat(" ", 22) . c . "" . n;
    echo c . "" . str_repeat("", 44) . "" . n;
    echo c . str_repeat("", 46) . n;
}

banner();

$config = file_exists($cfgFile) ? json_decode(file_get_contents($cfgFile), true) : [];

if (empty($config)) {
    echo k . " [!] Konfiguratsiya topilmadi. Asosiy API sozlamalari kiritiladi:" . d . n;
    echo h . "  [1] Multibot" . n . h . "  [2] Xevil" . n . n;
    $type = trim(readline(" Captcha API Type (1 or 2): ")); echo n;
    $config["type"] = $type ?: "2";
    
    if ($config["type"] == "1") {
        $config["multibot_apikey"] = trim(readline(" Multibot API Key: ")); echo n;
    } else {
        $config["xevil_apikey"] = trim(readline(" Xevil API Key: ")); echo n;
    }
    $config["gemini_apikey"] = trim(readline(" Gemini API Key: ")); echo n;
    $config["accounts"] = [];
}

while (true) {
    banner();
    $accounts = $config["accounts"] ?? [];
    $count = count($accounts);

    echo h . " Jami saqlangan akkauntlar soni: " . p . $count . d . n;
    echo c . str_repeat("", 46) . d . n;

    if ($count > 0) {
        echo k . " Mavjud akkauntlar ro'yxati:" . d . n;
        foreach ($accounts as $i => $acc) {
            $shortCookie = substr($acc["cookie"], 0, 30) . "...";
            echo c . " [" . ($i + 1) . "] " . p . "Cookie: " . h . $shortCookie . d . n;
        }
        echo c . str_repeat("", 46) . d . n;
    }

    echo p . " Amallar menyusi:" . d . n;
    echo h . "  [1] " . p . "Yangi akkaunt qo'shish" . d . n;
    echo h . "  [2] " . p . "Mavjud akkauntni yangilash (Cookie / UA)" . d . n;
    echo h . "  [3] " . p . "Akkauntni o'chirish" . d . n;
    echo h . "  [0] " . p . "Chiqish" . d . n;
    echo c . str_repeat("", 46) . d . n;

    $choice = trim(readline(" Tanlovingiz (0-3): ")); echo n;

    if ($choice === "1") {
        echo bg . p . " YANGI AKKAUNT QO'SHISH " . d . n;
        $cookie = trim(readline(" Cookie matnini kiriting: ")); echo n;
        $ua = trim(readline(" User-Agent kiriting (bo'sh qoldirsangiz standart yoziladi): ")); echo n;

        if (!empty($cookie)) {
            $config["accounts"][] = [
                "cookie" => $cookie,
                "user_agent" => !empty($ua) ? $ua : "Mozilla/5.0 (Linux; Android 11; M2101K7AG) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Mobile Safari/537.36"
            ];
            file_put_contents($cfgFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo h . " [] Akkaunt muvaffaqiyatli qo'shildi!" . d . n;
        } else {
            echo m . " [!] Xatolik: Cookie bo'sh bo'lishi mumkin emas!" . d . n;
        }
        readline(" Davom etish uchun Enter ni bosing...");

    } elseif ($choice === "2" && $count > 0) {
        echo bg . p . " AKKAUNTNI YANGILASH " . d . n;
        $index = (int)trim(readline(" Qaysi raqamli akkauntni yangilamoqchisiz? (1-$count): ")); echo n;
        
        if (isset($accounts[$index - 1])) {
            $cookie = trim(readline(" Yangi Cookie matnini kiriting: ")); echo n;
            $ua = trim(readline(" Yangi User-Agent kiriting (o'zgartirmasangiz Enter bosing): ")); echo n;

            if (!empty($cookie)) {
                $config["accounts"][$index - 1]["cookie"] = $cookie;
                if (!empty($ua)) {
                    $config["accounts"][$index - 1]["user_agent"] = $ua;
                }
                file_put_contents($cfgFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo h . " [] Akkaunt #" . $index . " muvaffaqiyatli yangilandi!" . d . n;
            } else {
                echo m . " [!] Xatolik: Cookie bo'sh bo'lishi mumkin emas!" . d . n;
            }
        } else {
            echo m . " [!] Bunday raqamdagi akkaunt topilmadi!" . d . n;
        }
        readline(" Davom etish uchun Enter ni bosing...");

    } elseif ($choice === "3" && $count > 0) {
        echo bg . p . " AKKAUNTNI O'CHIRISH " . d . n;
        $index = (int)trim(readline(" Qaysi raqamli akkauntni o'chirmoqchisiz? (1-$count): ")); echo n;
        
        if (isset($accounts[$index - 1])) {
            array_splice($config["accounts"], $index - 1, 1);
            file_put_contents($cfgFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo h . " [] Akkaunt o'chirildi!" . d . n;
        } else {
            echo m . " [!] Bunday raqamdagi akkaunt topilmadi!" . d . n;
        }
        readline(" Davom etish uchun Enter ni bosing...");

    } elseif ($choice === "0") {
        echo h . " Dastur to'xtatildi. Hayr!" . d . n;
        break;
    }
}
