<?php
/* =====================================================
   LAYOUT - DATOS DE LA APP INSTALABLE (PWA)
   Se incluye en el <head> del login y de la aplicación:
   el manifiesto (nombre, íconos, colores) y lo que piden
   el iPhone y Android para agregarla a la pantalla de inicio.
   js/pwa.js registra el service worker (sw.js).
===================================================== */
?>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#142544">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="PICKFLOW">
<link rel="apple-touch-icon" href="img/app/icono-apple-180.png">
