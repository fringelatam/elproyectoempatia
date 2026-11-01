# Google Apps Script backend

Esta rama prepara la migración del sitio estático desde cPanel a GitHub Pages. Apps Script recibirá los formularios, guardará los datos en la hoja de Empatía y enviará las notificaciones y confirmaciones por correo. El código no contiene credenciales ni una URL de implementación.

## Estado de preparación

- La hoja **El Proyecto Empatía — Solicitudes** pertenece a `empatia@elproyectoempatia.com` y tiene las pestañas `Pedidos` y `Solicitudes de lectura`.
- El proyecto vinculado de Apps Script contiene `Code.gs`; `setup()` ya guardó el ID de la hoja en las propiedades del script.
- Los formularios de esta rama ya están preparados para enviar campos URL-encoded a Apps Script, con verificación de consentimiento.
- Las acciones tienen el marcador `REPLACE_WITH_EXEC_URL`. No publicar ni fusionar esta rama hasta reemplazarlo por la URL real `/exec` y comprobar los dos formularios.
- Esta rama ya no contiene PHP ni el flujo de despliegue a cPanel. El archivo `CNAME` prepara el dominio personalizado para GitHub Pages.

## Activación

1. En el proyecto de Apps Script vinculado a la hoja, publicar una aplicación web que se ejecute como `empatia@elproyectoempatia.com`.
2. Habilitar acceso público para que cualquier persona pueda enviar los formularios del sitio sin iniciar sesión. El script solo acepta pedidos y solicitudes de lectura, valida los campos y escribe en las dos pestañas.
3. Reemplazar `REPLACE_WITH_EXEC_URL` en `index.html` por la URL `/exec` de la aplicación web.
4. Verificar que un pedido y una solicitud de lectura se guarden en la hoja y generen los correos.
5. Completar la configuración de GitHub Pages y apuntar el DNS del dominio a GitHub Pages antes de fusionar esta rama.

## Datos almacenados

- **Pedidos:** ID, fecha de Perú, nombre, correo, teléfono, profesión, edición, precio del libro, delivery, dedicatoria, consentimiento, estado del pedido y estado de los correos.
- **Solicitudes de lectura:** ID, fecha de Perú, nombre, correo, consentimiento y estado.

El precio del libro permanece separado del delivery. La página de respuesta solo confirma el registro después de guardarlo en la hoja; los estados de correo quedan anotados para detectar avisos fallidos.
