# PICKFLOW — Sistema de Gestión Logística con Zonas y Picking Asistido

Proyecto de 3.º BT Tecnologías de la Información (Talleres Don Bosco, 2026) del equipo **Softyle**.

Aplicación web en PHP con arquitectura **MVC**, MySQL/MariaDB, JavaScript (jQuery, AJAX) y Bootstrap 5.

## Qué hace
- **Administrador:** productos, zonas, ubicaciones (código `P02-E01-N01`: pasillo, estantería, nivel), pedidos, asignación a operarios, validación, cronograma de despachos, reportes, desempeño y logros, monitor y reporte de sensores, y una API simulada de pedidos externos.
- **Operario:** sus pedidos, hoja de ruta de picking (la zona refrigerada siempre al final), confirmación de cada retiro, historial, logros y perfil.
- **Sitio público** en español e inglés con formulario de contacto.

## Estructura
```
index.php        controlador frontal: index.php?c=<controlador>&a=<acción>
controller/      controladores (reciben el pedido, validan y eligen la vista)
model/           modelos (consultas SQL con sentencias preparadas)
view/            vistas (HTML/PHP) y el diseño común (layout)
css/ js/ img/    estilos, scripts del navegador e imágenes
uploads/         archivos que suben los usuarios (no se versionan)
softyleDEF.sql   base de datos completa: tablas, vista, triggers, procedimientos y datos de prueba
```

## Instalación (WAMP / XAMPP / LAMP)
1. Copiar la carpeta en el directorio web (por ejemplo `C:\wamp64\www\proyectosoftyle`).
2. Importar `softyleDEF.sql` en MySQL/MariaDB (crea la base `softyle`).
3. Revisar los datos de conexión en `model/Conexion.php` (servidor, usuario, contraseña, base y puerto).
4. PHP 8.1 o superior con las extensiones `mysqli` (mysqlnd), `mbstring` y `fileinfo`.
5. Entrar a `http://localhost/proyectosoftyle/`.

Los usuarios y contraseñas de prueba se entregan aparte (no se publican en este repositorio).
