/**
 * Backend de formularios de El Proyecto Empatía.
 * Vinculado a la hoja "El Proyecto Empatía — Solicitudes".
 * Ejecutar setup() una vez y luego publicar como aplicación web.
 */
const CONFIG = {
  ADMIN_EMAIL: "empatia@elproyectoempatia.com",
  TIME_ZONE: "America/Lima",
  SITE_URL: "https://elproyectoempatia.com/",
  WHATSAPP_URL: "https://wa.me/51922198480",
  SHEET_ORDERS: "Pedidos",
  SHEET_SAMPLE: "Solicitudes de lectura",
  PRICE_REGULAR: 59,
  PRICE_SPECIAL: 64,
  DELIVERY: 11
};

const ORDER_HEADERS = [
  "ID de solicitud", "Fecha (hora de Perú)", "Nombre", "Correo",
  "Teléfono", "Profesión", "Edición", "Precio del libro (S/)",
  "Delivery (S/)", "Dedicatoria", "Consentimiento",
  "Estado del pedido", "Estado del correo"
];
const SAMPLE_HEADERS = [
  "ID de solicitud", "Fecha (hora de Perú)", "Nombre",
  "Correo", "Consentimiento", "Estado"
];

/** Ejecutar manualmente una vez desde el editor de Apps Script. */
function setup() {
  const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  if (!spreadsheet) throw new Error("Abre este script desde la hoja de solicitudes.");
  spreadsheet.setSpreadsheetTimeZone(CONFIG.TIME_ZONE);
  ensureSheet_(spreadsheet, CONFIG.SHEET_ORDERS, ORDER_HEADERS);
  ensureSheet_(spreadsheet, CONFIG.SHEET_SAMPLE, SAMPLE_HEADERS);
  PropertiesService.getScriptProperties().setProperty("SPREADSHEET_ID", spreadsheet.getId());
  return "Configuración lista: " + spreadsheet.getName();
}

function doGet() {
  return page_(
    "Formulario de El Proyecto Empatía",
    "El servicio está activo. Envía tu pedido o solicita el primer capítulo desde el sitio.",
    true,
    "Puedes volver al sitio cuando quieras."
  );
}

function doPost(e) {
  try {
    const p = e && e.parameter ? e.parameter : {};
    const kind = p.form_type;
    console.info("form_received " + JSON.stringify({
      formType: kind || "missing",
      fieldsPresent: {
        name: Boolean(p.nombre),
        email: Boolean(p.email),
        phone: Boolean(p.telefono),
        profession: Boolean(p.profesion),
        edition: Boolean(p.edicion),
        consent: Boolean(p.consentimiento)
      }
    }));
    if (kind === "order") return handleOrder_(p);
    if (kind === "sample") return handleSample_(p);
    return page_("No pudimos procesar la solicitud", "Vuelve al sitio e inténtalo nuevamente.", false, "");
  } catch (error) {
    console.error(error && error.stack ? error.stack : error);
    return page_(
      "No pudimos registrar la solicitud",
      "No se guardó la información. Vuelve al sitio e inténtalo nuevamente o escríbenos a empatia@elproyectoempatia.com.",
      false,
      ""
    );
  }
}

