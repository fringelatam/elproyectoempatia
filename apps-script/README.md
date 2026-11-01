# Google Apps Script backend

El sitio estático se sirve desde GitHub Pages. Apps Script recibe los dos formularios, escribe en la hoja de Empatía y envía los avisos y las confirmaciones por correo. No se incluye ninguna credencial ni URL desplegada en este código.

## Preparación en el Workspace de Empatía

1. Abre la hoja **El Proyecto Empatía — Solicitudes** desde la cuenta `empatia@elproyectoempatia.com`.
2. En **Extensiones → Apps Script**, pega `Code.gs` en el proyecto vinculado.
3. Guarda y ejecuta `setup()` una vez. La función guarda el ID de esta hoja en las propiedades del script y verifica las pestañas `Pedidos` y `Solicitudes de lectura`.
4. Publica el proyecto como aplicación web, ejecutándolo como la cuenta de Empatía. El acceso público del formulario debe habilitarse para aceptar solicitudes sin iniciar sesión.
5. Configura la URL `/exec` en el sitio y verifica un pedido y una solicitud de lectura antes de retirar el backend anterior.

## Datos almacenados

- **Pedidos:** ID, fecha de Perú, nombre, correo, teléfono, profesión, edición, precio del libro, delivery, dedicatoria, consentimiento, estado del pedido y estado de los correos.
- **Solicitudes de lectura:** ID, fecha de Perú, nombre, correo, consentimiento y estado.

El precio del libro permanece separado del delivery. El formulario solo comunica éxito cuando el registro queda guardado en la hoja; los estados de envío de correo quedan registrados para poder detectar fallos.
