-- ============================================================
--  Índices para escalar el volumen de las listas de Ventas/Repartos.
--  Aplicar dentro de la DB de cada tenant:
--    mysql -uroot -proot atiende_<slug> < idx_pedidos_fidelizar.sql
--
--  Motivo: `pedidos` solo tenía PRIMARY(id). Las listas hacen
--  GROUP BY pedidoid y JOIN por clienteId / vendedorId / repartidor_id
--  (todos sin índice) → full table scan. El conteo de mensajes no
--  leídos filtra fidelizar por (pedidoid, estado, tipo).
--
--  NOTA: `pedidos.pedidoid` es TEXT, por eso se indexa con prefijo
--  (pedidoid(20)) — alcanza de sobra para los IDs numéricos.
--
--  Idempotente: MySQL 8.0 no soporta ADD INDEX IF NOT EXISTS, así que
--  cada índice se crea solo si no existe (guarda con information_schema).
--  Es seguro re-ejecutarlo en tenants ya migrados.
-- ============================================================

DROP PROCEDURE IF EXISTS __add_index_if_missing;
DELIMITER //
CREATE PROCEDURE __add_index_if_missing(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_cols VARCHAR(255))
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = p_table AND index_name = p_index
  ) THEN
    SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_cols, ')');
    PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END //
DELIMITER ;

CALL __add_index_if_missing('fidelizar', 'idx_fidelizar_pedido_estado_tipo', '`pedidoid`, `estado`, `tipo`');
CALL __add_index_if_missing('pedidos',   'idx_pedidos_pedidoid',            '`pedidoid`(20)');
CALL __add_index_if_missing('pedidos',   'idx_pedidos_clienteId',           '`clienteId`');
CALL __add_index_if_missing('pedidos',   'idx_pedidos_vendedorId',          '`vendedorId`');
CALL __add_index_if_missing('pedidos',   'idx_pedidos_repartidor',          '`repartidor_id`');

DROP PROCEDURE IF EXISTS __add_index_if_missing;
