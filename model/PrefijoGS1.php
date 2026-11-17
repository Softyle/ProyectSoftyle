<?php
/* =====================================================
   PREFIJOS GS1
   Los primeros dígitos de un EAN-13 dicen qué organización GS1
   entregó el código (el país donde la empresa registró la marca,
   no necesariamente donde se fabricó). Sirve para dar un dato de
   cualquier código, aunque ninguna base pública tenga el producto.
   Tabla: https://www.gs1.org/standards/id-keys/company-prefix
   No consulta la base ni internet.
===================================================== */
class PrefijoGS1
{
    // [desde, hasta, país u organización] con prefijos de 3 dígitos
    const RANGOS = [
        [0, 19, 'Estados Unidos y Canadá'], [20, 29, 'uso interno de un comercio (peso variable o código propio)'],
        [30, 39, 'Estados Unidos y Canadá'], [40, 49, 'uso interno de un comercio'], [50, 59, 'cupones'],
        [60, 139, 'Estados Unidos y Canadá'], [200, 299, 'uso interno de un comercio (peso variable o código propio)'],
        [300, 379, 'Francia y Mónaco'], [380, 380, 'Bulgaria'], [383, 383, 'Eslovenia'], [385, 385, 'Croacia'],
        [387, 387, 'Bosnia y Herzegovina'], [389, 389, 'Montenegro'], [390, 390, 'Kosovo'], [400, 440, 'Alemania'],
        [450, 459, 'Japón'], [460, 469, 'Rusia'], [470, 470, 'Kirguistán'], [471, 471, 'Taiwán'], [474, 474, 'Estonia'],
        [475, 475, 'Letonia'], [476, 476, 'Azerbaiyán'], [477, 477, 'Lituania'], [478, 478, 'Uzbekistán'],
        [479, 479, 'Sri Lanka'], [480, 480, 'Filipinas'], [481, 481, 'Bielorrusia'], [482, 482, 'Ucrania'],
        [483, 483, 'Turkmenistán'], [484, 484, 'Moldavia'], [485, 485, 'Armenia'], [486, 486, 'Georgia'],
        [487, 487, 'Kazajistán'], [488, 488, 'Tayikistán'], [489, 489, 'Hong Kong'], [490, 499, 'Japón'],
        [500, 509, 'Reino Unido'], [520, 521, 'Grecia'], [528, 528, 'Líbano'], [529, 529, 'Chipre'], [530, 530, 'Albania'],
        [531, 531, 'Macedonia del Norte'], [535, 535, 'Malta'], [539, 539, 'Irlanda'], [540, 549, 'Bélgica y Luxemburgo'],
        [560, 560, 'Portugal'], [569, 569, 'Islandia'], [570, 579, 'Dinamarca'], [590, 590, 'Polonia'], [594, 594, 'Rumania'],
        [599, 599, 'Hungría'], [600, 601, 'Sudáfrica'], [603, 603, 'Ghana'], [604, 604, 'Senegal'], [608, 608, 'Baréin'],
        [609, 609, 'Mauricio'], [611, 611, 'Marruecos'], [613, 613, 'Argelia'], [615, 615, 'Nigeria'], [616, 616, 'Kenia'],
        [618, 618, 'Costa de Marfil'], [619, 619, 'Túnez'], [620, 620, 'Tanzania'], [621, 621, 'Siria'], [622, 622, 'Egipto'],
        [623, 623, 'Brunéi'], [624, 624, 'Libia'], [625, 625, 'Jordania'], [626, 626, 'Irán'], [627, 627, 'Kuwait'],
        [628, 628, 'Arabia Saudita'], [629, 629, 'Emiratos Árabes Unidos'], [640, 649, 'Finlandia'], [680, 681, 'China'],
        [690, 699, 'China'], [700, 709, 'Noruega'], [729, 729, 'Israel'], [730, 739, 'Suecia'], [740, 740, 'Guatemala'],
        [741, 741, 'El Salvador'], [742, 742, 'Honduras'], [743, 743, 'Nicaragua'], [744, 744, 'Costa Rica'],
        [745, 745, 'Panamá'], [746, 746, 'República Dominicana'], [750, 750, 'México'], [754, 755, 'Canadá'],
        [759, 759, 'Venezuela'], [760, 769, 'Suiza y Liechtenstein'], [770, 771, 'Colombia'], [773, 773, 'Uruguay'],
        [775, 775, 'Perú'], [777, 777, 'Bolivia'], [778, 779, 'Argentina'], [780, 780, 'Chile'], [784, 784, 'Paraguay'],
        [786, 786, 'Ecuador'], [789, 790, 'Brasil'], [800, 839, 'Italia'], [840, 849, 'España'], [850, 850, 'Cuba'],
        [858, 858, 'Eslovaquia'], [859, 859, 'República Checa'], [860, 860, 'Serbia'], [865, 865, 'Mongolia'],
        [867, 867, 'Corea del Norte'], [868, 869, 'Turquía'], [870, 879, 'Países Bajos'], [880, 880, 'Corea del Sur'],
        [883, 883, 'Myanmar'], [884, 884, 'Camboya'], [885, 885, 'Tailandia'], [888, 888, 'Singapur'], [890, 890, 'India'],
        [893, 893, 'Vietnam'], [896, 896, 'Pakistán'], [899, 899, 'Indonesia'], [900, 919, 'Austria'],
        [930, 939, 'Australia'], [940, 949, 'Nueva Zelanda'], [955, 955, 'Malasia'], [958, 958, 'Macao'],
        [977, 977, 'publicación periódica (ISSN)'], [978, 979, 'libro (ISBN)'], [980, 980, 'recibo de devolución'],
        [981, 984, 'cupones'], [990, 999, 'cupones'],
    ];

    /* "China", "Uruguay", "libro (ISBN)"... o null si el prefijo no está asignado */
    public static function origen($codigo)
    {
        $codigo = (string)$codigo;
        if (strlen($codigo) === 12) {
            return 'Estados Unidos y Canadá';   // UPC-A: los 12 dígitos son de GS1 US
        }
        if (strlen($codigo) === 8 && $codigo[0] !== '0' && $codigo[0] !== '1') {
            $prefijo = (int)substr($codigo, 0, 3);   // EAN-8: también con prefijo GS1 de 3 dígitos
        } elseif (strlen($codigo) === 13) {
            $prefijo = (int)substr($codigo, 0, 3);
        } else {
            return null;
        }
        foreach (self::RANGOS as [$desde, $hasta, $nombre]) {
            if ($prefijo >= $desde && $prefijo <= $hasta) {
                return $nombre;
            }
        }
        return null;
    }

    /* Frase lista para mostrar: "Registrado en China (prefijo 693)." */
    public static function frase($codigo)
    {
        $origen = self::origen($codigo);
        if ($origen === null) {
            return '';
        }
        $esPais = !preg_match('/uso interno|cupones|ISSN|ISBN|recibo/', $origen);
        return ($esPais ? 'Código registrado en ' : 'Código de ') . $origen . ' (prefijo ' . substr((string)$codigo, 0, 3) . ').';
    }
}