function handleOrder_(p) {
  const name = clean_(p.nombre, 120);
  const email = clean_(p.email, 254).toLowerCase();
  const phone = clean_(p.telefono, 25);
  const profession = clean_(p.profesion, 120);
  const edition = clean_(p.edicion, 20);
  const dedication = clean_(p.dedicatoria, 400);
  const consent = p.consentimiento === "si";
  const validation = {
    name: Boolean(name),
    profession: Boolean(profession),
    email: validEmail_(email),
    phone: /^[+0-9()\s.-]{7,25}$/.test(phone),
    edition: ["regular", "firmada"].includes(edition),
    consent: consent
  };
  console.info("order_validation " + JSON.stringify(validation));
  if (Object.values(validation).some((passed) => !passed)) {
    return page_(
      "Revisa tus datos",
      "Completa todos los campos obligatorios y autoriza el uso de tus datos para atender el pedido.",
      false,
      ""
    );
  }

  const special = edition === "firmada";
  const price = special ? CONFIG.PRICE_SPECIAL : CONFIG.PRICE_REGULAR;
  const editionName = special ? "Edición especial" : "Edición estándar";
  const dedicationValue = special && dedication ? dedication : "No solicitada";
  const now = new Date();
  const dateText = Utilities.formatDate(now, CONFIG.TIME_ZONE, "dd/MM/yyyy HH:mm:ss") + " (hora de Perú)";
  const id = "PED-" + Utilities.formatDate(now, CONFIG.TIME_ZONE, "yyyyMMdd-HHmmss") + "-" + Utilities.getUuid().slice(0, 8).toUpperCase();

  const sheet = getSheet_(CONFIG.SHEET_ORDERS);
  sheet.appendRow([
    id, now, name, email, phone, profession, editionName, price,
    CONFIG.DELIVERY, dedicationValue, "Sí", "Nuevo", "Pendiente"
  ]);
  console.info("order_saved " + id);
  const row = sheet.getLastRow();
  sheet.getRange(row, 2).setNumberFormat("dd/mm/yyyy hh:mm:ss");

  let adminSent = false;
  let customerSent = false;
  try {
    MailApp.sendEmail({
      to: CONFIG.ADMIN_EMAIL,
      subject: "Nueva solicitud de pedido, El Proyecto Empatía",
      body: orderAdminText_(name, email, phone, profession, editionName, price, dedicationValue, dateText),
      htmlBody: orderAdminHtml_(name, email, phone, profession, editionName, price, dedicationValue, dateText),
      replyTo: email,
      name: "El Proyecto Empatía"
    });
    adminSent = true;
  } catch (error) {
    console.error("No se pudo enviar el aviso al equipo: " + error);
  }
  try {
    MailApp.sendEmail({
      to: email,
      subject: "Tu pedido ha sido registrado ✦ El Proyecto Empatía",
      body: orderCustomerText_(name, editionName, price, special),
      htmlBody: orderCustomerHtml_(name, editionName, price, special),
      replyTo: CONFIG.ADMIN_EMAIL,
      name: "El Proyecto Empatía"
    });
    customerSent = true;
  } catch (error) {
    console.error("No se pudo enviar la confirmación al cliente: " + error);
  }

  const emailStatus = "Equipo: " + (adminSent ? "enviado" : "pendiente") +
    " · Cliente: " + (customerSent ? "enviado" : "pendiente");
  console.info("order_email_status " + JSON.stringify({adminSent, customerSent}));
  sheet.getRange(row, 13).setValue(emailStatus);

  if (!adminSent) {
    return page_(
      "Pedido guardado",
      "Tu solicitud quedó registrada correctamente. Sin embargo, no se pudo enviar el aviso por correo al equipo. Si no recibes respuesta, escríbenos a empatia@elproyectoempatia.com.",
      true,
      "Código de pedido: " + id
    );
  }
  return page_(
    "Tu pedido ha sido registrado",
    "Gracias por sumarte a El Proyecto Empatía. Te escribiremos para coordinar el pago y la entrega.",
    true,
    "Código de pedido: " + id
  );
}

