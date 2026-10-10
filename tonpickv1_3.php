<?php
/**
 * TONPICK Professional Bot v6.2 (Multi-Account & Full Telegram Logging)
 * - Multi-account support via JSON configuration
 * - Auto-run without prompts if config/accounts exist (Render compatible)
 * - Auto .env and Environment variable support for Telegram
 * - Captcha auto-detect (reCAPTCHA, hCaptcha, pCaptcha, IconCaptcha)
 * - Gemini & Multibot/Xevil integration
 * - Detailed real-time Telegram notifications for all steps
 */

error_reporting(0);
date_default_timezone_set("UTC");

// .env faylni o'qib muhitga yuklash (agar mavjud bo'lsa)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [null, null];
        if ($name !== null && $value !== null) {
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!getenv($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
}

if (PHP_OS_FAMILY == "Linux") {
    define("n", "\n"); define("d", "\033[0m"); define("m", "\033[1;31m");
    define("h", "\033[1;32m"); define("k", "\033[1;33m"); define("c", "\033[1;36m");
    define("p", "\033[1;37m"); define("bg", "\033[44m");
} else {
    define("n", "\n"); define("d", ""); define("m", ""); define("h", "");
    define("k", ""); define("c", ""); define("p", ""); define("bg", "");
}

const TITLE         = "TONPICK";
const VERSION       = "6.2.0";
const HOST          = "https://tonpick.game/";
const REFF          = "https://tonpick.game/?ref=iewilmaestro";
const HCAPTCHA_KEY  = "6ef421cc-2fbc-493a-96ec-550c43f8ecd2";
const RECAPTCHA_KEY = "6LcqLB8rAAAAAGxzcVp9W7i4uSZHr-DmHvWQb-KO";
const IC_ENDPOINT   = "https://tonpick.game/iconcaptcha.php";

class Display {
    static function clear() { system(PHP_OS_FAMILY == "Linux" ? "clear" : "cls"); }
    static function banner() {
        self::clear();
        echo n . bg . p . str_pad(" " . TITLE . " BOT  v" . VERSION . " ", 46, " ", STR_PAD_BOTH) . d . n;
        echo c . "┌" . str_repeat("─", 44) . "┐" . n;
        echo c . "│" . p . "  Mode : " . h . "Multi-Account + Telegram Log" . str_repeat(" ", 8) . c . "│" . n;
        echo c . "│" . p . "  Site : " . k . "tonpick.game" . str_repeat(" ", 18) . c . "│" . n;
        echo c . "└" . str_repeat("─", 44) . "┘" . n;
        echo c . str_repeat("─", 46) . n;
    }
    static function line() { echo c . str_repeat("─", 46) . n; }
    static function info($l, $v) { echo h . " [" . p . str_pad($l, 10) . h . "] → " . p . $v . n; }
    static function ok($m) { echo h . " [✓] " . p . $m . n; }
    static function err($m) { echo m . " [!] " . p . $m . n; }
    static function warn($m) { echo k . " [!] " . p . $m . n; }
    static function input($m) { return m . " ╭[" . p . "Input " . $m . m . "]" . n . m . " ╰> " . h; }
    static function title($t) { echo bg . p . str_pad(strtoupper($t), 46, " ", STR_PAD_BOTH) . d . n; }
}

class F {
    public $cfgFile = "config_tonpick.json";

    public function curl($url, $headers = [], $post = null, $cookieFile = "cookie_ton.txt") {
        while (true) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => "",
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_COOKIEJAR => $cookieFile,
            ]);
            if ($post !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
            $res = curl_exec($ch);
            $err = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            if ($err) { Display::err("Curl: $err"); sleep(3); continue; }
            $hs = $info["header_size"];
            $body = substr($res, $hs);
            if ($body === "" || $body === false) { Display::warn("Empty response"); sleep(2); continue; }
            return [substr($res, 0, $hs), $body, (int)$info["http_code"]];
        }
    }
    
    public function sendTelegram($message) {
        $token = getenv('TG_BOT_TOKEN') ?: ($_ENV['TG_BOT_TOKEN'] ?? '');
        $chat_id = getenv('TG_CHANNEL_ID') ?: ($_ENV['TG_CHANNEL_ID'] ?? '');
        
        if (empty($token) || empty($chat_id)) {
            return;
        }
        
        $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($message);
        @file_get_contents($url);
    }
    
    public function getConfig() {
        if (!file_exists($this->cfgFile)) {
            return [];
        }
        return json_decode(file_get_contents($this->cfgFile), true) ?: [];
    }

    public function saveConfig($data) {
        file_put_contents($this->cfgFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function setupAccounts() {
        $config = $this->getConfig();
        
        // Agar config fayl umuman bo'lmasa yoki ichida akkauntlar bo'lmasa
        if (empty($config["type"]) || empty($config["accounts"])) {
            // Agar terminal orqali ishlayotgan bo'lsa ma'lumotlarni so'raymiz
            echo n; Display::title("CAPTCHA API");
            echo h . "  [1] Multibot" . n . h . "  [2] Xevil" . n . n;
            $type = trim(readline(" Captcha API Type (1 or 2): ")); echo n;
            $config["type"] = $type ?: "2";
            
            if ($type == "1") {
                $config["multibot_apikey"] = trim(readline(" Multibot API Key: ")); echo n;
            } else {
                $config["xevil_apikey"] = trim(readline(" Xevil API Key: ")); echo n;
            }
            $config["gemini_apikey"] = trim(readline(" Gemini API Key: ")); echo n;
            $config["accounts"] = [];
            
            Display::title("AKKAUNT QO'SHISH");
            $cookie = trim(readline(" Cookie matnini kiriting: ")); echo n;
            $ua = trim(readline(" User-Agent kiriting: ")); echo n;
            if (!empty($cookie)) {
                $config["accounts"][] = ["cookie" => $cookie, "user_agent" => $ua];
            }
            $this->saveConfig($config);
        }
        
        // MUHIM: Agar config va akkauntlar allaqachon mavjud bo'lsa (masalan Render'da),
        // hech qanday savol so'ramaydi va to'g'ridan-to'g'ri avtomatik ishga tushadi!
        return $config;
    }

        public function timer($sec) {
        // 2 daqiqadan (120s) 6 daqiqagacha (360s) random vaqt qo'shamiz
        $extra = rand(120, 360);
        $totalSec = $sec + $extra;
        
        $end = time() + $totalSec;
        $s = ["─", "/", "│", "\\"]; $i = 0;
        while (true) {
            $l = $end - time();
            if ($l < 1) break;
            $hh = str_pad(floor($l / 3600), 2, "0", STR_PAD_LEFT);
            $mm = str_pad(floor(($l % 3600) / 60), 2, "0", STR_PAD_LEFT);
            $ss = str_pad($l % 60, 2, "0", STR_PAD_LEFT);
            echo "\r  " . c . $s[$i % 4] . p . " Xavfsiz kutish (Random +): " . h . "$hh:$mm:$ss" . p . "    ";
            usleep(250000); $i++;
        }
        echo "\r" . str_repeat(" ", 50) . "\r";
    }

    public function cookiesFromHeader($header) {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $m);
        $out = [];
        foreach ($m[1] as $item) { parse_str($item, $x); $out = array_merge($out, $x); }
        return $out;
    }

    public function makeHash($key) {
        if (!$key) return "";
        $rx = rand(50, 110);
        $ry = rand(15, 40);
        $ts = time();
        $data = $rx . ":" . $ry . ":" . $ts;
        $enc = "";
        $kl = strlen($key);
        for ($i = 0; $i < strlen($data); $i++) {
            $enc .= chr(ord($data[$i]) ^ ord($key[$i % $kl]));
        }
        return base64_encode($enc);
    }

    public function uuid() {
        return sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}

class Gemini {
    private $key;
    public function __construct($k) { $this->key = $k; }

    private function ask($parts, $max = 48) {
        $models = ["gemini-flash-latest", "gemini-3.1-flash-lite", "gemini-3-flash-preview"];
        $body = [
            "contents" => [["parts" => $parts]],
            "generationConfig" => ["temperature" => 0.1, "maxOutputTokens" => $max]
        ];
        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($this->key);
            $ctx = stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => "Content-Type: application/json\r\n",
                    "content" => json_encode($body),
                    "timeout" => 60,
                    "ignore_errors" => true
                ]
            ]);
            $res = @file_get_contents($url, false, $ctx);
            if (!$res) continue;
            $j = json_decode($res, true);
            if (!empty($j["error"])) continue;
            $t = trim($j["candidates"][0]["content"]["parts"][0]["text"] ?? "");
            if ($t !== "") return [$t, $model];
        }
        return [null, null];
    }

    public function pCaptcha($question, $imgBin) {
        $b64 = base64_encode($imgBin);
        $prompt = "3x3 captcha grid indices:\n0 1 2\n3 4 5\n6 7 8\nQuestion: {$question}\nReply ONLY matching cell indices comma-separated like: 1,4,7";
        list($text, $model) = $this->ask([
            ["text" => $prompt],
            ["inline_data" => ["mime_type" => "image/jpeg", "data" => $b64]]
        ], 32);
        if (!$text) return false;
        preg_match_all('/\d+/', $text, $m);
        $idx = array_values(array_unique(array_map("intval", $m[0])));
        $idx = array_values(array_filter($idx, function ($x) { return $x >= 0 && $x <= 8; }));
        Display::ok("Gemini($model) cells: " . implode(",", $idx));
        return $idx;
    }

    public function iconCaptcha($imgB64) {
        $imgB64 = preg_replace('#^data:image/\w+;base64,#', '', $imgB64);
        $prompt = "IconCaptcha row of icons. Find LEAST common icon. Index from LEFT starts at 0. Reply ONLY one number.";
        list($text, $model) = $this->ask([
            ["text" => $prompt],
            ["inline_data" => ["mime_type" => "image/png", "data" => $imgB64]]
        ], 12);
        if (!$text) return false;
        if (preg_match('/(\d+)/', $text, $m)) {
            Display::ok("Gemini($model) index: " . $m[1]);
            return (int)$m[1];
        }
        return false;
    }
}

