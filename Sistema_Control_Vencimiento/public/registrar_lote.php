<?php
// public/registrar_lote.php - Formulario público para registrar lote
require_once __DIR__ . '/../src/conexion.php';
require_once __DIR__ . '/../src/autenticacion.php';
require_login_pagina(); // exige sesión

// Cargar productos y proveedores para SELECT
$productos = $pdo->query("SELECT idProducto, nombre, codigoProducto FROM Producto ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$proveedores = $pdo->query("SELECT idProveedor, nombre FROM Proveedor ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar lote</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/registrar_lote.css">
</head>
<body>
  <div class="card" role="main">

    <a href="inicio.php" class="btn-volver">← Volver</a>

    <h2>Registrar lote</h2>
    <form id="frm">
      <label for="idProducto">Producto</label>
      <select id="idProducto" name="idProducto" required>
        <option value="">-- seleccionar producto --</option>
        <?php foreach($productos as $p): ?>
          <option value="<?= (int)$p['idProducto'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['codigoProducto']) ?>)</option>
        <?php endforeach; ?>
      </select>

      <label for="codigoLote">Código de lote</label>
      <input id="codigoLote" name="codigoLote" type="text" required>

      <label for="idProveedor">Proveedor (opcional)</label>
      <select id="idProveedor" name="idProveedor">
        <option value="">-- ninguno --</option>
        <?php foreach($proveedores as $pr): ?>
          <option value="<?= (int)$pr['idProveedor'] ?>"><?= htmlspecialchars($pr['nombre']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="row">
        <div>
          <label for="fechaIngreso">Fecha de ingreso</label>
          <input id="fechaIngreso" name="fechaIngreso" type="date" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div>
          <label for="fechaVencimiento">Fecha de vencimiento</label>
          <input id="fechaVencimiento" name="fechaVencimiento" type="date" required>
        </div>
      </div>

      <label for="cantidad">Cantidad</label>
      <input id="cantidad" name="cantidad" type="number" min="0" value="0" required>

      <button type="submit">Guardar lote</button>
    </form>

    <div id="msg" class="msg" aria-live="polite" style="display:none"></div>
  </div>

<script>
document.getElementById('frm').addEventListener('submit', async function(e){
  e.preventDefault();
  const form = new FormData(this);
  const msgEl = document.getElementById('msg');
  msgEl.style.display = 'none';
  msgEl.className = 'msg';

  try {
    const resp = await fetch('api/registrar_lote.php', { method: 'POST', body: form });
    const j = await resp.json();

    if (j.ok) {
      msgEl.textContent = 'Lote registrado correctamente. ID: ' + (j.id_lote || '');
      msgEl.classList.add('ok');
      msgEl.style.display = 'block';
      this.reset();
      document.getElementById('fechaIngreso').value = new Date().toISOString().slice(0,10);
    } else {
      msgEl.textContent = 'Error: ' + (j.error || j.detalle || 'sin detalle');
      msgEl.classList.add('err');
      msgEl.style.display = 'block';
    }
  } catch (err) {
    msgEl.textContent = 'Error en la petición: ' + err.message;
    msgEl.classList.add('err');
    msgEl.style.display = 'block';
  }
});
</script>
</body>
</html>
