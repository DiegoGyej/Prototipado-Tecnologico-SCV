<?php
// public/registrar_producto.php - Formulario para crear productos
require_once __DIR__ . '/../src/autenticacion.php';
require_login_pagina(); // exige sesión
require_once __DIR__ . '/../src/conexion.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Producto - SCVM</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/registrar_producto.css">
</head>
<body>
  <div class="card">

    <a href="inicio.php" class="btn-volver">← Volver</a>

    <h2>Registrar producto</h2>

    <form id="frm">
      <label for="codigoProducto">Código (SKU)</label>
      <input id="codigoProducto" name="codigoProducto" required placeholder="Ej: LEC-001">

      <label for="nombre">Nombre</label>
      <input id="nombre" name="nombre" required placeholder="Nombre del producto">

      <label for="descripcion">Descripción</label>
      <textarea id="descripcion" name="descripcion" rows="3" placeholder="Opcional"></textarea>

      <div class="row">
        <div>
          <label for="unidad_medida">Unidad de medida</label>
          <input id="unidad_medida" name="unidad_medida" placeholder="unidad, botella, pack...">
        </div>
        <div>
          <label for="activo">Activo</label>
          <select id="activo" name="activo">
            <option value="1" selected>Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>

      <button type="submit">Crear producto</button>
    </form>

    <div id="msg" class="msg" style="display:none"></div>

    <hr style="margin-top:18px">

    <h3 style="margin-bottom:6px">Productos recientes</h3>
    <div id="listaProductos">Cargando...</div>
  </div>

<script>
async function cargarProductos() {
  const wrap = document.getElementById('listaProductos');
  try {
    const r = await fetch('api/listar_productos.php');
    const j = await r.json();
    if (!j.ok) {
      wrap.textContent = 'Error cargando productos';
      return;
    }
    if (!j.productos || j.productos.length === 0) {
      wrap.textContent = 'No hay productos registrados.';
      return;
    }
    const html = j.productos.slice(0,20)
      .map(p => `<div style="padding:8px;border-bottom:1px solid #f0f0f0">
        <strong>${escapeHtml(p.codigoProducto)}</strong> — ${escapeHtml(p.nombre)}
      </div>`).join('');
    wrap.innerHTML = html;
  } catch (e) {
    wrap.textContent = 'Error al cargar productos';
    console.error(e);
  }
}

document.getElementById('frm').addEventListener('submit', async function(e){
  e.preventDefault();
  const msg = document.getElementById('msg');
  msg.style.display = 'none';
  msg.className = 'msg';

  const form = new FormData(this);
  try {
    const r = await fetch('api/registrar_producto.php', { method: 'POST', body: form });
    const j = await r.json();
    if (j.ok) {
      msg.textContent = 'Producto creado correctamente (ID: ' + (j.idProducto || '') + ')';
      msg.classList.add('ok');
      msg.style.display = 'block';
      this.reset();
      cargarProductos();
    } else {
      msg.textContent = 'Error: ' + (j.error || j.detalle || 'sin detalle');
      msg.classList.add('err');
      msg.style.display = 'block';
    }
  } catch (err) {
    msg.textContent = 'Error en la petición: ' + err.message;
    msg.classList.add('err');
    msg.style.display = 'block';
  }
});

function escapeHtml(s){
  return String(s||'').replace(/[&<>"'`]/g, function(m){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'}[m];
  });
}

cargarProductos();
</script>
</body>
</html>