function handleSample_(p) {
  const name = clean_(p.nombre, 120);
  const email = clean_(p.email, 254).toLowerCase();
  const consent = p.consentimiento === "si";
  const validation = {
    name: Boolean(name),
    email: validEmail_(email),
    consent: consent
  };
  console.info("sample_validation " + JSON.stringify(validation));
  if (Object.values(validation).some((passed) => !passed)) {
    return page_(
      "Revisa tus datos",
      "Completa tu nombre, un correo válido y autoriza el uso de tus datos para atender esta solicitud.",
      false,
      ""
    );
  }

  const now = new Date();
  const dateText = Utilities.formatDate(now, CONFIG.TIME_ZONE, "dd/MM/yyyy HH:mm:ss") + " (hora de Perú)";
  const id = "LEC-" + Utilities.formatDate(now, CONFIG.TIME_ZONE, "yyyyMMdd-HHmmss") + "-" + Utilities.getUuid().slice(0, 8).toUpperCase();
  const sheet = getSheet_(CONFIG.SHEET_SAMPLE);
  sheet.appendRow([id, now, name, email, "Sí", "Nuevo"]);
  console.info("sample_saved " + id);
  const row = sheet.getLastRow();
  sheet.getRange(row, 2).setNumberFormat("dd/mm/yyyy hh:mm:ss");

  let adminSent = false;
  let customerSent = false;
  try {
    MailApp.sendEmail({
      to: CONFIG.ADMIN_EMAIL,
      subject: "Nueva solicitud del primer capítulo, El Proyecto Empatía",
      body: "Se recibió una nueva solicitud del primer capítulo.\n\nNombre: " + name +
        "\nCorreo: " + email + "\nFecha: " + dateText + "\nID: " + id,
      htmlBody: "<p>Se recibió una nueva solicitud del primer capítulo.</p><p><strong>Nombre:</strong> " +
        esc_(name) + "<br><strong>Correo:</strong> " + esc_(email) +
        "<br><strong>Fecha:</strong> " + esc_(dateText) + "<br><strong>ID:</strong> " + esc_(id) + "</p>",
      replyTo: email,
      name: "El Proyecto Empatía"
    });
    adminSent = true;
  } catch (error) {
    console.error("No se pudo enviar el aviso de lectura: " + error);
  }
  try {
    MailApp.sendEmail({
      to: email,
      subject: "Recibimos tu solicitud de lectura ✦ El Proyecto Empatía",
      body: "Hola " + name + ":\n\nRecibimos tu solicitud del primer capítulo de El Proyecto Empatía. Te responderemos desde empatia@elproyectoempatia.com.\n\nEl Proyecto Empatía\nAkuela Innovations",
      htmlBody: "<p>Hola " + esc_(name) + ":</p><p>Recibimos tu solicitud del primer capítulo de <i>El Proyecto Empatía</i>. Te responderemos desde <a href=\"mailto:empatia@elproyectoempatia.com\" style=\"color:#403c78\">empatia@elproyectoempatia.com</a>.</p><p style=\"margin-top:28px;color:#403c78;font-weight:600\">El Proyecto Empatía</p><p style=\"margin:0;color:#77736c\">Akuela Innovations</p>",
      replyTo: CONFIG.ADMIN_EMAIL,
      name: "El Proyecto Empatía"
    });
    customerSent = true;
  } catch (error) {
    console.error("No se pudo enviar la confirmación de lectura: " + error);
  }

  const status = "Equipo: " + (adminSent ? "enviado" : "pendiente") +
    " · Cliente: " + (customerSent ? "enviado" : "pendiente");
  console.info("sample_email_status " + JSON.stringify({adminSent, customerSent}));
  sheet.getRange(row, 6).setValue(status);
  return page_(
    adminSent ? "Solicitud recibida" : "Solicitud guardada",
    adminSent
      ? "Recibimos tu solicitud del primer capítulo. Te responderemos desde empatia@elproyectoempatia.com."
      : "Tu solicitud quedó registrada, pero el aviso por correo al equipo está pendiente. Si no recibes respuesta, escríbenos a empatia@elproyectoempatia.com.",
    true,
    "Código de solicitud: " + id
  );
}

function orderAdminText_(name, email, phone, profession, edition, price, dedication, dateText) {
  return "Se recibió una nueva solicitud de pedido.\n\n" +
    "Nombre: " + name + "\nCorreo: " + email + "\nTeléfono: " + phone +
    "\nProfesión: " + profession + "\nEdición: " + edition +
    "\nPrecio del libro: S/" + price + "\nDelivery: S/" + CONFIG.DELIVERY +
    "\nDedicatoria:\n" + dedication + "\nFecha: " + dateText;
}

