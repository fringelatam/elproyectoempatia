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
$phone = trim((string)($_POST['telefono'] ?? ''));
$edition = (string)($_POST['edicion'] ?? '');
$dedication = trim(strip_tags((string)($_POST['dedicatoria'] ?? '')));
$consent = (string)($_POST['consentimiento'] ?? '');
$name = preg_replace('/[\r\n]+/', ' ', $name);
$phone = preg_replace('/[\r\n]+/', ' ', $phone);

if ($name === '' || strlen($name) > 480 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[+0-9()\s.-]{7,25}$/', $phone) || !in_array($edition, ['regular', 'firmada'], true) || $consent !== 'si' || strlen($dedication) > 1600) {
    respond(422, ['ok' => false, 'message' => 'Revisa los datos del pedido y confirma la autorización de uso de datos.']);
}

$price = $edition === 'firmada' ? 64 : 59;
$editionName = $edition === 'firmada' ? 'Edición firmada, 64 soles' : 'Edición regular, 59 soles';
if ($edition !== 'firmada') $dedication = '';
$safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safePhone = htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEdition = htmlspecialchars($editionName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeDedication = nl2br(htmlspecialchars($dedication !== '' ? $dedication : 'No solicitada', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
$date = date('Y-m-d H:i:s T');
$subject = 'Nueva solicitud de pedido, El Proyecto Empatía';
$body = "Nueva solicitud de pedido\nNombre: {$name}\nCorreo: {$email}\nTeléfono: {$phone}\nEdición: {$editionName}\nPrecio: {$price} soles\nDedicatoria: " . ($dedication !== '' ? $dedication : 'No solicitada') . "\nFecha: {$date}";
$html = '<p>Se recibió una nueva solicitud de pedido.</p><p><strong>Nombre:</strong> ' . $safeName . '<br><strong>Correo:</strong> ' . $safeEmail . '<br><strong>Teléfono:</strong> ' . $safePhone . '<br><strong>Edición:</strong> ' . $safeEdition . '<br><strong>Precio:</strong> ' . $price . ' soles<br><strong>Dedicatoria:</strong><br>' . $safeDedication . '<br><strong>Fecha:</strong> ' . htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
if (!empatia_send_mail('empatia@elproyectoempatia.com', $subject, $body, $html, $email)) {
    respond(502, ['ok' => false, 'message' => 'No pudimos enviar el pedido. Inténtalo nuevamente más tarde.']);
}

$confirmationText = "Hola {$name}:\n\nRecibimos tu solicitud de {$editionName}. El precio es {$price} soles. Te contactaremos para coordinar el pago y la entrega.\n";
$confirmationHtml = '<p>Hola ' . $safeName . ':</p><p>Recibimos tu solicitud de <strong>' . $safeEdition . '</strong>, con un precio de <strong>' . $price . ' soles</strong>. Te contactaremos para coordinar el pago y la entrega.</p>';
empatia_send_mail($email, 'Recibimos tu pedido de El Proyecto Empatía', $confirmationText, $confirmationHtml);
respond(200, ['ok' => true, 'message' => 'Recibimos tu pedido. Te enviamos una confirmación por correo.']);