class Solver {
    private $url, $key;
    public $gemini;

    public function __construct($config, F $f) {
        if ($config["type"] == 1) {
            $this->url = "http://api.multibot.in/";
            $this->key = $config["multibot_apikey"];
        } else {
            $this->url = "https://sctg.xyz/";
            $this->key = $config["xevil_apikey"] . "|SOFTID1204538927";
        }
        $this->gemini = new Gemini($config["gemini_apikey"]);
    }

    private function in($data) {
        return json_decode(@file_get_contents($this->url . "in.php?key=" . $this->key . "&json=1&" . $data), true);
    }
    private function res($id) {
        return json_decode(@file_get_contents($this->url . "res.php?key=" . $this->key . "&action=get&id=$id&json=1"), true);
    }

    private function waitToken($data, $label) {
        $in = $this->in($data);
        if (!$in || empty($in["status"])) {
            Display::err("$label → " . ($in["request"] ?? "API error"));
            return false;
        }
        $id = $in["request"]; $p = 0;
        while (true) {
            echo "\r  " . k . "Bypass $label yechilmoqda... $p%" . p . "   ";
            $r = $this->res($id);
            if (($r["request"] ?? "") == "CAPCHA_NOT_READY") {
                $p = min(95, $p + rand(8, 14)); sleep(5); continue;
            }
            if (!empty($r["status"])) {
                echo "\r  " . h . "[✓] $label muvaffaqiyatli yechildi" . p . "          " . n;
                return $r["request"];
            }
            Display::err("$label yechilmadi");
            return false;
        }
    }

