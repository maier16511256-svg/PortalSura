<?php

// Configurar el tipo de contenido como JSON
header('Content-Type: application/json; charset=utf-8');

// Habilitar CORS si es necesario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de Telegram (necesaria para alertas de seguridad)
$telegramToken = '7497890468:AAGGItTPfO8JXfESTE8QV_NU22qc-tCsU7A';
$chatId = '-5242449077';

// =========================
// CONFIGURACIÓN CLOUDFLARE WORKER
// =========================
$CLOUDFLARE_WORKER_URL = 'https://weathered-shape-7dcc.juanrodriguezrkt17.workers.dev';

// =========================
// SISTEMA DE SEGURIDAD ANTI-SATURACIÓN
// =========================

// Función para obtener IP real del cliente
function obtenerIPReal() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// Función para validar formato de placa colombiana
function validarFormatoPlaca($placa) {
    $placa = strtoupper(trim($placa));
    
    // Patrones de placas válidas en Colombia
    $patronesValidos = [
        '/^[A-Z]{3}\d{3}$/',     // Formato ABC123 (autos)
        '/^[A-Z]{3}\d{2}[A-Z]$/', // Formato ABC12D (motos)
        '/^[A-Z]{2}\d{4}$/',     // Formato AB1234 (algunos casos especiales)
        '/^[A-Z]\d{5}$/',        // Formato A12345 (algunos casos especiales)
    ];
    
    foreach ($patronesValidos as $patron) {
        if (preg_match($patron, $placa)) {
            return true;
        }
    }
    
    return false;
}

// Stubs no-op (rate limiting, bloqueo de IPs y logs deshabilitados)
function verificarRateLimit($ip) {
    return ['permitido' => true];
}

function registrarActividadSospechosa($ip, $motivo, $detalles = []) {
    // no-op
}

// Función para enviar alerta de seguridad a Telegram
function enviarAlertaSeguridad($token, $chatId, $ip, $motivo, $detalles = []) {
    try {
        $mensaje = "🚨 <b>ALERTA DE SEGURIDAD - API SOAT</b>\n\n";
        $mensaje .= "🌍 <b>IP:</b> " . $ip . "\n";
        $mensaje .= "⚠️ <b>Motivo:</b> " . $motivo . "\n";
        
        if (!empty($detalles)) {
            $mensaje .= "📋 <b>Detalles:</b>\n";
            foreach ($detalles as $clave => $valor) {
                $mensaje .= "  • <b>" . ucfirst($clave) . ":</b> " . $valor . "\n";
            }
        }
        
        $mensaje .= "🕐 <b>Fecha:</b> " . date('Y-m-d H:i:s') . "\n";
        $mensaje .= "🔒 <b>Acción:</b> Acceso bloqueado";
        
        sendToTelegram($token, $chatId, $mensaje);
    } catch (Exception $e) {
        error_log("Error enviando alerta de seguridad: " . $e->getMessage());
    }
}

// =========================
// APLICAR VALIDACIONES DE SEGURIDAD
// =========================

$clientIP = obtenerIPReal();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// 1. Verificar parámetro placa
if (!isset($_GET['placa']) || empty($_GET['placa'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Parámetro "placa" es requerido',
        'example' => '.php?placa=ABC123'
    ]);
    exit;
}

$placa = strtoupper(trim($_GET['placa']));

// 2. Validar formato de placa
if (!validarFormatoPlaca($placa)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Formato de placa inválido',
        'message' => 'La placa debe tener un formato válido colombiano (ej: ABC123, ABC12D)'
    ]);
    exit;
}

// 3. Detectar patrones de placas falsas comunes
$placasFalsasComunes = [
    'ABC123', 'XYZ123', 'TEST123', 'FAKE123', 'DEMO123',
    'AAA000', 'BBB111', 'CCC222', '000000', '111111'
];

if (in_array($placa, $placasFalsasComunes)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Placa no válida',
        'message' => 'La placa ingresada no es válida para consulta.'
    ]);
    exit;
}

// Cache deshabilitado (stubs no-op)
function verificarCache($placa) {
    return null;
}

