<?php
/* =====================================================
   MODELO - UBICACIÓN
   Posiciones físicas del depósito: pasillo-estante-nivel
   (por ejemplo P02-E01-N01) dentro de una zona. El estante E00
   es un sector de piso (P05-E00-N02) para productos voluminosos.
===================================================== */
class Ubicacion extends Modelo
{
    // Formato de cada parte del código: [expresión regular, mensaje de error, ejemplo]
    const FORMATOS = [
        'pasillo'  => ['/^P[0-9]{2}$/', 'El pasillo debe ser P y dos números, por ejemplo P02.', 'P02'],
        'estante'  => ['/^E[0-9]{2}$/', 'La estantería debe ser E y dos números, por ejemplo E01 (E00 = sector de piso).', 'E01'],
        'posicion' => ['/^N[0-9]{2}$/', 'El nivel debe ser N y dos números, por ejemplo N01.', 'N01'],
    ];

    /* Los sectores de piso (productos voluminosos que no se apilan) usan el estante E00 */
    const SECTOR_PISO = 'E00';

    public static function esSectorPiso($estante)
    {
        return strtoupper((string)$estante) === self::SECTOR_PISO;
    }

    /* Ubicación explicada en palabras para la hoja de ruta:
       "Pasillo P02 · Estante E03 · Nivel N01" o "Pasillo P05 · Sector de piso N02" */
    public static function detalle($pasillo, $estante, $posicion)
    {
        return self::esSectorPiso($estante)
            ? 'Pasillo ' . $pasillo . ' · Sector de piso ' . $posicion
            : 'Pasillo ' . $pasillo . ' · Estante ' . $estante . ' · Nivel ' . $posicion;
    }

    /* Texto de la ubicación física: zona + pasillo-estante-nivel */
    public static function texto($zona, $pasillo, $estante, $posicion)
    {
        return trim($zona . ' · ' . $pasillo . '-' . $estante . '-' . $posicion, ' ·');
    }

    /* Plano del depósito: todas las ubicaciones (también las vacías) con el producto que guardan */
    public function planoConProductos()
    {
        return $this->filas("SELECT u.id_ubicacion, u.id_zona, u.pasillo, u.estante, u.posicion,
                                    p.id_producto, p.Nombre, IF(p.foto_oculta, NULL, p.foto) AS foto, p.stock_actual, p.stock_minimo
                             FROM Ubicacion u
                             LEFT JOIN Producto p ON p.id_ubicacion = u.id_ubicacion
                             ORDER BY u.pasillo, u.estante, u.posicion, p.Nombre");
    }

    public function listarConZona()
    {
        return $this->filas("SELECT u.*, z.nombre AS relacionado FROM Ubicacion u JOIN Zona z ON z.id_zona = u.id_zona ORDER BY u.id_ubicacion DESC");
    }

    /* Ubicaciones libres para asignar a un producto: habilitadas, de una zona activa y sin
       ningún producto guardado. Al editar se suma la que el producto ya tiene. */
    public function disponibles($incluirId = 0)
    {
        return $this->filas("SELECT u.id_ubicacion, u.pasillo, u.estante, u.posicion, u.id_zona, z.nombre AS zona
                             FROM Ubicacion u JOIN Zona z ON z.id_zona = u.id_zona
                             WHERE (u.disponible = 1 AND z.estado = 'Activa'
                                    AND NOT EXISTS (SELECT 1 FROM Producto p WHERE p.id_ubicacion = u.id_ubicacion))
                                OR u.id_ubicacion = ?
                             ORDER BY z.nombre, u.pasillo, u.estante, u.posicion", 'i', [$incluirId]);
    }

    /* true si la ubicación está libre para ese producto (la que ya ocupa él mismo cuenta como libre) */
    public function estaLibre($idUbicacion, $idProducto = 0)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Ubicacion u JOIN Zona z ON z.id_zona = u.id_zona
                                  WHERE u.id_ubicacion = ? AND u.disponible = 1 AND z.estado = 'Activa'
                                    AND NOT EXISTS (SELECT 1 FROM Producto p WHERE p.id_ubicacion = u.id_ubicacion AND p.id_producto <> ?)",
            'ii', [$idUbicacion, $idProducto]) > 0;
    }

