<?php
/**
 * TONPICK Professional Bot v5.0
 * - Dynamic hash key (har safar sahifadan)
 * - Captcha auto-detect
 * - reCAPTCHA / hCaptcha (Multibot|Xevil)
 * - pCaptcha / IconCaptcha (Gemini)
 * - Auto hourly claim
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

const TITLE         = "TONPICK";
const VERSION       = "5.0.0";
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
        echo c . "│" . p . "  Mode : " . h . "Auto Hourly + Gemini" . str_repeat(" ", 12) . c . "│" . n;
        echo c . "│" . p . "  Site : " . k . "tonpick.game" . str_repeat(" ", 18) . c . "│" . n;
        echo c . "└" . str_repeat("─", 44) . "┘" . n;
        echo c . str_repeat("─", 46) . n;
    }
    static function line() { echo c . str_repeat("─", 46) . n; }
    static function info($l, $v) {
        echo h . " [" . p . str_pad($l, 10) . h . "] → " . p . $v . n;
    }
    static function ok($m) { echo h . " [✓] " . p . $m . n; }
    static function err($m) { echo m . " [!] " . p . $m . n; }
    static function warn($m) { echo k . " [!] " . p . $m . n; }
    static function input($m) { return m . " ╭[" . p . "Input " . $m . m . "]" . n . m . " ╰> " . h; }
    static function title($t) { echo bg . p . str_pad(strtoupper($t), 46, " ", STR_PAD_BOTH) . d . n; }
}

class F {
    public $cfgFile = "config_tonpick.json";

    public function curl($url, $headers = [], $post = null) {
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
                CURLOPT_COOKIEFILE => "cookie_ton.txt",
                CURLOPT_COOKIEJAR => "cookie_ton.txt",
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
    
        // Bot klassi ichiga qo'shiladigan yordamchi funksiya:
        private function saveStatus($username, $balance, $api_bal, $next_time) {
        $status = [
            "username"  => $username,
            "balance"   => $balance,
            "api_bal"   => $api_bal,
            "next_time" => $next_time
        ];
        // Har bir akkaunt o'z papkasida status.json hosil qilishi uchun
        @file_put_contents(__DIR__ . "/status.json", json_encode($status, JSON_PRETTY_PRINT));
    }

    
    public function setCfg($key) {
        $c = file_exists($this->cfgFile) ? json_decode(file_get_contents($this->cfgFile), true) : [];
        if (!empty($c[$key])) return $c[$key];
        $v = trim(readline(Display::input($key))); echo n;
        $c[$key] = $v;
        file_put_contents($this->cfgFile, json_encode($c, JSON_PRETTY_PRINT));
        return $v;
    }

    public function getCfg($key) {
        if (!file_exists($this->cfgFile)) return null;
        $c = json_decode(file_get_contents($this->cfgFile), true);
        return $c[$key] ?? null;
    }

    public function timer($sec) {
        $end = time() + $sec + rand(2, 5);
        $s = ["─", "/", "│", "\\"]; $i = 0;
        while (true) {
            $l = $end - time();
            if ($l < 1) break;
            $hh = str_pad(floor($l / 3600), 2, "0", STR_PAD_LEFT);
            $mm = str_pad(floor(($l % 3600) / 60), 2, "0", STR_PAD_LEFT);
            $ss = str_pad($l % 60, 2, "0", STR_PAD_LEFT);
            echo "\r  " . c . $s[$i % 4] . p . " Next: " . h . "$hh:$mm:$ss" . p . "    ";
            usleep(250000); $i++;
        }
        echo "\r" . str_repeat(" ", 40) . "\r";
    }

    public function cookiesFromHeader($header) {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $m);
        $out = [];
        foreach ($m[1] as $item) { parse_str($item, $x); $out = array_merge($out, $x); }
        return $out;
    }

    /** Dinamik hash — sahifadagi key bilan */
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
            if (!empty($j["error"])) {
                Display::warn("Gemini($model): " . ($j["error"]["message"] ?? "err"));
                continue;
            }
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

    public function __construct(F $f) {
        if (empty($f->getCfg("type"))) {
            echo n; Display::title("CAPTCHA API");
            echo h . "  [1] Multibot" . n . h . "  [2] Xevil" . n . n;
            $f->setCfg("type"); Display::line();
        }
        if ($f->getCfg("type") == 1) {
            $this->url = "http://api.multibot.in/";
            $this->key = $f->setCfg("multibot_apikey");
        } else {
            $this->url = "https://sctg.xyz/";
            $this->key = $f->setCfg("xevil_apikey") . "|SOFTID1204538927";
        }
        $gk = $f->getCfg("gemini_apikey");
        if (empty($gk)) {
            Display::warn("Gemini key (pCaptcha/IconCaptcha uchun)");
            $gk = $f->setCfg("gemini_apikey");
        }
        $this->gemini = new Gemini($gk);
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
            echo "\r  " . k . "Bypass $label $p%" . p . "   ";
            $r = $this->res($id);
            if (($r["request"] ?? "") == "CAPCHA_NOT_READY") {
                $p = min(95, $p + rand(8, 14)); sleep(5); continue;
            }
            if (!empty($r["status"])) {
                echo "\r  " . h . "[✓] $label solved" . p . "          " . n;
                return $r["request"];
            }
            Display::err("$label failed");
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
    public $cookie, $ua, $solver;

    public function __construct() {
        Display::banner();
        if (empty($this->getCfg("cookie"))) {
            Display::info("Register", REFF);
            Display::line();
        }
        $this->cookie = $this->setCfg("cookie");
        $this->ua = $this->setCfg("user_agent");
        $this->solver = new Solver($this);

        Display::banner();
        $d = $this->dashboard();
        if (!$d["username"]) {
            Display::err("Login failed — cookie/user_agent tekshiring");
            exit;
        }
        Display::info("Username", $d["username"]);
        Display::info("Balance", $d["balance"] . " TON");
        Display::info("API Bal", $this->solver->balance());
        $this->saveStatus($d["username"], $d["balance"] . " TON", $this->solver->balance(), "00:55:43");
        
        Display::line();
        Display::title("AUTO HOURLY");
        $this->loop();
    }

    private function hdr($ajax = false) {
        $h = [
            "Host: " . parse_url(HOST)["host"],
            "Cookie: " . $this->cookie,
            "User-Agent: " . $this->ua,
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

    public function dashboard() {
        $body = $this->curl(HOST, $this->hdr())[1];
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
        if ($found) {
            $list = [];
            foreach ($found as $v => $info) $list[] = $info["name"] . ($info["selected"] ? "*" : "");
            Display::info("Options", implode(", ", $list));
        }

        $force = $this->getCfg("force_captcha");
        if ($force !== null && $force !== "") {
            $v = (int)$force;
            $names = [0 => "IconCaptcha", 1 => "reCAPTCHA", 2 => "hCaptcha", 4 => "pCaptcha"];
            Display::warn("FORCE type $v (" . $names[$v] . ")");
            return $v;
        }
        foreach ($found as $v => $info) if ($info["selected"]) return $v;
        foreach ([4, 0, 2, 1] as $v) if (isset($found[$v])) return $v;
        return 1;
    }

    private function extractHashKey($body) {
        if (preg_match("/get_hash\s*\(\s*event\s*,\s*'([a-f0-9]+)'/i", $body, $m)) return $m[1];
        return "";
    }

    private function solvePCaptcha() {
        Display::info("Captcha", "pCaptcha + Gemini");
        $r = $this->curl(HOST . "generate_pcaptcha.php", $this->hdr(true));
        $data = json_decode($r[1], true);
        if (!$data || empty($data["image"])) {
            Display::err("generate_pcaptcha fail");
            return false;
        }
        Display::info("Question", $data["question"] ?? "-");
        $img = $this->curl($data["image"], $this->hdr())[1];
        if (strlen($img) < 400) { Display::err("image download fail"); return false; }

        $boxes = $this->solver->gemini->pCaptcha($data["question"] ?? "", $img);
        if ($boxes === false) return false;

        $post = http_build_query([
            "action" => "verify_pcaptcha",
            "selected_boxes" => json_encode(array_values($boxes))
        ]);
        $vr = json_decode($this->curl(HOST . "process.php", $this->hdr(true), $post)[1], true);
        if (empty($vr["success"]) || empty($vr["token"])) {
            Display::err("pCaptcha verify: " . ($vr["message"] ?? "fail"));
            return false;
        }
        Display::ok("pCaptcha token OK");
        return $vr["token"];
    }

    private function solveIcon($body) {
        Display::info("Captcha", "IconCaptcha + Gemini");
        $token = "";
        if (preg_match("/_iconcaptcha-token' value='([^']+)'/", $body, $m)) $token = $m[1];
        if (!$token && preg_match('/name=["\']_iconcaptcha-token["\'][^>]*value=["\']([^"\']+)/', $body, $m)) $token = $m[1];
        if (!$token) { Display::err("IC token yo'q"); return false; }

        $wid = $this->uuid();
        $ts = (int)(microtime(true) * 1000);
        $payload = base64_encode(json_encode([
            "widgetId" => $wid, "action" => "LOAD", "theme" => "light",
            "token" => $token, "timestamp" => $ts, "initTimestamp" => $ts
        ], JSON_UNESCAPED_SLASHES));
        $h = array_merge($this->hdr(true), ["X-IconCaptcha-Token: $token"]);
        $raw = trim($this->curl(IC_ENDPOINT, $h, "payload=" . urlencode($payload))[1]);
        $data = @json_decode(base64_decode($raw), true);
        if (!$data) $data = @json_decode($raw, true);
        if (!$data || empty($data["challenge"] ?? $data["base64"] ?? null)) {
            Display::err("IC LOAD fail");
            return false;
        }
        $img = $data["challenge"] ?? $data["base64"];
        $cid = $data["identifier"] ?? $data["challengeId"] ?? "";
        sleep(2);
        $idx = $this->solver->gemini->iconCaptcha($img);
        if ($idx === false) return false;
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
            $raw = trim($this->curl(IC_ENDPOINT, $h, "payload=" . urlencode($payload))[1]);
            $d = @json_decode(base64_decode($raw), true);
            if (!$d) $d = @json_decode($raw, true);
            if (!empty($d["completed"])) {
                Display::ok("IconCaptcha completed");
                return [
                    "_iconcaptcha-token" => $token,
                    "ic-rq" => "1", "ic-wid" => $wid, "ic-cid" => $cid, "ic-hp" => ""
                ];
            }
        }
        Display::err("IC select fail");
        return false;
    }

    private function loop() {
        $fail = 0;
        while (true) {
            $r = $this->curl(HOST . "faucet.php", $this->hdr());
            $body = $r[1];
            $header = $r[0];

            if (stripos($body, "Just a moment") !== false) {
                Display::err("Cloudflare — cookie yangilang"); exit;
            }

            // CSRF
            $ck = $this->cookiesFromHeader($header);
            $csrf = $ck["csrf_cookie_name"] ?? "";
            if (!$csrf && preg_match('/csrf_cookie_name=([^;]+)/', $this->cookie, $m)) $csrf = trim($m[1]);

            $ft = "";
            if (preg_match('/_ft=([^;]+)/', $this->cookie, $m)) $ft = $m[1];

            // Dinamik hash key
            $hashKey = $this->extractHashKey($body);
            if (!$hashKey) {
                Display::err("Hash key topilmadi");
                sleep(5); continue;
            }
            Display::info("HashKey", substr($hashKey, 0, 16) . "...");

            $type = $this->detect($body);
            $names = [0 => "IconCaptcha", 1 => "reCAPTCHA", 2 => "hCaptcha", 4 => "pCaptcha"];
            Display::info("Using", $names[$type] ?? "type$type");

            $g = $hcap = $pc = "";
            $ic = [
                "_iconcaptcha-token" => "",
                "ic-rq" => "", "ic-wid" => "", "ic-cid" => "", "ic-hp" => ""
            ];

            if ($type === 4) {
                $pc = $this->solvePCaptcha();
                if (!$pc) { $fail++; sleep(8); if ($fail >= 5) { $this->timer(3600); $fail = 0; } continue; }
            } elseif ($type === 0) {
                $tmp = $this->solveIcon($body);
                if (!$tmp) { $fail++; sleep(8); if ($fail >= 5) { $this->timer(3600); $fail = 0; } continue; }
                $ic = $tmp;
            } elseif ($type === 2) {
                $hcap = $this->solver->hcaptcha(HOST . "faucet.php");
                if (!$hcap) { $fail++; sleep(8); continue; }
            } else {
                $type = 1;
                $g = $this->solver->recaptcha(HOST . "faucet.php");
                if (!$g) { $fail++; sleep(8); continue; }
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
            $raw  = $this->curl(HOST . "process.php", $this->hdr(true), $post)[1];
            $claim = json_decode($raw, true);

            // 2 ta JSON ketma-ket kelsa — oxirgisini olish
            if (!is_array($claim) || !isset($claim["mes"])) {
                if (preg_match_all('/\{[^{}]*"ret"\s*:\s*(\d+)[^{}]*"mes"\s*:\s*"([^"]*)"[^{}]*\}/', $raw, $mm, PREG_SET_ORDER)) {
                    $last = end($mm);
                    $claim = ["ret" => (int)$last[1], "mes" => $last[2]];
                } elseif (preg_match_all('/"mes"\s*:\s*"([^"]+)"/', $raw, $mm)) {
                    $claim = ["ret" => 0, "mes" => end($mm[1])];
                }
            }

            // ===== SUCCESS =====
            if (is_array($claim) && !empty($claim["ret"])) {
                $fail = 0;
                Display::ok($claim["mes"] ?? "Success");
                if (isset($claim["num"])) Display::info("Number", $claim["num"]);
                $d = $this->dashboard();
                Display::info("Balance", $d["balance"] . " TON");
                Display::info("API Bal", $this->solver->balance());
                Display::line();
                $this->timer(3600);
                continue;
            }

            // ===== ERROR / TIMER =====
            $msg = is_array($claim) ? ($claim["mes"] ?? "") : "";
            if ($msg === "" && is_string($raw)) {
                $msg = substr(preg_replace('/\s+/', ' ', strip_tags($raw)), 0, 180);
            }
            Display::err($msg !== "" ? $msg : "Unknown response");
            Display::line();

            $waitSec = 0;
            if (preg_match('/wait\s+for\s+(\d+)\s+minute/i', $msg, $m)) {
                $waitSec += ((int)$m[1]) * 60;
            }
            if (preg_match('/(\d+)\s+second/i', $msg, $m)) {
                $waitSec += (int)$m[1];
            }
            if ($waitSec < 1 && preg_match('/(\d+)\s*min/i', $msg, $m)) {
                $waitSec = ((int)$m[1]) * 60;
            }

            if ($waitSec > 0) {
                Display::warn("Kutish: {$waitSec}s — captcha qayta yechilmaydi");
                $this->timer($waitSec + rand(5, 20));
                continue;
            }

            if (stripos($msg, "wait") !== false || stripos($msg, "refresh") !== false) {
                Display::warn("Timer aniq emas — 35 daqiqa kutamiz");
                $this->timer(35 * 60);
                continue;
            }

            $fail++;
            sleep(10);
            if ($fail >= 5) {
                $this->timer(3600);
                $fail = 0;
            }
        } // while
    }     // loop()
}         // class Bot

new Bot();