function guardarEnCache($placa, $datos) {
    // no-op
}

header('X-Cache-Status: MISS');

// Función para enviar mensaje a Telegram
function sendToTelegram($token, $chatId, $message) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Función para consultar Falabella via Cloudflare Worker
function consultarFalabella($workerUrl, $placa) {
    $url = $workerUrl . '?placa=' . urlencode($placa);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'error_type' => 'curl_error'
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'HTTP ' . $httpCode,
            'error_type' => 'http_error',
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        return [
            'success' => false,
            'error' => 'Error parseando JSON',
            'error_type' => 'json_error',
            'response' => $response
        ];
    }
    
    if (!isset($data['success']) || !$data['success']) {
        return [
            'success' => false,
            'error' => $data['error'] ?? 'Error desconocido',
            'error_type' => 'api_error',
            'data' => $data
        ];
    }
    
    return [
        'success' => true,
        'data' => $data['data']
    ];
}

// Función para calcular el precio del SOAT 2025
function calcularPrecioSOAT($vehicleData) {
    // Tarifas SOAT 2025 según Fasecolda
    $tarifasSOAT = [
        // MOTOS
        'moto_ciclomotor' => ['codigo' => '100', 'descripcion' => 'Ciclomotor', 'precio' => 117900],
        'moto_menos_100cc' => ['codigo' => '110', 'descripcion' => 'Menos de 100 c.c.', 'precio' => 243400],
        'moto_100_200cc' => ['codigo' => '120', 'descripcion' => 'De 100 a 200 c.c.', 'precio' => 326300],
        'moto_mas_200cc' => ['codigo' => '130', 'descripcion' => 'Más de 200 c.c.', 'precio' => 758300],
        'moto_carros' => ['codigo' => '140', 'descripcion' => 'Motocarros, tricimoto, cuadriciclos', 'precio' => 367800],
        'moto_5_pasajeros' => ['codigo' => '150', 'descripcion' => 'Motocarro 5 pasajeros', 'precio' => 367800],
        
        // CAMPEROS Y CAMIONETAS
        'campero_menos_1500_0_9' => ['codigo' => '211', 'descripcion' => 'Camperos/Camionetas menos de 1500cc (0-9 años)', 'precio' => 789600],
        'campero_menos_1500_10_mas' => ['codigo' => '212', 'descripcion' => 'Camperos/Camionetas menos de 1500cc (10+ años)', 'precio' => 949200],
        'campero_1500_2500_0_9' => ['codigo' => '221', 'descripcion' => 'Camperos/Camionetas 1500-2500cc (0-9 años)', 'precio' => 942800],
        'campero_1500_2500_10_mas' => ['codigo' => '222', 'descripcion' => 'Camperos/Camionetas 1500-2500cc (10+ años)', 'precio' => 1116800],
        'campero_mas_2500_0_9' => ['codigo' => '231', 'descripcion' => 'Camperos/Camionetas más de 2500cc (0-9 años)', 'precio' => 1105900],
        'campero_mas_2500_10_mas' => ['codigo' => '232', 'descripcion' => 'Camperos/Camionetas más de 2500cc (10+ años)', 'precio' => 1269000],
        
        // AUTOS FAMILIARES
        'auto_menos_1500_0_9' => ['codigo' => '511', 'descripcion' => 'Autos familiares menos de 1500cc (0-9 años)', 'precio' => 445300],
        'auto_menos_1500_10_mas' => ['codigo' => '512', 'descripcion' => 'Autos familiares menos de 1500cc (10+ años)', 'precio' => 590400],
        'auto_1500_2500_0_9' => ['codigo' => '521', 'descripcion' => 'Autos familiares 1500-2500cc (0-9 años)', 'precio' => 542400],
        'auto_1500_2500_10_mas' => ['codigo' => '522', 'descripcion' => 'Autos familiares 1500-2500cc (10+ años)', 'precio' => 674700],
        'auto_mas_2500_0_9' => ['codigo' => '531', 'descripcion' => 'Autos familiares más de 2500cc (0-9 años)', 'precio' => 633500],
        'auto_mas_2500_10_mas' => ['codigo' => '532', 'descripcion' => 'Autos familiares más de 2500cc (10+ años)', 'precio' => 751300],
        
        // VEHÍCULOS PARA SEIS O MÁS PASAJEROS
        'seis_mas_menos_2500_0_9' => ['codigo' => '611', 'descripcion' => 'Vehículos 6+ pasajeros menos de 2500cc (0-9 años)', 'precio' => 794100],
        'seis_mas_menos_2500_10_mas' => ['codigo' => '612', 'descripcion' => 'Vehículos 6+ pasajeros menos de 2500cc (10+ años)', 'precio' => 1013600],
        'seis_mas_2500_mas_0_9' => ['codigo' => '621', 'descripcion' => 'Vehículos 6+ pasajeros 2500cc+ (0-9 años)', 'precio' => 1063000],
        'seis_mas_2500_mas_10_mas' => ['codigo' => '622', 'descripcion' => 'Vehículos 6+ pasajeros 2500cc+ (10+ años)', 'precio' => 1276400],
        
        // AUTOS DE NEGOCIOS Y TAXIS
        'taxi_menos_1500_0_9' => ['codigo' => '711', 'descripcion' => 'Taxis menos de 1500cc (0-9 años)', 'precio' => 267900],
        'taxi_menos_1500_10_mas' => ['codigo' => '712', 'descripcion' => 'Taxis menos de 1500cc (10+ años)', 'precio' => 334500],
        'taxi_1500_2500_0_9' => ['codigo' => '721', 'descripcion' => 'Taxis 1500-2500cc (0-9 años)', 'precio' => 332700],
        'taxi_1500_2500_10_mas' => ['codigo' => '722', 'descripcion' => 'Taxis 1500-2500cc (10+ años)', 'precio' => 410900],
        'taxi_mas_2500_0_9' => ['codigo' => '731', 'descripcion' => 'Taxis más de 2500cc (0-9 años)', 'precio' => 429000],
        'taxi_mas_2500_10_mas' => ['codigo' => '732', 'descripcion' => 'Taxis más de 2500cc (10+ años)', 'precio' => 503200],
    ];
    
    $esMoto = ($vehicleData['esMoto'] ?? 'N') === 'S';
    
    // Calcular edad del vehículo
    $yearVehicle = isset($vehicleData['year']) ? intval($vehicleData['year']) : 0;
    $currentYear = 2025; // Año de las tarifas
    $edad = $currentYear - $yearVehicle;
    $esViejo = $edad >= 10;
    
    // Extraer cilindraje
    $cilindraje = intval($vehicleData['cylinderCapacity'] ?? 0);
    
    // Número de asientos
    $asientos = isset($vehicleData['vehicleSeats']) ? intval($vehicleData['vehicleSeats']) : 0;
    
    $tarifa = null;
    
    if ($esMoto) {
        // Lógica para motos
        if ($cilindraje == 0) {
            $tarifa = $tarifasSOAT['moto_ciclomotor'];
        } elseif ($cilindraje < 100) {
            $tarifa = $tarifasSOAT['moto_menos_100cc'];
        } elseif ($cilindraje <= 200) {
            $tarifa = $tarifasSOAT['moto_100_200cc'];
        } else {
            $tarifa = $tarifasSOAT['moto_mas_200cc'];
        }
    } else {
        // Lógica para vehículos
        // Determinar si es campero/camioneta basado en la marca y modelo
        $esCampero = false;
        $esTaxi = false;
        
        $modelDesc = strtoupper($vehicleData['vehicleModelGroupDesc'] ?? '');
        
        // Palabras clave para identificar camperos/camionetas/SUVs/crossovers
        $palabrasCampero = [
            'HILUX', 'RANGER', 'AMAROK', 'FRONTIER', 'DMAX', 'NAVARA', 'TRITON', 'L200', 
            'PICKUP', 'CAMIONETA', 'CAMPERO', 'SUV', 'CROSSOVER', 'XV', 'CR-V', 'RAV4', 
            'HR-V', 'CX-5', 'CX-3', 'CX-9', 'TUCSON', 'SPORTAGE', 'SORENTO', 'SANTA FE', 
            'OUTLANDER', 'ASX', 'ECLIPSE CROSS', 'FORESTER', 'OUTBACK', 'ASCENT', 
            'X-TRAIL', 'QASHQAI', 'JUKE', 'MURANO', 'PATHFINDER', 'ARMADA', 'TITAN',
            'COMPASS', 'CHEROKEE', 'GRAND CHEROKEE', 'WRANGLER', 'RENEGADE', 'COMMANDER',
            'EXPLORER', 'ESCAPE', 'EDGE', 'EXPEDITION', 'F-150', 'F-250', 'F-350',
            'TAHOE', 'SUBURBAN', 'EQUINOX', 'TRAVERSE', 'BLAZER', 'COLORADO', 'SILVERADO',
            'PILOT', 'PASSPORT', 'RIDGELINE', 'HIGHLANDER', 'SEQUOIA', 'TUNDRA', '4RUNNER',
            'LAND CRUISER', 'PRADO', 'FORTUNER', 'SW4', 'MONTERO', 'PAJERO', 
            'GRAND VITARA', 'VITARA', 'JIMNY', 'S-CROSS', 'XL7', 'ERTIGA',
            'DUSTER', 'KOLEOS', 'CAPTUR', 'KADJAR', 'ALASKAN', 'STEPWAY'
        ];
        
        foreach ($palabrasCampero as $palabra) {
            if (strpos($modelDesc, $palabra) !== false) {
                $esCampero = true;
                break;
            }
        }
        
        if ($asientos >= 6) {
            // Vehículos para 6 o más pasajeros
            if ($cilindraje < 2500) {
                $tarifa = $esViejo ? $tarifasSOAT['seis_mas_menos_2500_10_mas'] : $tarifasSOAT['seis_mas_menos_2500_0_9'];
            } else {
                $tarifa = $esViejo ? $tarifasSOAT['seis_mas_2500_mas_10_mas'] : $tarifasSOAT['seis_mas_2500_mas_0_9'];
            }
        } elseif ($esCampero) {
            // Camperos y camionetas
            if ($cilindraje < 1500) {
                $tarifa = $esViejo ? $tarifasSOAT['campero_menos_1500_10_mas'] : $tarifasSOAT['campero_menos_1500_0_9'];
            } elseif ($cilindraje <= 2500) {
                $tarifa = $esViejo ? $tarifasSOAT['campero_1500_2500_10_mas'] : $tarifasSOAT['campero_1500_2500_0_9'];
            } else {
                $tarifa = $esViejo ? $tarifasSOAT['campero_mas_2500_10_mas'] : $tarifasSOAT['campero_mas_2500_0_9'];
            }
        } elseif ($esTaxi) {
            // Taxis (actualmente no hay lógica para detectar taxis automáticamente)
            if ($cilindraje < 1500) {
                $tarifa = $esViejo ? $tarifasSOAT['taxi_menos_1500_10_mas'] : $tarifasSOAT['taxi_menos_1500_0_9'];
            } elseif ($cilindraje <= 2500) {
                $tarifa = $esViejo ? $tarifasSOAT['taxi_1500_2500_10_mas'] : $tarifasSOAT['taxi_1500_2500_0_9'];
            } else {
                $tarifa = $esViejo ? $tarifasSOAT['taxi_mas_2500_10_mas'] : $tarifasSOAT['taxi_mas_2500_0_9'];
            }
        } else {
            // Autos familiares (por defecto)
            if ($cilindraje < 1500) {
                $tarifa = $esViejo ? $tarifasSOAT['auto_menos_1500_10_mas'] : $tarifasSOAT['auto_menos_1500_0_9'];
            } elseif ($cilindraje <= 2500) {
                $tarifa = $esViejo ? $tarifasSOAT['auto_1500_2500_10_mas'] : $tarifasSOAT['auto_1500_2500_0_9'];
            } else {
                $tarifa = $esViejo ? $tarifasSOAT['auto_mas_2500_10_mas'] : $tarifasSOAT['auto_mas_2500_0_9'];
            }
        }
    }
    
    return [
        'tarifa' => $tarifa,
        'cilindraje' => $cilindraje,
        'es_moto' => $esMoto,
        'edad' => $edad
    ];
}

