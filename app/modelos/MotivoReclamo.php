<?php
require_once __DIR__ . '/MotivoBase.php';

/**
 * PRODUCTO CONCRETO — motivos de RECLAMOS (tabla unificada `motivos`, tipo='reclamo').
 */
class MotivoReclamo extends MotivoBase
{
    protected function tipo(): string { return 'reclamo'; }

    /** Alias histórico usado por ajax/motivo.php. */
    public function filterMotivos($select, $where) { return $this->filtrar($select, $where); }
}
