<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Genera miniaturas con GD, que ya viene con PHP. No merece la pena una
 * dependencia extra para redimensionar: las parrillas solo necesitan un jpg
 * pequeño y la original se sirve tal cual en el detalle del anuncio.
 */
class ThumbnailMaker
{
    public const ANCHO = 480;

    public const CALIDAD = 80;

    /** Devuelve la ruta de la miniatura, o null si la imagen no se pudo procesar. */
    public function generar(string $rutaOriginal, string $disco = 'public'): ?string
    {
        $almacen = Storage::disk($disco);

        $original = @imagecreatefromstring($almacen->get($rutaOriginal));

        if ($original === false) {
            return null;
        }

        $ancho = imagesx($original);
        $alto = imagesy($original);

        // Una imagen ya pequeña se copia tal cual: ampliarla solo la empeora.
        $nuevoAncho = min(self::ANCHO, $ancho);
        $nuevoAlto = (int) round($alto * ($nuevoAncho / $ancho));

        $miniatura = imagescale($original, $nuevoAncho, $nuevoAlto);
        imagedestroy($original);

        if ($miniatura === false) {
            return null;
        }

        // El fondo blanco evita que las transparencias salgan en negro.
        $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagecopy($lienzo, $miniatura, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto);
        imagedestroy($miniatura);

        ob_start();
        imagejpeg($lienzo, null, self::CALIDAD);
        $contenido = ob_get_clean();
        imagedestroy($lienzo);

        $ruta = preg_replace('/\.\w+$/', '', $rutaOriginal).'_thumb.jpg';
        $almacen->put($ruta, $contenido);

        return $ruta;
    }
}