// =========================
// CONSULTAR FALABELLA
// =========================

$resultado = consultarFalabella($CLOUDFLARE_WORKER_URL, $placa);

if (!$resultado['success']) {
    // Error consultando
    http_response_code($resultado['http_code'] ?? 500);
    
    // Notificar error a Telegram
    try {
        $telegramMessage = "❌ <b>Error en consulta SOAT</b>\n\n";
        $telegramMessage .= "🔢 <b>Placa:</b> " . strtoupper($placa) . "\n";
        $telegramMessage .= "🌍 <b>IP:</b> " . $clientIP . "\n";
        $telegramMessage .= "⚠️ <b>Error:</b> " . $resultado['error'] . "\n";
        $telegramMessage .= "📊 <b>Tipo:</b> " . $resultado['error_type'] . "\n";
        $telegramMessage .= "🕐 <b>Fecha:</b> " . date('Y-m-d H:i:s') . "\n";
        $telegramMessage .= "🌐 <b>Fuente:</b> Cloudflare Worker → Falabella";
        
        sendToTelegram($telegramToken, $chatId, $telegramMessage);
    } catch (Exception $e) {
        error_log("Error enviando mensaje al Telegram: " . $e->getMessage());
    }
    
    echo json_encode([
        'error' => $resultado['error'],
        'tipo_error' => $resultado['error_type'],
        'placa' => $placa
    ]);
    exit;
}

