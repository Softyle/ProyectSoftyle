<?php
/* =====================================================
   CÓDIGOS DE BARRAS (EAN-13, EAN-8 y UPC-A)
   - valida un código leído o escrito (dígito verificador)
   - genera el código interno de un producto: EAN-13 que
     empieza con "20", el rango reservado para uso interno,
     así no choca con el código de un producto del mercado
   - dibuja la etiqueta EAN-13 en SVG para imprimirla
   No consulta la base: los datos están en Producto.codigo_barras.
===================================================== */
class CodigoBarras
{
    // Patrones de barras de cada dígito (1 = barra, 0 = espacio)
    const L = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];
    const G = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];
    const R = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'];
    // El primer dígito no se dibuja: define si cada dígito de la izquierda usa L o G
    const PARIDAD = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];

    /* Deja solo los dígitos: "7 790001 234567" -> "7790001234567" */
    public static function limpiar($codigo)
    {
        return preg_replace('/\D/', '', (string)$codigo);
    }

    /* Dígito verificador de EAN-13, EAN-8 y UPC-A (los datos sin el último dígito).
       Desde la derecha, los dígitos se multiplican alternando por 3 y por 1. */
    public static function digitoVerificador($datos)
    {
        $suma = 0;
        foreach (array_reverse(str_split($datos)) as $i => $digito) {
            $suma += (int)$digito * ($i % 2 === 0 ? 3 : 1);
        }
        return (10 - $suma % 10) % 10;
    }

    /* true si tiene 8, 12 o 13 dígitos y el verificador es correcto */
    public static function valido($codigo)
    {
        $codigo = (string)$codigo;
        if (!preg_match('/^(\d{8}|\d{12}|\d{13})$/', $codigo)) {
            return false;
        }
        return self::digitoVerificador(substr($codigo, 0, -1)) === (int)substr($codigo, -1);
    }

    /* Código interno del producto: "20" + id con ceros (10 dígitos) + verificador */
    public static function interno($idProducto)
    {
        $datos = '20' . str_pad((string)(int)$idProducto, 10, '0', STR_PAD_LEFT);
        return $datos . self::digitoVerificador($datos);
    }

    /* Etiqueta EAN-13 en SVG (barras y números). Para otros formatos devuelve ''. */
    public static function svg($codigo, $alto = 45)
    {
        if (!preg_match('/^\d{13}$/', (string)$codigo)) {
            return '';
        }
        $d = array_map('intval', str_split($codigo));
        $paridad = self::PARIDAD[$d[0]];
        // 95 módulos: guarda + 6 dígitos + guarda central + 6 dígitos + guarda
        $modulos = '101';
        for ($i = 1; $i <= 6; $i++) {
            $modulos .= $paridad[$i - 1] === 'L' ? self::L[$d[$i]] : self::G[$d[$i]];
        }
        $modulos .= '01010';
        for ($i = 7; $i <= 12; $i++) {
            $modulos .= self::R[$d[$i]];
        }
        $modulos .= '101';

        $margen = 11;                       // zona libre a la izquierda (lugar del primer dígito)
        $ancho = $margen + 95 + 7;
        $altoTexto = 12;
        $barras = '';
        foreach (str_split($modulos) as $x => $bit) {
            if ($bit !== '1') {
                continue;
            }
            // Las guardas (inicio, centro y fin) bajan un poco más que el resto
            $esGuarda = $x < 3 || ($x >= 45 && $x < 50) || $x >= 92;
            $h = $esGuarda ? $alto + 6 : $alto;
            $barras .= '<rect x="' . ($margen + $x) . '" y="0" width="1" height="' . $h . '"/>';
        }
        $y = $alto + $altoTexto - 1;
        $texto = '<text x="4" y="' . $y . '">' . $d[0] . '</text>'
            . '<text x="' . ($margen + 24) . '" y="' . $y . '" text-anchor="middle">' . substr($codigo, 1, 6) . '</text>'
            . '<text x="' . ($margen + 71) . '" y="' . $y . '" text-anchor="middle">' . substr($codigo, 7, 6) . '</text>';

        return '<svg class="codigo-barras" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $ancho . ' ' . ($alto + $altoTexto + 2) . '"'
            . ' role="img" aria-label="Código de barras ' . $codigo . '"><rect width="100%" height="100%" fill="#fff"/>'
            . '<g fill="#000">' . $barras . '</g><g font-family="monospace" font-size="10" fill="#000">' . $texto . '</g></svg>';
    }
}
