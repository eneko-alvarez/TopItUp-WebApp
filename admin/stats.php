<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$apiKey = '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';

require_once "config.php"; // Configura $host, $dbname, $username, $password


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die('Error conexión DB: ' . $e->getMessage());
}


// Traducción manual de meses para evitar deprecated strftime
$meses = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];
$dt = new DateTime('first day of last month');
$nombre_mes = $meses[(int)$dt->format('n')];


$primer_dia_mes_pasado = date('Y-m-01', strtotime('first day of last month'));
$ultimo_dia_mes_pasado = date('Y-m-t', strtotime('last month'));


// Obtener todos los usuarios con email y nombre
$usuariosQuery = $pdo->query("SELECT id, email, username FROM users");
$usuarios = $usuariosQuery->fetchAll(PDO::FETCH_ASSOC);


// Función para enviar email con Brevo (ya definida en pasos anteriores)
function enviarEmailBrevo($email, $htmlContent, $subject) {
    global $apiKey;


    $url = "https://api.brevo.com/v3/smtp/email";


    $data = [
        "sender" => ["name" => "TopItUp Stats", "email" => "stats@enekoalvarez.com"],
        "to" => [["email" => $email]],
        "subject" => $subject,
        "htmlContent" => $htmlContent
    ];


    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "accept: application/json",
        "content-type: application/json",
        "api-key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);


    if ($error) {
        echo "Error CURL: $error\n";
        return false;
    }
    return json_decode($response, true);
}


foreach ($usuarios as $user) {
    $usuario_id = $user['id'];
    $email = $user['email'];
    $nombre_usuario = htmlspecialchars($user['username']);


    // Consulta counters del usuario actual
    $query = "SELECT c.id, c.name FROM counters c WHERE c.user_id = :uid";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['uid' => $usuario_id]);
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $html = "<p>Hola <b>$nombre_usuario</b>,</p><p>Estas son tus <b>estadísticas de $nombre_mes</b> por contador:</p>";


    foreach ($counters as $counter) {
        $counter_id = $counter['id'];
        $counter_name = htmlspecialchars($counter['name']);


        $queryLogs = "
            SELECT date
            FROM counter_logs
            WHERE counter_id = :cid
                AND date >= :from
                AND date <= :to
            ORDER BY date
        ";
        $stmtLogs = $pdo->prepare($queryLogs);
        $stmtLogs->execute([
            'cid' => $counter_id,
            'from' => $primer_dia_mes_pasado,
            'to' => $ultimo_dia_mes_pasado
        ]);
        $logs = $stmtLogs->fetchAll(PDO::FETCH_COLUMN);


        $dias = [];
        foreach ($logs as $d) $dias[$d] = ($dias[$d]??0)+1;


        $dias_con_counts = array_keys($dias);
        $dias_del_mes = [];
        $start = strtotime($primer_dia_mes_pasado);
        $end = strtotime($ultimo_dia_mes_pasado);
        for ($i = $start; $i <= $end; $i += 86400) {
            $dias_del_mes[] = date('Y-m-d', $i);
        }


        // Estadísticas
        $racha_con = 0; $max_racha_con = 0;
        foreach ($dias_del_mes as $d) {
            $racha_con = isset($dias[$d]) ? $racha_con + 1 : 0;
            $max_racha_con = max($max_racha_con, $racha_con);
        }


        $racha_sin = 0; $max_racha_sin = 0;
        foreach ($dias_del_mes as $d) {
            $racha_sin = !isset($dias[$d]) ? $racha_sin + 1 : 0;
            $max_racha_sin = max($max_racha_sin, $racha_sin);
        }


        $mayor_dia_counts = $dias ? max($dias) : 0;
        $dia_max_counts = $mayor_dia_counts ? array_search($mayor_dia_counts, $dias) : '-';


        $dia_ultimo = $dias_con_counts ? max($dias_con_counts) : '-';
        $dia_primero = $dias_con_counts ? min($dias_con_counts) : '-';


        $total_dias_activos = count($dias_con_counts);
        $total_suma_mes = array_sum($dias);
        $dias_totales_mes = count($dias_del_mes);
        $promedio_diario_real = $dias_totales_mes ? number_format($total_suma_mes / $dias_totales_mes, 2) : '0';


        $estadisticas = [
            "¡Racha! Estuviste <b>$max_racha_con</b> días seguidos sumando a <b>$counter_name</b>.",
            "Mayor periodo sin sumar: <b>$max_racha_sin</b> días sin registrar en <b>$counter_name</b>.",
            "El día que más registraste <b>$counter_name</b> fue <b>$dia_max_counts</b> con <b>$mayor_dia_counts</b> veces.",
            "Última vez que registraste <b>$counter_name</b>: <b>$dia_ultimo</b>.",
            "Primer día del mes que registraste <b>$counter_name</b>: <b>$dia_primero</b>.",
            "Días activos este mes con <b>$counter_name</b>: <b>$total_dias_activos</b>.",
            "Promedio diario de registros de <b>$counter_name</b>: <b>$promedio_diario_real</b>.",
        ];


        $eligida = $estadisticas[array_rand($estadisticas)];
        $html .= "<h3>$counter_name</h3><p>$eligida</p>";
    }


    $html .= "<p>El siguiente mes se parte más, espero (es amenaza, si). Gracias :)</p>";


    $subject = "Tus estadísticas de $nombre_mes - TopItUp";


    // Por ahora imprimimos para revisar
    echo "<hr><p>Correo para $email procesado</p>";


    // Imprimir resultado envío para debug
    $resultadoEnvio = enviarEmailBrevo($email, $html, $subject);

    if (!$resultadoEnvio) {
        echo "<p style='color:red;'>Error: falla conexión o CURL.</p>";
    } else if (isset($resultadoEnvio['messageId'])) {
        echo "<p style='color:green;'>Email enviado correctamente. ID: " . htmlspecialchars($resultadoEnvio['messageId']) . "</p>";
    } else if (isset($resultadoEnvio['code']) && isset($resultadoEnvio['message'])) {
        echo "<p style='color:red;'>Error API: Código " . htmlspecialchars($resultadoEnvio['code']) . " - " . htmlspecialchars($resultadoEnvio['message']) . "</p>";
    } else {
        echo "<p style='color:orange;'>Respuesta inesperada: <pre>" . print_r($resultadoEnvio, true) . "</pre></p>";
    }
}
?>
