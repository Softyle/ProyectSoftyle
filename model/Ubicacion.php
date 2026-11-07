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

    public function listarConZona()
    {
        return $this->filas("SELECT u.*, z.nombre AS relacionado FROM Ubicacion u JOIN Zona z ON z.id_zona = u.id_zona ORDER BY u.id_ubicacion DESC");
    }

    /* Ubicaciones libres para asignar a un producto (más la que ya tiene, al editar) */
    public function disponibles($incluirId = 0)
    {
        return $this->filas("SELECT u.id_ubicacion, u.pasillo, u.estante, u.posicion, z.nombre AS zona
                             FROM Ubicacion u JOIN Zona z ON z.id_zona = u.id_zona
                             WHERE u.disponible = 1 OR u.id_ubicacion = ?
                             ORDER BY z.nombre, u.pasillo, u.estante, u.posicion", 'i', [$incluirId]);
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
