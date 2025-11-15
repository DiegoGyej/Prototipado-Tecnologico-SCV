<?php
// public/inicio.php
require_once __DIR__ . '/../src/conexion.php';
require_once __DIR__ . '/../src/autenticacion.php';

// Protege la página (redirige a login si no hay sesión)
if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario = usuario_actual();
$nombre_usuario = htmlspecialchars($usuario['nombre'] ?? $usuario['correo'] ?? 'Usuario');

// Obtener idRol y nombre del rol para mostrar en UI y condicionar botones
$rol_id = (int)($usuario['idRol'] ?? 0);
$rol_nombre = 'N/A';
try {
    if ($rol_id > 0) {
        $stmt = $pdo->prepare("SELECT nombre FROM Rol WHERE idRol = :id LIMIT 1");
        $stmt->execute([':id' => $rol_id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r && !empty($r['nombre'])) $rol_nombre = htmlspecialchars($r['nombre']);
    }
} catch (Throwable $e) {
    // si falla la consulta, dejamos rol_nombre = 'N/A'
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>SCV - Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/inicio.css">
</head>
<body>
  <div class="wrap">
    <div class="card" role="main" aria-labelledby="titulo-panel">
      <div class="left">
        <div class="brand" aria-hidden="true">
          <div class="logo">SCV</div>
          <div class="brand-info">
            <h1 id="titulo-panel">Sistema Control Vencimientos</h1>
            <p>Panel principal</p>
          </div>
        </div>

        <div class="welcome" aria-live="polite">
          <div>
            <div class="who">Bienvenido, <?= $nombre_usuario ?></div>
            <div class="meta">Rol: <?= $rol_nombre ?></div>
          </div>
          <div><small class="helper">Última sesión: ahora</small></div>
        </div>

        <div class="grid" aria-hidden="true">
          <div class="stat"><div class="n" id="stat-lotes">--</div><div class="t">Lotes próximos</div></div>
          <div class="stat"><div class="n" id="stat-alertas">--</div><div class="t">Alertas nuevas</div></div>
          <div class="stat"><div class="n" id="stat-productos">--</div><div class="t">Productos</div></div>
        </div>

        <div class="footer-actions" aria-hidden="true">
          <a href="logout.php" class="logout" title="Cerrar sesión">Salir</a>
          <small class="helper">Usuario: <?= $nombre_usuario ?></small>
        </div>
      </div>

      <div class="right">
        <nav class="menu" aria-label="Menú principal">
          <!-- Siempre visibles para los 3 roles -->
          <a class="btn btn-primary btn-large" href="registrar_producto.php">Registrar producto</a>
          <a class="btn btn-primary btn-large" href="registrar_lote.php">Registrar lote</a>

          <!-- Ambos roles pueden ver alertas en modo lectura/acción según rol -->
          <a class="btn btn-secondary btn-large" href="alertas.php">Alertas</a>

          <!-- Solo ADMIN/Gerente (rol_id === 1 y 2) ven estas opciones -->
          <?php if ($rol_id === 1): ?>
            <a class="btn btn-alert btn-large" href="exportar_vencimientos.php">Exportar CSV</a>
            <a class="btn btn-secondary btn-large" href="historial_alertas.php">Histórico de alertas</a>
            <button id="btnGenerar" class="btn btn-primary btn-large" type="button">Generar alertas</button>
          <?php endif; ?>
        </nav>

        <section aria-live="polite" aria-atomic="true">
          <h3 style="margin:0 0 8px 0;color:var(--muted);font-size:14px">Actividad reciente</h3>
          <div id="activity" style="background:#fff;padding:12px;border-radius:10px;border:1px solid rgba(0,0,0,0.04);min-height:80px">
            Cargando…
          </div>
        </section>
      </div>
    </div>
  </div>

  <script>
    async function cargarResumen() {
      try {
        const resp = await fetch('api/obtener_proximos.php?days=30');
        const j = await resp.json();
        if (j.ok) {
          document.getElementById('stat-lotes').textContent = j.count || 0;
          const act = (j.lotes || []).slice(0,3).map(l => `${l.codigoLote || l.codigo_lote || l.codigo_lote || ''} — ${l.producto || l.nombre || ''} (vence en ${l.dias_faltantes ?? l.diasFaltantes ?? ''} días)`);
          document.getElementById('activity').textContent = act.length ? act.join('\n') : 'Sin actividad reciente';
        } else {
          document.getElementById('activity').textContent = 'Error al cargar lotes';
          document.getElementById('stat-lotes').textContent = 0;
        }
      } catch (err) {
        document.getElementById('activity').textContent = 'Error cargando datos';
        document.getElementById('stat-lotes').textContent = 0;
      }

      try {
        const r2 = await fetch('api/get_alertas.php?estado=nueva');
        const j2 = await r2.json();
        if (j2.ok) {
          // get_alertas.php devuelve 'alertas'
          document.getElementById('stat-alertas').textContent = (j2.count) ? j2.count : (j2.alertas ? j2.alertas.length : 0);
        } else {
          document.getElementById('stat-alertas').textContent = 0;
        }
      } catch(e) {
        document.getElementById('stat-alertas').textContent = 0;
      }

      try {
        const r3 = await fetch('api/count_productos.php');
        const j3 = await r3.json();
        if (j3.ok) {
          document.getElementById('stat-productos').textContent = j3.count || 0;
        } else {
          document.getElementById('stat-productos').textContent = '--';
        }
      } catch(e){
        document.getElementById('stat-productos').textContent = '--';
      }
    }

    // Registrar el listener solo si el botón existe (solo lo vera rol 1 y 2)
    const btnGenerar = document.getElementById('btnGenerar');
    if (btnGenerar) {
      btnGenerar.addEventListener('click', async function(){
        if (!confirm('Generar alertas según umbrales configurados?')) return;
        try {
          const r = await fetch('api/generar_alertas.php');
          const j = await r.json();
          alert('Alertas generadas: ' + (j.alertas_creadas || j.alertas_generadas || 0));
          cargarResumen();
        } catch (e) {
          alert('Error generando alertas');
        }
      });
    }

    cargarResumen();
  </script>
</body>
</html>