function orderAdminHtml_(name, email, phone, profession, edition, price, dedication, dateText) {
  return "<div style=\"font-family:Arial,sans-serif;color:#252525;line-height:1.6\">" +
    "<p>Se recibió una nueva solicitud de pedido.</p><p>" +
    "<strong>Nombre:</strong> " + esc_(name) + "<br>" +
    "<strong>Correo:</strong> " + esc_(email) + "<br>" +
    "<strong>Teléfono:</strong> " + esc_(phone) + "<br>" +
    "<strong>Profesión:</strong> " + esc_(profession) + "<br>" +
    "<strong>Edición:</strong> " + esc_(edition) + "<br>" +
    "<strong>Precio del libro:</strong> S/" + price + "<br>" +
    "<strong>Delivery:</strong> S/" + CONFIG.DELIVERY + "<br>" +
    "<strong>Dedicatoria:</strong><br>" + esc_(dedication).replace(/\n/g, "<br>") + "<br>" +
    "<strong>Fecha:</strong> " + esc_(dateText) + "</p></div>";
}

function orderCustomerText_(name, edition, price, special) {
  const total = price + CONFIG.DELIVERY;
  const message = "Hola, quiero comprar " + edition + " de El Proyecto Empatía (S/" + price +
    "). Delivery: S/" + CONFIG.DELIVERY + " soles adicionales." +
    (special ? " Quisiera consultar la opción de dedicatoria." : "");
  return "Hola " + name + ":\n\nGracias por sumarte a El Proyecto Empatía. Tu pedido ha quedado registrado.\n\n" +
    "Resumen de tu pedido:\n- " + edition + ": S/" + price + "\n- Delivery: S/" + CONFIG.DELIVERY +
    " soles adicionales\n- Total a coordinar: S/" + total + "\n\n" +
    "Te escribiremos a la brevedad para coordinar el pago y la entrega. Si necesitas hacer algún ajuste, responde a este correo o escríbenos por WhatsApp:\n" +
    CONFIG.WHATSAPP_URL + "?text=" + encodeURIComponent(message) + "\n";
}

function orderCustomerHtml_(name, edition, price, special) {
  const total = price + CONFIG.DELIVERY;
  const message = "Hola, quiero comprar " + edition + " de El Proyecto Empatía (S/" + price +
    "). Delivery: S/" + CONFIG.DELIVERY + " soles adicionales." +
    (special ? " Quisiera consultar la opción de dedicatoria." : "");
  const whatsapp = CONFIG.WHATSAPP_URL + "?text=" + encodeURIComponent(message);
  return "<div style=\"margin:0 auto;max-width:600px;background:#fffdfa;color:#252525;font-family:Arial,sans-serif;line-height:1.6\">" +
    "<div style=\"padding:20px 24px;background:#2d2a59;color:#fff;font-family:Georgia,serif;font-size:22px\">El Proyecto Empatía</div>" +
    "<div style=\"padding:28px 24px\"><h1 style=\"margin:0 0 22px;font-size:22px;font-weight:500\">Tu pedido ha sido registrado</h1>" +
    "<p>Hola " + esc_(name) + ":</p><p>Gracias por sumarte a <i>El Proyecto Empatía</i>. Tu pedido ha quedado registrado.</p>" +
    "<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;background:#f6f3ee;border:1px solid #ded8ce\">" +
    "<tr><td style=\"padding:12px 16px\">" + esc_(edition) + "</td><td align=\"right\" style=\"padding:12px 16px;color:#403c78;font-weight:bold\">S/" + price + "</td></tr>" +
    "<tr><td style=\"padding:0 16px 12px;color:#5f5a54\">Delivery</td><td align=\"right\" style=\"padding:0 16px 12px;color:#5f5a54\">S/" + CONFIG.DELIVERY + " soles adicionales</td></tr>" +
    "<tr><td style=\"padding:12px 16px;border-top:1px solid #ded8ce;font-weight:bold\">Total a coordinar</td><td align=\"right\" style=\"padding:12px 16px;border-top:1px solid #ded8ce;color:#403c78;font-weight:bold\">S/" + total + "</td></tr></table>" +
    "<h2 style=\"margin:24px 0 6px;font-family:Georgia,serif;font-size:19px;font-weight:normal\">¿Qué sigue ahora?</h2>" +
    "<p>Te escribiremos a la brevedad para coordinar el pago y la entrega. Si necesitas hacer algún ajuste, responde a este correo o escríbenos por WhatsApp.</p>" +
    "<p style=\"margin:22px 0\"><a href=\"" + esc_(whatsapp) + "\" style=\"display:inline-block;padding:12px 20px;border-radius:24px;background:#403c78;color:#fff;text-decoration:none\">Escribir por WhatsApp →</a></p>" +
    "<div style=\"margin-top:30px;padding-top:18px;border-top:1px solid #ded8ce;color:#5f5a54\"><strong style=\"color:#2d2a59\">El Proyecto Empatía</strong><br>Una novela de Carlos Fernández Gates<br>Akuela Innovations<br><a href=\"mailto:empatia@elproyectoempatia.com\" style=\"color:#403c78\">empatia@elproyectoempatia.com</a></div>" +
    "</div></div>";
}

