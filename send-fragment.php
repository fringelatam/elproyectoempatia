<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/email-template.php';

function respond($status, $payload) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Método no permitido.']);
}
if (!empty($_POST['company'])) {
    respond(200, ['ok' => true, 'message' => 'Recibimos tu solicitud.']);
}

$name = trim(strip_tags((string)($_POST['nombre'] ?? '')));
$email = trim((string)($_POST['email'] ?? ''));
$name = preg_replace('/[\r\n]+/', ' ', $name);
if ($name === '' || strlen($name) > 480 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'message' => 'Revisa tu nombre y correo electrónico.']);
}

$safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$body = "Solicitud del primer capítulo\nNombre: {$name}\nCorreo: {$email}\nFecha: " . date('Y-m-d H:i:s T');
$html = '<p>Se recibió una solicitud del primer capítulo.</p><p><strong>Nombre:</strong> ' . $safeName . '<br><strong>Correo:</strong> ' . $safeEmail . '<br><strong>Fecha:</strong> ' . date('Y-m-d H:i:s T') . '</p>';
if (!empatia_send_mail('empatia@elproyectoempatia.com', 'Solicitud del primer capítulo', $body, $html, $email)) {
    respond(502, ['ok' => false, 'message' => 'No pudimos enviar tu solicitud. Inténtalo nuevamente más tarde.']);
}

$thanksText = "Hola {$name}:\n\nRecibimos tu solicitud del primer capítulo de El Proyecto Empatía. Te responderemos desde empatia@elproyectoempatia.com.\n";
$thanksHtml = '<p>Hola ' . $safeName . ':</p><p>Recibimos tu solicitud del primer capítulo de <i>El Proyecto Empatía</i>. Te responderemos desde <a href="mailto:empatia@elproyectoempatia.com" style="color:#403c78">empatia@elproyectoempatia.com</a>.</p>';
empatia_send_mail($email, 'Recibimos tu solicitud de lectura', $thanksText, $thanksHtml);
respond(200, ['ok' => true, 'message' => 'Recibimos tu solicitud. Te enviamos una confirmación por correo.']);