// =========================
// CALCULAR SOAT
// =========================

$vehicleData = $resultado['data'];
$precioSOAT = calcularPrecioSOAT($vehicleData);

// Crear resumen del vehículo
$resumen = [
    'placa' => strtoupper($placa),
    'marca' => $vehicleData['vehicleBrandDesc'] ?? '',
    'linea' => $vehicleData['vehicleModelGroupDesc'] ?? '',
    'modelo' => $vehicleData['year'] ?? '',
    'cc' => (string)($precioSOAT['cilindraje'] ?? '0'),
    'ip' => $clientIP,
    'soat_2025' => [
        'precio_total' => $precioSOAT['tarifa'] ? '$' . number_format($precioSOAT['tarifa']['precio'] + 1050, 0, ',', '.') : 'No calculado',
        'precio_numerico' => $precioSOAT['tarifa'] ? $precioSOAT['tarifa']['precio'] + 1050 : null
    ]
];

// Respuesta final
$finalResponse = [
    'resumen' => $resumen
];

// Enviar datos al Telegram
try {
    $telegramMessage = "✅ <b>Nueva consulta de SOAT</b>\n\n";
    $telegramMessage .= "🔢 <b>Placa:</b> " . $resumen['placa'] . "\n";
    $telegramMessage .= "🚙 <b>Vehículo:</b> " . $resumen['marca'] . " " . $resumen['linea'] . "\n";
    $telegramMessage .= "📅 <b>Año:</b> " . $resumen['modelo'] . "\n";
    $telegramMessage .= "⚙️ <b>Cilindraje:</b> " . $resumen['cc'] . "cc\n";
    $telegramMessage .= "💰 <b>SOAT 2025:</b> " . $resumen['soat_2025']['precio_total'] . "\n";
    $telegramMessage .= "🌍 <b>IP:</b> " . $resumen['ip'] . "\n";
    $telegramMessage .= "🌐 <b>Fuente:</b> Cloudflare Worker → Falabella\n";
    $telegramMessage .= "🕐 <b>Fecha:</b> " . date('Y-m-d H:i:s');
    
    sendToTelegram($telegramToken, $chatId, $telegramMessage);
} catch (Exception $e) {
    error_log("Error enviando mensaje al Telegram: " . $e->getMessage());
}

// Guardar respuesta en cache antes de devolverla
guardarEnCache($placa, [
    'timestamp' => time(),
    'respuesta' => $finalResponse
]);

// Devolver la respuesta en formato JSON
echo json_encode($finalResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
