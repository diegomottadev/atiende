<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
// Define getPlatformPDO() / getProvisionerPDO().
// Solo declara funciones; no abre conexión al incluirse, así que es
// inofensivo para los tests que no tocan la DB (ej. EncryptionTest).
require_once dirname(__DIR__) . '/config/database.php';