function getSheet_(name) {
  const id = PropertiesService.getScriptProperties().getProperty("SPREADSHEET_ID");
  if (!id) throw new Error("Ejecuta setup() antes de publicar la aplicación.");
  const spreadsheet = SpreadsheetApp.openById(id);
  const sheet = spreadsheet.getSheetByName(name);
  if (!sheet) throw new Error("Falta la pestaña " + name + ".");
  return sheet;
}

function ensureSheet_(spreadsheet, name, headers) {
  let sheet = spreadsheet.getSheetByName(name);
  if (!sheet) sheet = spreadsheet.insertSheet(name);
  if (sheet.getLastRow() === 0) {
    sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
    sheet.setFrozenRows(1);
    sheet.getRange(1, 1, 1, headers.length).setFontWeight("bold");
  }
}

function clean_(value, maxLength) {
  return String(value || "").replace(/[\r\n]+/g, " ").trim().slice(0, maxLength);
}

function validEmail_(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function esc_(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;").replace(/</g, "&lt;")
    .replace(/>/g, "&gt;").replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function page_(title, message, success, detail) {
  const status = success ? "Solicitud procesada" : "No se pudo procesar";
  const color = success ? "#403c78" : "#8b3535";
  return HtmlService.createHtmlOutput(
    "<!doctype html><html lang=\"es\"><head><meta charset=\"utf-8\">" +
    "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">" +
    "<meta name=\"color-scheme\" content=\"light\"><title>" + esc_(title) + " · El Proyecto Empatía</title>" +
    "<style>*{box-sizing:border-box}body{margin:0;padding:28px 18px;background:#f5f3ee;color:#252525;font:16px/1.65 Arial,sans-serif}.card{width:min(100%,620px);margin:7vh auto;background:#fffdfa;border:1px solid #d5d0c7;box-shadow:0 18px 55px #25252512}.brand{padding:20px 26px;background:#2d2a59;color:#fff;font:24px/1.2 Georgia,serif}.content{padding:32px 26px}.eyebrow{margin:0 0 10px;color:" + color + ";font-size:11px;letter-spacing:.16em;text-transform:uppercase}.title{margin:0 0 16px;font:32px/1.15 Georgia,serif}.message{margin:0;color:#4b4843}.detail{margin:20px 0 0;color:#77736c;font-size:13px}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px}.button{display:inline-block;padding:12px 20px;border:1px solid #403c78;border-radius:24px;color:#403c78;text-decoration:none}.button.primary{background:#403c78;color:white}.footer{padding:18px 26px;border-top:1px solid #ded8ce;color:#77736c;font-size:12px}.footer a{color:#403c78}</style></head><body>" +
    "<main class=\"card\"><header class=\"brand\">El Proyecto Empatía</header><section class=\"content\">" +
    "<p class=\"eyebrow\">" + status + "</p><h1 class=\"title\">" + esc_(title) + "</h1>" +
    "<p class=\"message\">" + esc_(message) + "</p>" +
    (detail ? "<p class=\"detail\">" + esc_(detail) + "</p>" : "") +
    "<div class=\"actions\"><a class=\"button primary\" href=\"" + CONFIG.SITE_URL + "\">Volver al sitio</a>" +
    "<a class=\"button\" href=\"mailto:" + CONFIG.ADMIN_EMAIL + "\">Escribir a Empatía</a></div></section>" +
    "<footer class=\"footer\"><strong>El Proyecto Empatía</strong><br>Una novela de Carlos Fernández Gates · Akuela Innovations</footer></main></body></html>"
  ).setTitle(title + " · El Proyecto Empatía");
}
