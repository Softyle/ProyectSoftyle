<?php
/* =====================================================
   MODELO - DOCUMENTACIÓN TÉCNICA (tabla Documento_Producto)
   Hojas de seguridad y manuales de manipulación de carga.
   Los archivos se guardan en uploads/documentos/.
===================================================== */
class Documento extends Modelo
{
    const CARPETA = 'uploads/documentos';
    const TIPOS = ['Hoja de seguridad', 'Manual de manipulación', 'Otro'];

    public function listarPorProducto($idProducto)
    {
        return $this->filas("SELECT id_documento, tipo_documento, nombre_original, mime, tamano_bytes, fecha_subida
                             FROM Documento_Producto WHERE id_producto = ? ORDER BY fecha_subida DESC", 'i', [$idProducto]);
    }

    /* Documentos de varios productos agrupados por producto (hoja de ruta del operario) */
    public function porProductos(array $ids)
    {
        $ids = array_values(array_map('intval', $ids));
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $documentos = [];
        foreach ($this->filas("SELECT id_documento, id_producto, tipo_documento FROM Documento_Producto
                               WHERE id_producto IN ($marcas) ORDER BY tipo_documento", str_repeat('i', count($ids)), $ids) as $d) {
            $documentos[(int)$d['id_producto']][] = $d;
        }
        return $documentos;
    }

    /* Nombres de archivo de un producto (para borrarlos del disco) */
    public function archivosDeProducto($idProducto)
    {
        return array_column($this->filas("SELECT archivo FROM Documento_Producto WHERE id_producto = ?", 'i', [$idProducto]), 'archivo');
    }

    public function buscar($id)
    {
        return $this->fila("SELECT id_documento, id_producto, nombre_original, archivo, mime FROM Documento_Producto WHERE id_documento = ?", 'i', [$id]);
    }

    public function crear($idProducto, $tipo, $nombreOriginal, $archivo, $mime, $tamano)
    {
        return $this->insertar("INSERT INTO Documento_Producto (id_producto, tipo_documento, nombre_original, archivo, mime, tamano_bytes)
                                VALUES (?, ?, ?, ?, ?, ?)", 'issssi', [$idProducto, $tipo, $nombreOriginal, $archivo, $mime, $tamano]);
    }

    public function eliminar($id)
    {
        return $this->ejecutar("DELETE FROM Documento_Producto WHERE id_documento = ?", 'i', [$id]);
    }
}
