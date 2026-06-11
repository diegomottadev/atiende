<?php
/**
 * Normalización de números de teléfono al formato wa_id según el país del tenant.
 * Lógica pura (sin DB) para que sea unit-testeable. Ver tests/TelefonoNormalizarTest.php.
 */
class Telefono
{
    /** cc = código de país; movil9 = inserta el '9' móvil (solo Argentina). */
    const PAISES = [
        'AR' => ['cc' => '54',  'movil9' => true],
        'BR' => ['cc' => '55'],
        'MX' => ['cc' => '52'],
        'UY' => ['cc' => '598'],
        'CL' => ['cc' => '56'],
        'PY' => ['cc' => '595'],
        'CO' => ['cc' => '57'],
        'PE' => ['cc' => '51'],
    ];

    /** Normaliza un número tipeado a formato wa_id según el país (ISO-2). Vacío → ''. */
    public static function normalizar($numero, $pais = 'AR'): string
    {
        $d = preg_replace('/\D/', '', (string) $numero);
        $d = ltrim($d, '0');                                  // troncal local (0…) primero → evita prefijo duplicado
        if ($d === '') return '';
        $p  = self::PAISES[$pais] ?? self::PAISES['AR'];       // país desconocido → AR (retrocompat)
        $cc = $p['cc'];
        if (strncmp($d, $cc, strlen($cc)) === 0) return $d;   // ya trae código de país → tal cual
        return !empty($p['movil9']) ? $cc . '9' . $d : $cc . $d;
    }
}