    /* Zona y pasillo que se crearían si no queda lugar: la primera letra sin usar y el pasillo siguiente al último.
       Devuelve ['nombre' => 'Zona H', 'pasillo' => 'P08'] o null si ya se usaron las 26 letras o los 99 pasillos. */
    public function proximaZona()
    {
        $usadas = array_column($this->filas("SELECT nombre FROM Zona"), 'nombre');
        $libres = array_values(array_diff(Zona::nombresPosibles(), $usadas));
        $ultimo = (int)$this->valor("SELECT COALESCE(MAX(CAST(SUBSTRING(pasillo, 2) AS UNSIGNED)), 0) FROM Ubicacion");
        if (!$libres || $ultimo >= 99) {
            return null;
        }
        return ['nombre' => $libres[0], 'pasillo' => sprintf('P%02d', $ultimo + 1)];
    }

    /* Cuando no queda ninguna ubicación libre: crea una zona nueva (siguiente letra) con un pasillo
       nuevo de $estanterias estanterías x $niveles niveles. Devuelve la zona creada y su primera ubicación. */
    public function crearZonaNueva($tipo, $idDeposito, $estanterias = 3, $niveles = 5)
    {
        $this->iniciarTransaccion();
        try {
            $proxima = $this->proximaZona();
            if (!$proxima) {
                throw new RuntimeException('No quedan letras o pasillos para crear otra zona.');
            }
            $idDeposito = (int)$idDeposito ?: (int)$this->valor("SELECT MIN(id_deposito) FROM Deposito");
            $idZona = (new Zona())->crear(['nombre' => $proxima['nombre'], 'tipo' => $tipo, 'capacidad_total' => 500,
                                           'estado' => 'Activa', 'id_deposito' => $idDeposito]);
            $primera = 0;
            for ($e = 1; $e <= $estanterias; $e++) {
                for ($n = 1; $n <= $niveles; $n++) {
                    $id = $this->crear(['pasillo' => $proxima['pasillo'], 'estante' => sprintf('E%02d', $e),
                                        'posicion' => sprintf('N%02d', $n), 'disponible' => 1, 'id_zona' => $idZona]);
                    $primera = $primera ?: $id;
                }
            }
            $this->confirmarTransaccion();
            return $proxima + ['id_zona' => $idZona, 'id_ubicacion' => $primera, 'ubicaciones' => $estanterias * $niveles];
        } catch (Throwable $ex) {
            $this->deshacerTransaccion();
            throw $ex;
        }
    }

    public function buscar($id)
    {
        return $this->fila("SELECT * FROM Ubicacion WHERE id_ubicacion = ?", 'i', [$id]);
    }

    /* El código pasillo-estante-posición no se puede repetir en todo el depósito */
    public function existeCodigo($pasillo, $estante, $posicion, $excluirId)
    {
        return (int)$this->valor("SELECT COUNT(*) FROM Ubicacion WHERE pasillo = ? AND estante = ? AND posicion = ? AND id_ubicacion <> ?",
            'sssi', [$pasillo, $estante, $posicion, $excluirId]) > 0;
    }

    public function crear(array $v)
    {
        return $this->insertar("INSERT INTO Ubicacion (pasillo, estante, posicion, disponible, id_zona) VALUES (?, ?, ?, ?, ?)",
            'sssii', [$v['pasillo'], $v['estante'], $v['posicion'], (int)$v['disponible'], (int)$v['id_zona']]);
    }

    public function actualizar($id, array $v)
    {
        return $this->ejecutar("UPDATE Ubicacion SET pasillo = ?, estante = ?, posicion = ?, disponible = ?, id_zona = ? WHERE id_ubicacion = ?",
            'sssiii', [$v['pasillo'], $v['estante'], $v['posicion'], (int)$v['disponible'], (int)$v['id_zona'], $id]);
    }

    public function eliminar($id)
    {
        return $this->ejecutar("DELETE FROM Ubicacion WHERE id_ubicacion = ?", 'i', [$id]);
    }
}
