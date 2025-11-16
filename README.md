# Sistema de Gestion de Control de Vencimiento de Mercaderia
Aplicación web diseñada para gestionar productos, lotes y fechas de vencimiento para distribuidoras de alimentos. Permite registrar productos, cargar lotes con sus respectivas fechas de vencimiento y generar alertas automáticas cuando un artículo está próximo a vencer

## 🚀 Tecnologías Utilizadas
  - Frontend: HTML, CSS, JavaScript
  - Backend: PHP 8 o superior, XAMPP (Apache + PHP)
  - Base de Datos: MySQL Workbench

## 📋 Requisitos Previos
Antes de ejecutar el sistema, asegurate de tener instalado:
  - XAMPP 
  - MySQL Workbench  
  - PHP 8 o superior  
  - Navegador web (Chrome o Edge recomendados)

## 🔧 Instalación y Configuración
1. Instalar servidor local:
Instalar XAMPP e iniciar (start):
  - Apache (es lo unico que usaremos)

2. Copiar el proyecto a la carpeta del servidor (Xampp):
  - Pegar carpeta en ruta de instalacion. Ejemplo:
    - C:\xampp\htdocs

3. Crear la Base de Datos:
  - Abrir MySQL Workbench
  - Crear una base de datos llamada sistema_vencimiento
  - Importar el archivo sistema_vencimiento.sql

4. Configurar conexion a la base de datos:
  - Abrir la carpeta "src" del prototipo
  - Seleccionar el archivo configuracion.php
  - Modificar credenciales:
  ```php
// Configuración de conexión a la base de datos
'db_host' => '127.0.0.1',
'db_name' => 'sistema_vencimiento',
'db_user' => ' ',   // Usuario de MySQL
'db_pass' => ' ',   // Contraseña en caso de tenerla
'db_charset' => 'utf8mb4',
```

5. Ejecutar Sistema:
  - Ejecutar desde el navegador:
    - http://localhost/Sistema_Control_Vencimiento/public/login.php

## 🔐 Usuarios de Prueba
Se incluyen credenciales para iniciar sesion:
  - Administrador
    - correo: admin@demo.com
    - contraseña: Admin123!
  
  - Encargado (de Deposito)
    - correo: encargado@demo.com
    - contraseña: Encargado123!

## 👥 Rol de Usuario
  - Administrador
    - Registrar Producto
    - Registrar Lote
    - Alertas (realizar acciones)
    - Generar Alertas
    - Exportar CSV

  - Encargado (de Deposito)
    - Registrar Producto
    - Registrar Lote
    - Alertas (Solo lectura)

## 📂 Estructura del Proyecto

<pre>
  └── SISTEMA_CONTROL_VENCIMIENTOS/
    ├── public/
    │   ├── api/                           # BACKEND (endpoints PHP)
    │   │   ├── count_productos.php
    │   │   ├── generar_alertas.php
    │   │   ├── get_alertas.php
    │   │   ├── historial_alertas.php
    │   │   ├── importar_csv.php
    │   │   ├── listar_productos.php
    │   │   ├── marcar_alerta.php
    │   │   ├── obtener_proximos.php
    │   │   ├── registrar_lote.php
    │   │   ├── registrar_producto.php
    │   │   └── ...
    │   │
    │   ├── assets/                        # FRONTEND (estilos y scripts)
    │   │   ├── css/
    │   │   │   ├── alertas.css
    │   │   │   ├── inicio.css
    │   │   │   ├── login.css
    │   │   │   ├── registrar_lote.css
    │   │   │   └── registrar_producto.css
    │   │   │
    │   │   └── js/                        # Scripts a implementar!
    │   │       ├── inicio.js
    │   │       └── ...
    │   │
    │   ├── alertas.php
    │   ├── exportar_vencimientos.php
    │   ├── historial_alertas.php
    │   ├── index.php
    │   ├── inicio.php
    │   ├── login.php
    │   ├── logout.php
    │   ├── registrar_lote.php
    │   └── registrar_producto.php
    │
    ├── src/                           # BACKEND - Lógica interna
    │   ├── model/                     # BACKEND — Modelos principales del sistema (a implementar)
    │        ├── alerta_model.php
    │        ├── producto_model.php
    │        └── lote_model.php 
    │   ├── autenticacion.php      # Lógica de login y sesiones
    │   ├── conexion.php           # Conexión PDO a MySQL
    │   ├── configuracion.php      # Credenciales de conexion
    │   ├── correo.php             # Notificaciones por email (falta desarrollar)
    │   └── helper.php             # Funciones auxiliares
    │
    ├── tools/                         # BACKEND - Herramientas administrativas
    │   ├── convertir_contraseña.php   # Generador de contraseñas con password_hash
    │   ├── crear_usuario.php          # Falta terminar de desarrollar
    │   └── prueba_db.php              # Test de conexión
    │
    ├── README.md
    └── sistema_vencimiento.sql 
</pre>

## 📝 Mejoras Futuras
A continuación, se presenta un conjunto de mejoras planificadas para optimizar la arquitectura, aumentar la escalabilidad y completar módulos que están en estado inicial.
  1. Reorganización y mejora del código:
     - Reacomodar y estandarizar el código del backend y frontend.
     - Implementar un patrón más estructurado para controladores y modelos.

  2. Completar los modelos del backend (model/):
     - Para lograr una mejor logica de acceso y manipulacion de datos.

  3. Crear archivos JavaScript para cada módulo:
     - Para tener mayor orden interno.
     - Para reutilizacion del codigo.

  4. Finalizar módulo de notificaciones (correo.php):
     - Implementar envío de correos automáticos para alertas de productos próximos a vencer.
     - Integrar PHPMailer.
     - Configurar plantilla HTML para envío de notificaciones.

  5. Panel de estadísticas:
     - Dashboard con gráficos.
     - Metricas.

  6. Exportación a PDF y Excel:
     - Por ahora solo exporta en CSV.

  7. Implemetar registro de mercaderia por medio de codigo de barra o QR.

## 👨‍💻 Autor
Osinaga Diego Fernando

## 📄 Licencia
Este proyecto fue desarrollado con fines académicos.
