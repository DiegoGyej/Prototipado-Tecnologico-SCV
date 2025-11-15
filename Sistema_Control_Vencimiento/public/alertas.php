<?php
// public/alertas.php - Panel de alertas
require_once __DIR__ . '/../src/conexion.php';
require_once __DIR__ . '/../src/autenticacion.php';
require_login_pagina('login.php');
$usuario = usuario_actual();
$nombre_usuario = htmlspecialchars($usuario['nombre'] ?? $usuario['correo'] ?? 'Usuario');
// idRol en sesión (asegurar que autenticacion.php guarda 'idRol')
$miRol = isset($usuario['idRol']) ? (int)$usuario['idRol'] : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Alertas - SCV</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="assets/css/alertas.css">
</head>
<body>
  <div class="wrap">
    <div class="card" role="main" aria-labelledby="titulo-alertas">
      <header class="top">
        <div>
          <h1 id="titulo-alertas">Panel de Alertas</h1>
          <div class="meta">Usuario: <strong><?= $nombre_usuario ?></strong> — Aquí verás las alertas por vencimiento</div>
        </div>

        <div class="controls">
          <a class="btn btn-ghost" href="inicio.php" style="color:var(--accent)">Volver</a>
          <button id="btnRefresh" class="btn btn-primary">Actualizar</button>
          <a href="logout.php" class="btn btn-danger" style="text-decoration:none">Salir</a>
        </div>
      </header>

      <div class="filters" aria-hidden="false">
        <label>
          Estado:
          <select id="fEstado" style="margin-left:8px;padding:8px;border-radius:8px;border:1px solid #eee">
            <option value="">Todos</option>
            <option value="nueva" selected>Nuevas</option>
            <option value="revisada">Revisadas</option>
            <option value="descartada">Descartadas</option>
          </select>
        </label>

        <label>
          Mostrar:
          <select id="fLimit" style="margin-left:8px;padding:8px;border-radius:8px;border:1px solid #eee">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100" selected>100</option>
          </select>
        </label>

        <div style="margin-left:auto" class="small muted">Datos vía <code>api/get_alertas.php</code></div>
      </div>

      <div class="stats" aria-hidden="true">
        <div class="stat"><div class="n" id="stat-total">--</div><div class="t">Total alertas</div></div>
        <div class="stat"><div class="n" id="stat-nuevas">--</div><div class="t">Nuevas</div></div>
        <div class="stat"><div class="n" id="stat-revisadas">--</div><div class="t">Revisadas</div></div>
      </div>

      <div id="contenido">
        <div id="loading" class="small muted" style="padding:12px">Cargando alertas…</div>

        <div class="table-wrap" id="tabla_wrap" style="display:none">
          <table id="tabla_alertas" role="table" aria-describedby="titulo-alertas">
            <thead>
              <tr>
                <th>#</th>
                <th>Lote / Producto</th>
                <th>Vencimiento</th>
                <th>Días</th>
                <th>Mensaje</th>
                <th>Estado</th>
                <th>Generada</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<script>
// rol del usuario pasado desde PHP
const MI_ROL = <?= json_encode($miRol, JSON_NUMERIC_CHECK) ?>; // 1/2 = Admin/Gerente

