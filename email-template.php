<?php
function empatia_email_html($title, $content) {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<!doctype html><html lang="es"><body style="margin:0;padding:0;background:#f4f1eb;color:#252525;font-family:Arial,sans-serif">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f4f1eb"><tr><td align="center" style="padding:28px 12px">'
        . '<table role="presentation" width="620" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:620px;background:#fffdfa;border:1px solid #d7d0c5;border-collapse:collapse">'
        . '<tr><td style="height:6px;background:#403c78;font-size:0;line-height:0">&nbsp;</td></tr>'
        . '<tr><td style="padding:28px 32px 8px;color:#403c78;font-family:Arial,sans-serif;font-size:11px;letter-spacing:2px">EL PROYECTO EMPATÍA</td></tr>'
        . '<tr><td style="padding:6px 32px 24px;color:#252525;font-family:Georgia,serif;font-size:28px;line-height:1.25">' . $safeTitle . '</td></tr>'
        . '<tr><td style="padding:0 32px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td style="height:1px;background:#d5d0c7;font-size:0;line-height:0">&nbsp;</td><td width="28" align="center" style="color:#c9a965;font-family:Georgia,serif;font-size:14px">✦</td><td style="height:1px;background:#d5d0c7;font-size:0;line-height:0">&nbsp;</td></tr></table></td></tr>'
        . '<tr><td style="padding:26px 32px 30px;color:#4b4843;font-family:Arial,sans-serif;font-size:15px;line-height:1.65">' . $content . '</td></tr>'
        . '<tr><td style="padding:0 32px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td style="height:1px;background:#d5d0c7;font-size:0;line-height:0">&nbsp;</td></tr></table></td></tr>'
        . '<tr><td style="padding:20px 32px 26px;color:#5f5a54;font-family:Arial,sans-serif;font-size:12px;line-height:1.7">'
        . '<strong style="color:#403c78;font-family:Georgia,serif;font-size:15px">Carlos Fernández Gates</strong><br>'
        . 'Akuela Innovations<br>'
        . '<a href="mailto:empatia@elproyectoempatia.com" style="color:#403c78;text-decoration:none">empatia@elproyectoempatia.com</a>'
        . '</td></tr></table></td></tr></table></body></html>';
}
function empatia_send_mail($recipient, $subject, $plainText, $contentHtml, $replyTo = 'empatia@elproyectoempatia.com', $displayTitle = null) {
    $boundary = '=_Empatia_' . md5(uniqid((string)mt_rand(), true));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = "From: El Proyecto Empatía <empatia@elproyectoempatia.com>\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $message = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($plainText));
    $message .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode(empatia_email_html($displayTitle ?: $subject, $contentHtml)));
    $message .= "--{$boundary}--\r\n";
    return @mail($recipient, $encodedSubject, $message, $headers);
}
