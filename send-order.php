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
$profession = trim(strip_tags((string)($_POST['profesion'] ?? '')));
$edition = (string)($_POST['edicion'] ?? '');
$dedication = trim(strip_tags((string)($_POST['dedicatoria'] ?? '')));
$consent = (string)($_POST['consentimiento'] ?? '');
$name = preg_replace('/[\r\n]+/', ' ', $name);
$phone = preg_replace('/[\r\n]+/', ' ', $phone);
$profession = preg_replace('/[\r\n]+/', ' ', $profession);

if ($name === '' || strlen($name) > 480 || $profession === '' || strlen($profession) > 480 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[+0-9()\s.-]{7,25}$/', $phone) || !in_array($edition, ['regular', 'firmada'], true) || $consent !== 'si' || strlen($dedication) > 1600) {
    respond(422, ['ok' => false, 'message' => 'Revisa los datos del pedido y confirma la autorización de uso de datos.']);
}

$price = $edition === 'firmada' ? 64 : 59;
$delivery = 11;
$editionName = $edition === 'firmada' ? 'Edición especial' : 'Edición estándar';
if ($edition !== 'firmada') $dedication = '';
$safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safePhone = htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeProfession = htmlspecialchars($profession, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEdition = htmlspecialchars($editionName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeDedication = nl2br(htmlspecialchars($dedication !== '' ? $dedication : 'No solicitada', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
date_default_timezone_set('America/Lima');
$date = date('d/m/Y H:i:s') . ' (hora de Perú)';
$subject = 'Nueva solicitud de pedido, El Proyecto Empatía';
$body = "Nueva solicitud de pedido\nNombre: {$name}\nCorreo: {$email}\nTeléfono: {$phone}\nProfesión: {$profession}\nEdición: {$editionName}\nPrecio del libro: S/{$price}\nDelivery: S/{$delivery}\nDedicatoria: " . ($dedication !== '' ? $dedication : 'No solicitada') . "\nFecha: {$date}";
$html = '<p>Se recibió una nueva solicitud de pedido.</p><p><strong>Nombre:</strong> ' . $safeName . '<br><strong>Correo:</strong> ' . $safeEmail . '<br><strong>Teléfono:</strong> ' . $safePhone . '<br><strong>Profesión:</strong> ' . $safeProfession . '<br><strong>Edición:</strong> ' . $safeEdition . '<br><strong>Precio del libro:</strong> S/' . $price . '<br><strong>Delivery:</strong> S/' . $delivery . '<br><strong>Dedicatoria:</strong><br>' . $safeDedication . '<br><strong>Fecha:</strong> ' . htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
if (!empatia_send_mail('empatia@elproyectoempatia.com', $subject, $body, $html, $email)) {
    respond(502, ['ok' => false, 'message' => 'No pudimos enviar el pedido. Inténtalo nuevamente más tarde.']);
}

$editionLabel = $edition === 'firmada' ? 'Edición Especial' : 'Edición Estándar';
$total = $price + $delivery;
$confirmationSubject = 'Tu pedido ha sido registrado ✦ El Proyecto Empatía';
$whatsappMessageText = 'Hola, quiero comprar ' . $editionLabel . ' de El Proyecto Empatía (S/' . $price . '). Delivery: S/' . $delivery . ' soles adicionales.';
if ($edition === 'firmada') $whatsappMessageText .= ' Quisiera consultar la opción de dedicatoria.';
$whatsappMessage = rawurlencode($whatsappMessageText);
$whatsappPlainUrl = 'https://wa.me/51922198480?text=' . $whatsappMessage;
$confirmationText = "Hola {$name}:\n\nGracias por sumarte a El Proyecto Empatía. Te confirmamos que tu pedido ha quedado registrado.\n\nResumen de tu pedido:\n- {$editionLabel}: S/{$price}\n- Delivery: S/{$delivery} soles adicionales\n- Total a coordinar: S/{$total}\n\n¿Qué sigue ahora? Te escribiremos a la brevedad para coordinar el pago y la entrega. Si necesitas hacer algún ajuste, responde a este correo o escríbenos directamente por WhatsApp:\n{$whatsappPlainUrl}\n";
$safeEditionLabel = htmlspecialchars($editionLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$whatsappUrl = htmlspecialchars($whatsappPlainUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$confirmationHtml = '<p style="margin:0 0 18px;color:#252525">Hola ' . $safeName . ':</p>'
    . '<p style="margin:0 0 22px;color:#4b4843">Gracias por sumarte a <i>El Proyecto Empatía</i>. Te confirmamos que tu pedido ha quedado registrado.</p>'
    . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f6f3ee;border:1px solid #ded8ce;border-collapse:collapse">'
    . '<tr><td colspan="2" style="padding:16px 18px 8px;color:#77736c;font-family:DM Sans,Arial,sans-serif;font-size:10px;letter-spacing:1.5px">RESUMEN DE TU PEDIDO</td></tr>'
    . '<tr><td style="padding:6px 18px 12px;color:#252525;font-family:&quot;PP Editorial New&quot;,Georgia,serif;font-size:18px">' . $safeEditionLabel . '</td><td align="right" style="padding:6px 18px 12px;color:#403c78;font-family:Arial,sans-serif;font-size:17px;font-weight:bold">S/' . $price . '</td></tr>'
    . '<tr><td style="padding:0 18px 10px;color:#5f5a54;font-family:Arial,sans-serif;font-size:13px">Delivery</td><td align="right" style="padding:0 18px 10px;color:#5f5a54;font-family:Arial,sans-serif;font-size:13px">S/' . $delivery . ' soles adicionales</td></tr>'
    . '<tr><td colspan="2" style="padding:0 18px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td style="height:1px;background:#ded8ce;font-size:0;line-height:0">&nbsp;</td></tr></table></td></tr>'
    . '<tr><td style="padding:12px 18px 16px;color:#252525;font-family:Arial,sans-serif;font-size:13px;font-weight:bold">Total a coordinar</td><td align="right" style="padding:12px 18px 16px;color:#403c78;font-family:Arial,sans-serif;font-size:15px;font-weight:bold">S/' . $total . '</td></tr>'
    . '</table>'
    . '<p style="margin:22px 0 5px;color:#252525;font-family:Georgia,serif;font-size:19px">¿Qué sigue ahora?</p>'
    . '<p style="margin:0 0 20px;color:#5f5a54">Te escribiremos a la brevedad para coordinar el pago y la entrega. Si necesitas hacer algún ajuste, responde a este correo o escríbenos directamente por WhatsApp.</p>'
    . '<p style="margin:0"><a href="' . $whatsappUrl . '" style="display:inline-block;padding:13px 22px;border-radius:24px;background:#403c78;color:#ffffff;font-family:Arial,sans-serif;font-size:13px;text-decoration:none">Escribir por WhatsApp &nbsp; →</a></p>'
empatia_send_mail($email, $confirmationSubject, $confirmationText, $confirmationHtml, 'empatia@elproyectoempatia.com', 'Gracias por elegir una historia que se siente.');
respond(200, ['ok' => true, 'message' => 'Recibimos tu pedido. Te enviamos una confirmación por correo.']);