async function cargarAlertas() {
  const estado = document.getElementById('fEstado').value;
  const limit = document.getElementById('fLimit').value || 100;
  document.getElementById('loading').style.display = 'block';
  document.getElementById('tabla_wrap').style.display = 'none';
  document.getElementById('loading').textContent = 'Cargando alertas…';

  try {
    const url = `api/get_alertas.php?estado=${encodeURIComponent(estado)}&limit=${encodeURIComponent(limit)}`;
    const r = await fetch(url);
    const j = await r.json();

    if (!j.ok) {
      document.getElementById('loading').textContent = 'Error cargando alertas';
      console.error(j);
      return;
    }

    const tbody = document.querySelector('#tabla_alertas tbody');
    tbody.innerHTML = '';

    const alertas = j.alertas || [];
    // Estadísticas pequeñas
    document.getElementById('stat-total').textContent = alertas.length;
    document.getElementById('stat-nuevas').textContent = alertas.filter(a => (a.estado || a.estado) === 'nueva').length;
    document.getElementById('stat-revisadas').textContent = alertas.filter(a => (a.estado || a.estado) === 'revisada').length;

    if (!alertas.length) {
      document.getElementById('loading').textContent = 'No hay alertas con esos filtros';
      return;
    }

    alertas.forEach((a) => {
      const tr = document.createElement('tr');

      // id
      const tdId = document.createElement('td');
      tdId.textContent = a.id_alerta || a.idAlerta || '';
      tr.appendChild(tdId);

      // Lote / Producto
      const tdLP = document.createElement('td');
      tdLP.innerHTML = `<strong>${escapeHtml(a.codigo_lote || a.codigoLote || '')}</strong><br><span class="muted">${escapeHtml(a.producto || a.nombre || '')}${a.codigo_producto ? ' ('+escapeHtml(a.codigo_producto)+')' : ''}</span>`;
      tr.appendChild(tdLP);

      // Vencimiento
      const tdV = document.createElement('td');
      tdV.textContent = a.fecha_vencimiento || a.fechaVencimiento || '';
      tr.appendChild(tdV);

      // Días
      const tdD = document.createElement('td');
      tdD.textContent = typeof a.dias_faltantes !== 'undefined' ? a.dias_faltantes : (typeof a.diasFaltantes !== 'undefined' ? a.diasFaltantes : '');
      tr.appendChild(tdD);

      // Mensaje
      const tdM = document.createElement('td');
      tdM.textContent = a.mensaje || '';
      tr.appendChild(tdM);

      // Estado
      const tdE = document.createElement('td');
      let estadoHtml = `<span class="badge" style="background:#fff;border:1px solid #eee;color:#666">${escapeHtml(a.estado || '')}</span>`;
      if ((a.estado || '') === 'nueva') estadoHtml = `<span class="badge badge-new">Nueva</span>`;
      if ((a.estado || '') === 'revisada') estadoHtml = `<span class="badge badge-reviewed">Revisada</span>`;
      tdE.innerHTML = estadoHtml;
      tr.appendChild(tdE);

      // Generada
      const tdG = document.createElement('td');
      tdG.textContent = a.fecha_generada || a.fechaGenerada || '';
      tr.appendChild(tdG);

      // Acciones
      const tdA = document.createElement('td');
      tdA.className = 'actions';

      const estadoActual = (a.estado || a.estado) || '';
      const idAlerta = a.id_alerta || a.idAlerta || '';

      if (estadoActual === 'nueva') {
        if (MI_ROL === 1) {
          // Admin y Gerente: botones activos
          const btnRe = document.createElement('button');
          btnRe.className = 'btn btn-primary';
          btnRe.textContent = 'Marcar revisada';
          btnRe.onclick = () => marcar(idAlerta, 'revisada');
          tdA.appendChild(btnRe);

          const btnDes = document.createElement('button');
          btnDes.className = 'btn btn-danger';
          btnDes.textContent = 'Descartar';
          btnDes.style.marginLeft = '6px';
          btnDes.onclick = () => marcar(idAlerta, 'descartada');
          tdA.appendChild(btnDes);
        } else {
          // Encargado: solo lectura (UX)
          const span = document.createElement('span');
          span.className = 'small muted';
          span.textContent = 'Solo lectura';
          tdA.appendChild(span);
        }
      } else {
        if (MI_ROL === 1) {
          const btnNueva = document.createElement('button');
          btnNueva.className = 'btn btn-ghost';
          btnNueva.textContent = 'Marcar nueva';
          btnNueva.onclick = () => marcar(idAlerta, 'nueva');
          tdA.appendChild(btnNueva);
        } else {
          const span = document.createElement('span');
          span.className = 'small muted';
          span.textContent = 'Sin acciones';
          tdA.appendChild(span);
        }
      }

      tr.appendChild(tdA);
      tbody.appendChild(tr);
    });

    document.getElementById('loading').style.display = 'none';
    document.getElementById('tabla_wrap').style.display = 'block';
  } catch (err) {
    document.getElementById('loading').textContent = 'Error cargando alertas';
    console.error(err);
  }
}

async function marcar(id_alerta, estado) {
  if (!confirm('¿Confirmas el cambio de estado?')) return;
  try {
    const form = new FormData();
    // En backend soportamos ambos nombres: idAlerta o id_alerta
    form.append('idAlerta', id_alerta);
    form.append('estado', estado);

    const r = await fetch('api/marcar_alerta.php', { method: 'POST', body: form });
    const j = await r.json();
    if (j.ok) {
      cargarAlertas();
    } else {
      // Si el servidor responde 403 (rol insuficiente) o similar, lo mostramos
      alert('Error: ' + (j.error || j.detalle || 'sin detalle'));
      console.error(j);
    }
  } catch (err) {
    alert('Error en la petición');
    console.error(err);
  }
}

function escapeHtml(s){ return String(s||'').replace(/[&<>"'`]/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'}[m]; }); }

document.getElementById('btnRefresh').addEventListener('click', cargarAlertas);
document.getElementById('fEstado').addEventListener('change', cargarAlertas);
document.getElementById('fLimit').addEventListener('change', cargarAlertas);

cargarAlertas();
</script>
</body>
</html>
