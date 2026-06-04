<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';
use App\Auth;
Auth::requireSuperadmin();
$pdo = getPlatformPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id        = (int)($_POST['id'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $precioArs = (float)($_POST['precio_ars'] ?? 0);
        $precioUsd = (float)($_POST['precio_usd'] ?? 0);
        $activo    = isset($_POST['activo']) ? 1 : 0;
        if ($id) {
            $pdo->prepare('UPDATE plans SET nombre=?, precio_ars=?, precio_usd=?, activo=? WHERE id=?')
                ->execute([$nombre, $precioArs, $precioUsd, $activo, $id]);
        } else {
            $pdo->prepare('INSERT INTO plans (nombre, precio_ars, precio_usd, activo) VALUES (?,?,?,?)')
                ->execute([$nombre, $precioArs, $precioUsd, $activo]);
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE plans SET activo = 1 - activo WHERE id = ?')->execute([$id]);
    }
    header('Location: ' . APP_URL . '/superadmin/planes.php');
    exit;
}

$plans = $pdo->query('SELECT * FROM plans ORDER BY id')->fetchAll();
$edit  = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM plans WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$pageTitle    = 'Planes';
$activeModule = 'superadmin';
include dirname(__DIR__) . '/views/layout/header.php';
?>
<h2>Planes</h2>
<table class="table mt-3">
  <thead><tr><th>Nombre</th><th>ARS</th><th>USD</th><th>Activo</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($plans as $p): ?>
  <tr>
    <td><?= htmlspecialchars($p['nombre']) ?></td>
    <td>$<?= number_format($p['precio_ars'], 2) ?></td>
    <td>$<?= number_format($p['precio_usd'], 2) ?></td>
    <td><?= $p['activo'] ? '✓' : '—' ?></td>
    <td>
      <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <button class="btn btn-sm btn-outline-secondary"><?= $p['activo'] ? 'Desactivar' : 'Activar' ?></button>
      </form>
    </td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>

<h5 class="mt-4"><?= $edit ? 'Editar plan' : 'Nuevo plan' ?></h5>
<form method="post" style="max-width:400px">
  <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
  <div class="mb-2"><input type="text" name="nombre" class="form-control" placeholder="Nombre" value="<?= htmlspecialchars($edit['nombre'] ?? '') ?>" required></div>
  <div class="mb-2"><input type="number" name="precio_ars" step="0.01" class="form-control" placeholder="Precio ARS" value="<?= $edit['precio_ars'] ?? '' ?>" required></div>
  <div class="mb-2"><input type="number" name="precio_usd" step="0.01" class="form-control" placeholder="Precio USD" value="<?= $edit['precio_usd'] ?? '' ?>" required></div>
  <div class="mb-2 form-check"><input type="checkbox" class="form-check-input" name="activo" id="activo" <?= ($edit['activo'] ?? 1) ? 'checked' : '' ?>>
    <label class="form-check-label" for="activo">Activo (visible en landing)</label></div>
  <button type="submit" class="btn btn-primary">Guardar</button>
</form>
<?php include dirname(__DIR__) . '/views/layout/footer.php'; ?>
