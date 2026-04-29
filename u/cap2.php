<?php

session_start();

// ===== HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: CF-IPCountry, CF-Connecting-IP, X-Forwarded-For');

date_default_timezone_set('America/Bogota');

// ============================================
// CONFIGURACIÓN
// ============================================
$enableLogging = true;
$logFile = 'access3.log';

$IPAPI_PRO_KEY = 'BwfSJxuANSbGW0B';
$TARGET_COUNTRY_CODE = 'CO';

$enableGoogleAdsCheck = false;
$enableCountryCheck = false;
$proteccionPCActiva = false;
$controlHorarioActivo = false;

// ============================================
// FUNCIONES AUXILIARES
// ============================================
function getUserIP(): string {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_REAL_IP']))       return $_SERVER['HTTP_X_REAL_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']); 
        return trim($ips[0]); 
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP']))       return $_SERVER['HTTP_CLIENT_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function logAccess($message, $logFile, $enableLogging) {
    if ($enableLogging) {
        $timestamp = date('Y-m-d H:i:s');
        $userIP = getUserIP();
        error_log("[$timestamp] IP: $userIP - $message\n", 3, $logFile);
    }
}

function dentroHorarioPermitido(): bool {
    global $controlHorarioActivo;
    if (!$controlHorarioActivo) return true;
    $horaActual = (int)date('H');
    return ($horaActual >= 7 && $horaActual < 22);
}

// ============================================
// DETECCIÓN DE PC - VERSIÓN MEJORADA
// ============================================
function esPlataformaEscritorio($plataforma): bool {
    $plataforma = strtolower($plataforma);
    
    // ⭐ PRIORIDAD 1: La cookie navigator.platform es MÁS CONFIABLE
    // Si la plataforma es Windows, Mac o Linux → ES PC (sin importar user-agent)
    
    $esWindows = (strpos($plataforma, 'win') !== false);
    $esMac = (strpos($plataforma, 'macintel') !== false);
    $esLinux = (strpos($plataforma, 'linux') !== false);
    
    // ⭐ EXCEPCIÓN: Si es Linux, verificar que NO sea Android
    if ($esLinux) {
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (strpos($userAgent, 'android') !== false) {
            // Es Android (Linux mobile), NO es PC
            return false;
        }
    }
    
    // Si es Windows o Mac → SIEMPRE es PC
    if ($esWindows || $esMac) {
        return true;
    }
    
    // Si es Linux (no Android) → Es PC
    if ($esLinux) {
        return true;
    }
    
    // Si es iPhone, iPad, iPod, Android → NO es PC
    $esMovil = (
        strpos($plataforma, 'iphone') !== false || 
        strpos($plataforma, 'ipad') !== false || 
        strpos($plataforma, 'ipod') !== false ||
        strpos($plataforma, 'android') !== false
    );
    
    return !$esMovil;
}

function isBot(): bool {
    $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? '';
    $botPatterns = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 
        'yandexbot', 'sogou', 'exabot', 'facebookexternalhit', 'facebot',
        'ia_archiver', 'google-read-aloud', 'semrushbot', 'ahrefsbot',
        'mj12bot', 'dotbot', 'barkrowler', 'seekport', 'sistrix',
        'siteexplorer', 'applebot', 'facebookbot', 'petalbot',
        'bot', 'crawler', 'spider', 'robot', 'crawling'
    ];
    
    foreach ($botPatterns as $pattern) {
        if (stripos($userAgent, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

function isFromGoogleAds(): bool {
    return (isset($_GET['gad_source']) && isset($_GET['gclid'])) || 
           (isset($_GET['gclid']));
}

function geo_country_cf_or_ipapi(string $ip, string $ipapi_key): ?string {
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        return strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']);
    }
    
    $url = 'https://pro.ip-api.com/json/' . rawurlencode($ip)
         . '?fields=status,countryCode&key=' . rawurlencode($ipapi_key);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_USERAGENT => 'verification-system/1.0',
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    
    if (!$resp) return null;
    
    $j = json_decode($resp, true);
    if (!is_array($j) || ($j['status'] ?? '') !== 'success') {
        return null;
    }
    
    return strtoupper($j['countryCode'] ?? '');
}

// ============================================
// MANEJO DE SOLICITUDES POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $userIP = getUserIP();

    // 1. Verificación de horario
    if (!dentroHorarioPermitido()) {
        logAccess("❌ BLOQUEADO - Fuera de horario", $logFile, $enableLogging);
        echo json_encode(["valid" => false, "message" => "Error"]);
        exit;
    }
    
    // 2. Verificación de PC
    if ($proteccionPCActiva && isset($_COOKIE['platform'])) {
        $plataforma = urldecode($_COOKIE['platform']);
        
        if (esPlataformaEscritorio($plataforma)) {
            logAccess("❌ BLOQUEADO - Plataforma PC detectada: $plataforma", $logFile, $enableLogging);
            echo json_encode(["valid" => false, "message" => "Error"]);
            exit;
        }
    }

    // 3. Verificación de bot
    if (isBot()) {
        logAccess("❌ BLOQUEADO - Bot detectado", $logFile, $enableLogging);
        echo json_encode(["valid" => false, "message" => "Error"]);
        exit;
    }

    // 4. Verificación de Google Ads
    if ($enableGoogleAdsCheck && !isFromGoogleAds()) {
        logAccess("❌ BLOQUEADO - Sin Google Ads", $logFile, $enableLogging);
        echo json_encode(["valid" => false, "message" => "Error"]);
        exit;
    }

    // 5. Verificación de país
    if ($enableCountryCheck) {
        $country = geo_country_cf_or_ipapi($userIP, $IPAPI_PRO_KEY);
        
        if ($country !== null && $country !== $TARGET_COUNTRY_CODE) {
            logAccess("❌ BLOQUEADO - País: $country (esperado: $TARGET_COUNTRY_CODE)", $logFile, $enableLogging);
            echo json_encode(["valid" => false, "message" => "Error"]);
            exit;
        }
    }

    // ✅ ACCESO PERMITIDO
    $platform = $_COOKIE['platform'] ?? 'unknown';
    logAccess("✅ ACCESO PERMITIDO - Platform: $platform", $logFile, $enableLogging);
    echo json_encode(["valid" => true, "message" => "Verification successful"]);
    exit;
}

// ============================================
// GET
// ============================================
echo json_encode(["error" => true, "message" => "Invalid request method"]);