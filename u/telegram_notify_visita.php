<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");


// Configuración de Telegram
$botToken = '7497890468:AAGGItTPfO8JXfESTE8QV_NU22qc-tCsU7A';
$chatId = '-5242449077';


// Obtener datos del visitante
$data = json_decode(file_get_contents('php://input'), true);

// Obtener IP del usuario
$ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

// Obtener información de geolocalización
function getGeoInfo($ip) {
    $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp,org,as,lat,lon,timezone,query";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$geoInfo = getGeoInfo($ip);

// Obtener información adicional
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
$referer = $_SERVER['HTTP_REFERER'] ?? 'Directo';
$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$urlCompleta = $protocol . '://' . $url;

// Detectar sistema operativo
function detectOS($userAgent) {
    $os = 'Desconocido';
    if (preg_match('/windows nt 10/i', $userAgent)) $os = 'Windows 10';
    elseif (preg_match('/windows nt 11/i', $userAgent)) $os = 'Windows 11';
    elseif (preg_match('/windows/i', $userAgent)) $os = 'Windows';
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'macOS';
    elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';
    elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
    elseif (preg_match('/iphone|ipad/i', $userAgent)) $os = 'iOS';
    return $os;
}

$os = detectOS($userAgent);

// Construir mensaje
$mensaje = "🚨 <b>Un usuario ingresó a la página</b> 🚨\n\n";
$mensaje .= "📍 <b>Página:</b> {$urlCompleta}\n";
$mensaje .= "🌐 <b>IP:</b> <code>{$ip}</code>\n";

if ($geoInfo && $geoInfo['status'] === 'success') {
    $mensaje .= "🌎 <b>País:</b> {$geoInfo['country']}\n";
    $mensaje .= "📌 <b>Departamento/Región:</b> {$geoInfo['regionName']}\n";
    $mensaje .= "🏙️ <b>Ciudad:</b> {$geoInfo['city']}\n";
    $mensaje .= "🏢 <b>Compañía/ISP:</b> {$geoInfo['org']}\n";
    $mensaje .= "🔢 <b>ASN:</b> {$geoInfo['as']}\n";
    $mensaje .= "🕐 <b>Zona horaria:</b> {$geoInfo['timezone']}\n";
    $mensaje .= "📍 <b>Coords:</b> {$geoInfo['lat']}, {$geoInfo['lon']}\n";
}

$mensaje .= "🔗 <b>Referencia:</b> " . ($referer === 'Directo' ? 'Directo' : 'Referido') . "\n";
$mensaje .= "🌐 <b>Referer:</b> " . ($referer === 'Directo' ? 'N/A' : $referer) . "\n";
$mensaje .= "💻 <b>Sistema operativo:</b> {$os}\n";
$mensaje .= "🖥️ <b>User-Agent:</b> <code>{$userAgent}</code>\n";
$mensaje .= "\n⏰ <b>Hora:</b> " . date('Y-m-d H:i:s');

// Enviar a Telegram
function enviarTelegram($botToken, $chatId, $mensaje) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'response' => json_decode($response, true)
    ];
}

$resultado = enviarTelegram($botToken, $chatId, $mensaje);

echo json_encode([
    'success' => $resultado['success'],
    'message' => $resultado['success'] ? 'Notificación enviada' : 'Error al enviar'
]);
?>