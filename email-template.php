<?php
function empatia_email_html($title, $content) {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<!doctype html><html lang="es"><body style="margin:0;padding:24px;background:#f5f3ee;color:#252525;font-family:Arial,sans-serif">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;margin:0 auto;background:#fffdfa;border-collapse:collapse">'
        . '<tr><td style="padding:22px 28px;background:#403c78;color:#fff;font-family:Georgia,serif;font-size:22px">El Proyecto Empatía</td></tr>'
        . '<tr><td style="height:3px;background:#c9a965;font-size:0;line-height:0">&nbsp;</td></tr>'
        . '<tr><td style="padding:30px 28px 10px;font-size:20px;line-height:1.35">' . $safeTitle . '</td></tr>'
        . '<tr><td style="padding:8px 28px 28px;font-size:15px;line-height:1.65">' . $content . '</td></tr>'
        . '<tr><td style="padding:18px 28px 24px;border-top:1px solid #d5d0c7;color:#5f5a54;font-size:13px;line-height:1.6">'
        . '<strong style="color:#403c78;font-family:Georgia,serif;font-size:16px">El Proyecto Empatía</strong><br>'
        . 'Una novela de Carlos Fernández Gates<br>Palestra Editores<br>'
        . '<a href="mailto:empatia@elproyectoempatia.com" style="color:#403c78;text-decoration:none">empatia@elproyectoempatia.com</a>'
        . '</td></tr></table></body></html>';
}

function empatia_send_mail($recipient, $subject, $plainText, $contentHtml, $replyTo = 'empatia@elproyectoempatia.com') {
    $boundary = '=_Empatia_' . md5(uniqid((string)mt_rand(), true));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = "From: El Proyecto Empatía <empatia@elproyectoempatia.com>\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $message = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($plainText));
    $message .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode(empatia_email_html($subject, $contentHtml)));
    $message .= "--{$boundary}--\r\n";
    return @mail($recipient, $encodedSubject, $message, $headers);
}