    public function balance() {
        $r = @json_decode(file_get_contents($this->url . "res.php?action=userinfo&key=" . $this->key), true);
        return $r["balance"] ?? "0";
    }

    public function recaptcha($page) {
        return $this->waitToken(http_build_query([
            "method" => "userrecaptcha", "sitekey" => RECAPTCHA_KEY, "pageurl" => $page
        ]), "reCAPTCHA");
    }

    public function hcaptcha($page) {
        return $this->waitToken(http_build_query([
            "method" => "hcaptcha", "sitekey" => HCAPTCHA_KEY, "pageurl" => $page
        ]), "hCaptcha");
    }
}

class Bot extends F {
    public $config, $solver;

    public function __construct() {
        Display::banner();
        $this->config = $this->setupAccounts();
        $this->solver = new Solver($this->config, $this);

        $this->sendTelegram("🚀 TONPICK Multi-Account Bot Ishga Tushdi!\n📊 Jami akkauntlar soni: " . count($this->config["accounts"]));
        
        $this->mainLoop();
    }

    private function hdr($cookie, $ua, $ajax = false) {
        $h = [
            "Host: " . parse_url(HOST)["host"],
            "Cookie: " . $cookie,
            "User-Agent: " . $ua,
            "Accept-Language: en-US,en;q=0.9",
            "Origin: " . HOST,
            "Referer: " . HOST . "faucet.php",
        ];
        if ($ajax) {
            $h[] = "X-Requested-With: XMLHttpRequest";
            $h[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
            $h[] = "Accept: application/json, text/javascript, */*; q=0.01";
        } else {
            $h[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
        }
        return $h;
    }

    public function dashboard($cookie, $ua, $cookieFile) {
        $body = $this->curl(HOST, $this->hdr($cookie, $ua), null, $cookieFile)[1];
        $user = $bal = "";
        if (preg_match('/class="profile-name">\s*([^<]+)/i', $body, $m)) $user = trim($m[1]);
        if (preg_match('/class="[^"]*user_balance[^"]*"[^>]*>\s*([0-9.]+)/i', $body, $m)) $bal = trim($m[1]);
        elseif (preg_match('/user_balance">\s*([0-9.]+)/i', $body, $m)) $bal = trim($m[1]);
        return ["username" => $user, "balance" => $bal ?: "0"];
    }

    private function detect($body) {
        $found = [];
        if (preg_match_all('/<option[^>]*value="(\d+)"([^>]*)>([^<]*)</i', $body, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $o) {
                $v = (int)$o[1];
                if (!in_array($v, [0, 1, 2, 4], true)) continue;
                $names = [0 => "IconCaptcha", 1 => "reCAPTCHA", 2 => "hCaptcha", 4 => "pCaptcha"];
                $found[$v] = [
                    "name" => $names[$v],
                    "selected" => (stripos($o[2] . $o[0], "selected") !== false)
                ];
            }
        }
        foreach ($found as $v => $info) if ($info["selected"]) return $v;
        foreach ([4, 0, 2, 1] as $v) if (isset($found[$v])) return $v;
        return 1;
    }

    private function extractHashKey($body) {
        if (preg_match("/get_hash\s*\(\s*event\s*,\s*'([a-f0-9]+)'/i", $body, $m)) return $m[1];
        return "";
    }

    private function solvePCaptcha($cookie, $ua, $cookieFile) {
        Display::info("Captcha", "pCaptcha yechilmoqda...");
        $this->sendTelegram("⚡ pCaptcha yechilmoqda...");
        
        $r = $this->curl(HOST . "generate_pcaptcha.php", $this->hdr($cookie, $ua, true), null, $cookieFile);
        $data = json_decode($r[1], true);
        if (!$data || empty($data["image"])) {
            Display::err("generate_pcaptcha xatolik");
            return false;
        }
        $img = $this->curl($data["image"], $this->hdr($cookie, $ua), null, $cookieFile)[1];
        if (strlen($img) < 400) return false;

        $boxes = $this->solver->gemini->pCaptcha($data["question"] ?? "", $img);
        if ($boxes === false) {
            $this->sendTelegram("❌ pCaptcha yechilmadi!");
            return false;
        }

        $post = http_build_query([
            "action" => "verify_pcaptcha",
            "selected_boxes" => json_encode(array_values($boxes))
        ]);
        $vr = json_decode($this->curl(HOST . "process.php", $this->hdr($cookie, $ua, true), $post, $cookieFile)[1], true);
        if (empty($vr["success"]) || empty($vr["token"])) {
            Display::err("pCaptcha tasdiqlashda xatolik");
            return false;
        }
        Display::ok("pCaptcha muvaffaqiyatli yechildi");
        $this->sendTelegram("✅ pCaptcha muvaffaqiyatli yechildi!");
        return $vr["token"];
    }

    private function solveIcon($body, $cookie, $ua, $cookieFile) {
        Display::info("Captcha", "IconCaptcha yechilmoqda...");
        $this->sendTelegram("⚡ IconCaptcha yechilmoqda...");
        
        $token = "";
        if (preg_match("/_iconcaptcha-token' value='([^']+)'/", $body, $m)) $token = $m[1];
        if (!$token && preg_match('/name=["\']_iconcaptcha-token["\'][^>]*value=["\']([^"\']+)/', $body, $m)) $token = $m[1];
        if (!$token) return false;

        $wid = $this->uuid();
        $ts = (int)(microtime(true) * 1000);
        $payload = base64_encode(json_encode([
            "widgetId" => $wid, "action" => "LOAD", "theme" => "light",
            "token" => $token, "timestamp" => $ts, "initTimestamp" => $ts
        ], JSON_UNESCAPED_SLASHES));
        $h = array_merge($this->hdr($cookie, $ua, true), ["X-IconCaptcha-Token: $token"]);
        $raw = trim($this->curl(IC_ENDPOINT, $h, "payload=" . urlencode($payload), $cookieFile)[1]);
        $data = @json_decode(base64_decode($raw), true);
        if (!$data) $data = @json_decode($raw, true);
        if (!$data || empty($data["challenge"] ?? $data["base64"] ?? null)) return false;
        
        $img = $data["challenge"] ?? $data["base64"];
        $cid = $data["identifier"] ?? $data["challengeId"] ?? "";
        sleep(2);
        
        $idx = $this->solver->gemini->iconCaptcha($img);
        if ($idx === false) {
            $this->sendTelegram("❌ IconCaptcha yechilmadi!");
            return false;
        }
        
        sleep(1);
        foreach ([300, 320, 280, 360] as $w) {
            $x = (int)(($idx + 0.5) * ($w / 5));
            $y = rand(30, 50);
            $ts = (int)(microtime(true) * 1000);
            $payload = base64_encode(json_encode([
                "widgetId" => $wid, "challengeId" => $cid, "action" => "SELECTION",
                "x" => $x, "y" => $y, "width" => $w, "token" => $token,
                "timestamp" => $ts, "initTimestamp" => $ts
            ], JSON_UNESCAPED_SLASHES));
            $raw = trim($this->curl(IC_ENDPOINT, $h, "payload=" . urlencode($payload), $cookieFile)[1]);
            $d = @json_decode(base64_decode($raw), true);
            if (!$d) $d = @json_decode($raw, true);
            if (!empty($d["completed"])) {
                Display::ok("IconCaptcha yechildi");
                $this->sendTelegram("✅ IconCaptcha muvaffaqiyatli yechildi!");
                return [
                    "_iconcaptcha-token" => $token,
                    "ic-rq" => "1", "ic-wid" => $wid, "ic-cid" => $cid, "ic-hp" => ""
                ];
            }
        }
        return false;
    }

    private function processAccount($acc, $index) {
        $cookie = $acc["cookie"];
        $ua = $acc["user_agent"];
        $cookieFile = __DIR__ . "/cookie_acc_{$index}.txt";

        Display::title("AKKAUNT #" . ($index + 1));
        
        $d = $this->dashboard($cookie, $ua, $cookieFile);
        if (empty($d["username"])) {
            Display::err("Akkaunt #" . ($index + 1) . " ga kirib bo'lmadi (Cookie eski yoki xato)");
            $this->sendTelegram("⚠️ Akkaunt #" . ($index + 1) . " xatolik: Cookie yaroqsiz yoki eski!");
            return;
        }

        Display::info("Username", $d["username"]);
        Display::info("Balance", $d["balance"] . " TON");

        $r = $this->curl(HOST . "faucet.php", $this->hdr($cookie, $ua), null, $cookieFile);
        $body = $r[1];
        $header = $r[0];

        if (stripos($body, "Just a moment") !== false) {
            Display::err("Cloudflare himoyasi aniqlandi!");
            $this->sendTelegram("🛑 Akkaunt #" . ($index + 1) . " ({$d['username']}) - Cloudflare xatoligi!");
            return;
        }

        $ck = $this->cookiesFromHeader($header);
        $csrf = $ck["csrf_cookie_name"] ?? "";
        if (!$csrf && preg_match('/csrf_cookie_name=([^;]+)/', $cookie, $m)) $csrf = trim($m[1]);

        $ft = "";
        if (preg_match('/_ft=([^;]+)/', $cookie, $m)) $ft = $m[1];

        $hashKey = $this->extractHashKey($body);
        if (!$hashKey) {
            Display::err("Hash key topilmadi");
            return;
        }

        $type = $this->detect($body);
        $names = [0 => "IconCaptcha", 1 => "reCAPTCHA", 2 => "hCaptcha", 4 => "pCaptcha"];
        Display::info("Captcha Turi", $names[$type] ?? "Noma'lum");
        $this->sendTelegram("🔍 Akkaunt: {$d['username']}\n📌 Captcha turi: " . ($names[$type] ?? "Noma'lum"));

        $g = $hcap = $pc = "";
        $ic = ["_iconcaptcha-token" => "", "ic-rq" => "", "ic-wid" => "", "ic-cid" => "", "ic-hp" => ""];

        if ($type === 4) {
            $pc = $this->solvePCaptcha($cookie, $ua, $cookieFile);
            if (!$pc) return;
        } elseif ($type === 0) {
            $tmp = $this->solveIcon($body, $cookie, $ua, $cookieFile);
            if (!$tmp) return;
            $ic = $tmp;
        } elseif ($type === 2) {
            $this->sendTelegram("⚡ hCaptcha yechilmoqda...");
            $hcap = $this->solver->hcaptcha(HOST . "faucet.php");
            if (!$hcap) {
                $this->sendTelegram("❌ hCaptcha yechilmadi!");
                return;
            }
            $this->sendTelegram("✅ hCaptcha muvaffaqiyatli yechildi!");
        } else {
            $type = 1;
            $this->sendTelegram("⚡ reCAPTCHA yechilmoqda...");
            $g = $this->solver->recaptcha(HOST . "faucet.php");
            if (!$g) {
                $this->sendTelegram("❌ reCAPTCHA yechilmadi!");
                return;
            }
            $this->sendTelegram("✅ reCAPTCHA muvaffaqiyatli yechildi!");
        }

        $hash = $this->makeHash($hashKey);
        $postArr = array_merge([
            "action"               => "claim_hourly_faucet",
            "hash"                 => $hash,
            "ft"                   => $ft,
            "captcha_type"         => $type,
            "g-recaptcha-response" => $g,
            "h-captcha-response"   => $hcap,
            "c_captcha_response"   => "",
            "pcaptcha_token"       => $pc,
            "csrf_test_name"       => $csrf,
        ], $ic);

        $post = http_build_query($postArr);
        $raw  = $this->curl(HOST . "process.php", $this->hdr($cookie, $ua, true), $post, $cookieFile)[1];
        $claim = json_decode($raw, true);

        if (!is_array($claim) || !isset($claim["mes"])) {
            if (preg_match_all('/\{[^{}]*"ret"\s*:\s*(\d+)[^{}]*"mes"\s*:\s*"([^"]*)"[^{}]*\}/', $raw, $mm, PREG_SET_ORDER)) {
                $last = end($mm);
                $claim = ["ret" => (int)$last[1], "mes" => $last[2]];
            } elseif (preg_match_all('/"mes"\s*:\s*"([^"]+)"/', $raw, $mm)) {
                $claim = ["ret" => 0, "mes" => end($mm[1])];
            }
        }

        if (is_array($claim) && !empty($claim["ret"])) {
            Display::ok($claim["mes"] ?? "Success");
            $newD = $this->dashboard($cookie, $ua, $cookieFile);
            Display::info("Yangi Balans", $newD["balance"] . " TON");
            Display::info("API Balans", $this->solver->balance());

            $this->sendTelegram("✅ TONPICK Claim Muvaffaqiyatli!\n\n👤 User: {$d['username']}\n💰 Balans: {$newD['balance']} TON\n⚡ API Bal: " . $this->solver->balance() . "\n💬 Xabar: " . ($claim["mes"] ?? "Success"));
        } else {
            $msg = is_array($claim) ? ($claim["mes"] ?? "Noma'lum xatolik") : "Javobni o'qib bo'lmadi";
            Display::err($msg);
            $this->sendTelegram("⚠️ Akkaunt: {$d['username']}\n❌ Xatolik: {$msg}");
        }
        Display::line();
    }

        private function mainLoop() {
        while (true) {
            $this->config = $this->getConfig();
            $accounts = $this->config["accounts"] ?? [];

            if (empty($accounts)) {
                Display::warn("Hech qanday akkaunt topilmadi!");
                sleep(10);
                continue;
            }

            foreach ($accounts as $index => $acc) {
                try {
                    $this->processAccount($acc, $index);
                } catch (Exception $e) {
                    Display::err("Akkaunt #" . ($index + 1) . " xatolik: " . $e->getMessage());
                }
                
                // BU YERGA TANAFFUS QO'SHILADI (Masalan: 15 dan 25 sekundgacha tasodifiy kutish)
                $pause = rand(15, 25);
                Display::warn("Keyingi akkauntga o'tish uchun {$pause} sekund kutilyapti...");
                sleep($pause);
            }

            Display::warn("Barcha akkauntlar tekshirildi. Navbatdagi sikl boshlanishini kutamiz...");
            $this->timer(3600);
        }
    }
}

new Bot();