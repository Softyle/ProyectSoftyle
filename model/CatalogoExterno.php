<?php
/* =====================================================
   CATÁLOGOS EXTERNOS DE PRODUCTOS (gratuitos)
   Dado un código de barras, busca el producto en varias bases
   públicas al mismo tiempo y devuelve el primero que aparezca,
   en este orden de prioridad:
     1. Open Food Facts      - alimentos y bebidas
     2. Open Beauty Facts    - perfumería e higiene
     3. Open Pet Food Facts  - alimento para mascotas
     4. Open Products Facts  - el resto (limpieza, bazar, electrónica...)
     5. UPCitemdb (prueba)   - muchos productos importados; 100 consultas por día
   Las cuatro "Open ... Facts" son libres (licencia ODbL, fotos CC BY-SA).
   Las consultas salen juntas (curl_multi): tarda lo que la más lenta,
   con un tope de ESPERA_SEGUNDOS. No usa la base de PICKFLOW.
===================================================== */
class CatalogoExterno
{
    const ESPERA_SEGUNDOS = 6;
    const AGENTE = 'PICKFLOW/1.0 (proyecto educativo)';
    const CAMPOS_OPEN = 'product_name_es,product_name,generic_name_es,brands,quantity,image_front_url';
    const FUENTES = [
        'Open Food Facts'     => 'https://world.openfoodfacts.org/api/v2/product/%s.json?fields=',
        'Open Beauty Facts'   => 'https://world.openbeautyfacts.org/api/v2/product/%s.json?fields=',
        'Open Pet Food Facts' => 'https://world.openpetfoodfacts.org/api/v2/product/%s.json?fields=',
        'Open Products Facts' => 'https://world.openproductsfacts.org/api/v2/product/%s.json?fields=',
        'UPCitemdb'           => 'https://api.upcitemdb.com/prod/trial/lookup?upc=%s',
    ];

    /* ['nombre', 'marca', 'cantidad', 'descripcion', 'foto', 'fuente'] del primero que lo tenga,
       null si ninguna base lo tiene y false si no se pudo consultar ninguna (sin conexión). */
    public static function buscar($codigo)
    {
        if (!preg_match('/^\d{8,14}$/', (string)$codigo)) {
            return null;
        }
        if (!function_exists('curl_multi_init')) {
            return false;
        }
        $multi = curl_multi_init();
        $pedidos = [];
        foreach (self::FUENTES as $fuente => $plantilla) {
            $url = sprintf($plantilla, $codigo) . (strpos($plantilla, 'fields=') !== false ? self::CAMPOS_OPEN : '');
            $c = curl_init($url);
            curl_setopt_array($c, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::ESPERA_SEGUNDOS,
                CURLOPT_CONNECTTIMEOUT => self::ESPERA_SEGUNDOS,
                CURLOPT_USERAGENT => self::AGENTE,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            curl_multi_add_handle($multi, $c);
            $pedidos[$fuente] = $c;
        }
        do {
            $estado = curl_multi_exec($multi, $activos);
            if ($activos) {
                curl_multi_select($multi, 1.0);
            }
        } while ($activos && $estado === CURLM_OK);

        $respondio = false;
        $resultado = null;
        foreach ($pedidos as $fuente => $c) {   // en orden de prioridad
            $cuerpo = curl_multi_getcontent($c);
            $http = curl_getinfo($c, CURLINFO_HTTP_CODE);
            if ($http >= 200 && $http < 500 && $cuerpo !== null && $cuerpo !== '') {
                $respondio = true;
            } elseif (curl_error($c)) {
                error_log('PICKFLOW catálogo ' . $fuente . ': ' . curl_error($c));
            }
            if ($resultado === null && $http === 200 && $cuerpo) {
                $datos = json_decode($cuerpo, true);
                $producto = $fuente === 'UPCitemdb' ? self::leerUpcitemdb($datos) : self::leerOpen($datos);
                if ($producto) {
                    $resultado = $producto + ['fuente' => $fuente];
                }
            }
            curl_multi_remove_handle($multi, $c);
        }
        curl_multi_close($multi);
        return $resultado ?? ($respondio ? null : false);
    }

    /* Respuesta de las bases "Open ... Facts" (misma API) */
    private static function leerOpen($datos)
    {
        $p = $datos['product'] ?? null;
        if (($datos['status'] ?? 0) !== 1 || !is_array($p)) {
            return null;
        }
        $nombre = self::texto($p['product_name_es'] ?? '') ?: self::texto($p['product_name'] ?? '');
        if ($nombre === '') {
            return null;   // registrado pero sin nombre: no sirve para completar el formulario
        }
        return self::armar($nombre, explode(',', (string)($p['brands'] ?? ''))[0], $p['quantity'] ?? '',
            $p['generic_name_es'] ?? '', $p['image_front_url'] ?? '');
    }

    /* Respuesta de UPCitemdb: {"code":"OK","items":[{"title","brand","description","images":[...]}]} */
    private static function leerUpcitemdb($datos)
    {
        $item = $datos['items'][0] ?? null;
        if (($datos['code'] ?? '') !== 'OK' || !is_array($item) || self::texto($item['title'] ?? '') === '') {
            return null;
        }
        $foto = '';
        foreach ((array)($item['images'] ?? []) as $imagen) {
            if (strncasecmp((string)$imagen, 'https://', 8) === 0) {
                $foto = $imagen;
                break;
            }
        }
        return self::armar(self::texto($item['title']), $item['brand'] ?? '', '', $item['description'] ?? '', $foto);
    }

    /* Nombre para PICKFLOW: marca + nombre + presentación, sin repetir la marca */
    private static function armar($nombre, $marca, $cantidad, $descripcion, $foto)
    {
        $marca = self::texto($marca);
        $cantidad = preg_replace('/\s+(℮|e)$/u', '', self::texto($cantidad));   // "400 g ℮" -> "400 g"
        $completo = $nombre;
        if ($marca !== '' && strpos(self::clave($nombre), self::clave($marca)) === false) {
            $completo = $marca . ' ' . $completo;
        }
        if ($cantidad !== '' && strpos(self::clave($completo), self::clave($cantidad)) === false) {
            $completo .= ' ' . $cantidad;
        }
        return [
            'nombre' => mb_substr($completo, 0, 100),
            'marca' => $marca,
            'cantidad' => $cantidad,
            'descripcion' => mb_substr(self::texto($descripcion), 0, 255),
            'foto' => preg_match('#^https://[^\s"<>]{1,240}$#', (string)$foto) ? $foto : '',
        ];
    }

    private static function clave($texto)
    {
        return preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($texto));
    }

    private static function texto($valor)
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string)$valor)));
    }
}
