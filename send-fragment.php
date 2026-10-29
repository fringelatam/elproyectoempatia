<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function respond($status, $payload) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Método no permitido.']);
}

// Honeypot for basic bot filtering. Legitimate visitors leave this field empty.
if (!empty($_POST['company'])) {
    respond(200, ['ok' => true, 'message' => 'Recibimos tu solicitud.']);
}

$name = trim(strip_tags((string)($_POST['nombre'] ?? '')));
$email = trim((string)($_POST['email'] ?? ''));
$name = preg_replace('/[\\r\\n]+/', ' ', $name);

if ($name === '' || strlen($name) > 480 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'message' => 'Revisa tu nombre y correo electrónico.']);
}

$to = 'empatia@elproyectoempatia.com';
$from = 'empatia@elproyectoempatia.com';
$subject = 'Solicitud del primer capítulo de El Proyecto Empatía';
$body = "Nueva solicitud desde elproyectoempatia.com\n\nNombre: {$name}\nCorreo: {$email}\nFecha: " . date('Y-m-d H:i:s T') . "\n";
$headers = "From: El Proyecto Empatía <{$from}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (!@mail($to, $subject, $body, $headers)) {
    respond(502, ['ok' => false, 'message' => 'No pudimos enviar tu solicitud. Inténtalo nuevamente más tarde.']);
}

respond(200, ['ok' => true, 'message' => 'Recibimos tu solicitud. Te responderemos desde empatia@elproyectoempatia.com.']);
