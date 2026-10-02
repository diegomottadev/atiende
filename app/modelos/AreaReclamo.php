<?php
require_once __DIR__ . '/AreaBase.php';

/**
 * PRODUCTO CONCRETO — áreas de RECLAMOS (tabla unificada `areas`, tipo='reclamo').
 */
class AreaReclamo extends AreaBase
{
    protected function tipo(): string { return 'reclamo'; }
}
