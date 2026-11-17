# PICKFLOW — Sistema de Gestión Logística con Zonas y Picking Asistido

Proyecto de 3.º BT Tecnologías de la Información (Talleres Don Bosco, 2026) del equipo **Softyle**.

Aplicación web en PHP con arquitectura **MVC**, MySQL/MariaDB, JavaScript (jQuery, AJAX) y Bootstrap 5.

## Qué hace
- **Administrador:** productos (con código de barras y fotos), zonas, ubicaciones (código `P02-E01-N01`: pasillo, estantería, nivel), mapa del depósito, pedidos (alta por categoría, asignación y validación), cronograma de despachos, reportes con exportación a Excel/PDF/CSV, desempeño y logros, monitor y reporte de sensores, y una API simulada de pedidos externos.
- **Abastecimiento:** recepción de mercadería con lotes y vencimientos (se retira primero lo que vence antes), vencimientos y bajas, conteos de inventario por zona (también "a ciegas") con ajuste automático del stock, proveedores, reposición sugerida y órdenes de compra, e historial de stock de cada producto.
- **Operario:** sus pedidos, hoja de ruta de picking con lector de código de barras (la zona refrigerada siempre al final), confirmación de cada retiro, recepción de mercadería, conteos asignados, historial, logros y perfil.
- **Avisos:** campanita con lo que hay que atender según el rol (stock bajo, vencimientos, pedidos y conteos pendientes, órdenes atrasadas, alertas de temperatura).
- **App instalable (PWA):** se puede agregar a la pantalla de inicio del celular; sin conexión muestra un aviso en lugar del error del navegador.
- **Sitio público** en español e inglés con formulario de contacto.

## Estructura
```
index.php        controlador frontal: index.php?c=<controlador>&a=<acción>
controller/      controladores (reciben el pedido, validan y eligen la vista)
model/           modelos (consultas SQL con sentencias preparadas)
view/            vistas (HTML/PHP) y el diseño común (layout)
css/ js/ img/    estilos, scripts del navegador e imágenes (img/app: íconos de la app instalable)
manifest.json    datos de la app instalable (nombre, íconos, colores)
sw.js            service worker de la app instalable (offline.html: página sin conexión)
uploads/         archivos que suben los usuarios (no se versionan)
softyleDEF.sql   base de datos completa: tablas, vista, triggers, procedimientos y datos de prueba
softyle_actualizacion_*.sql   cambios de la base posteriores a softyleDEF.sql
softyle_productos_*.sql       cargas opcionales de productos reales (con fotos y códigos)
```

## Instalación (WAMP / XAMPP / LAMP)
1. Copiar la carpeta en el directorio web (por ejemplo `C:\wamp64\www\proyectosoftyle`).
2. Importar `softyleDEF.sql` en MySQL/MariaDB (crea la base `softyle`).
3. Correr sobre la base `softyle`, en este orden: `softyle_actualizacion_foto_oculta.sql`, `softyle_actualizacion_intentos_login.sql` y `softyle_actualizacion_abastecimiento.sql`. No borran datos y se pueden volver a correr sin problema.
4. Revisar los datos de conexión en `model/Conexion.php` (servidor, usuario, contraseña, base y puerto).
5. PHP 8.1 o superior con las extensiones `mysqli` (mysqlnd), `mbstring` y `fileinfo`.
6. Entrar a `http://localhost/proyectosoftyle/`. Para instalarla como app en el celular hace falta publicarla con HTTPS.

Los usuarios y contraseñas de prueba se entregan aparte (no se publican en este repositorio).
