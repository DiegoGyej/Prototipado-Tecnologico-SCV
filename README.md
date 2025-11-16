# Prototipado-Tecnologico-SCV

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
  - Pegar carpeta en C:\xampp\htdocs (o ruta de instalacion de Xampp, pero dentro de la carpeta htdocs)

3. Crear la Base de Datos:
  - Abrir MySQL Workbench
  - Crear una base de datos llamada sistema_vencimiento
  - Importar el archivo sistema_vencimiento.sql

4. Configurar conexion a la base de datos:
  - Abrir la carpeta "src" del prototipo
  - Seleccionar el archivo configuracion.php
  - Modificar credenciales: .env
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'sistema_vencimiento',
    'db_user' => '', // usuario de MySQL
    'db_pass' => '', // contraseña si corresponde
    'db_charset' => 'utf8mb4',

5. Ejecutar Sistema:
  - Ejecutar desde el navegador: ".env
    http://localhost/Sistema_Control_Vencimiento/public/login.php

## 🔐 Usuarios de Prueba
Se incluyen credenciales para iniciar sesion:
  - Administrador
    - correo: admin@demo.com
    - contraseña: Admin123!
  
  - Encargado (de Deposito)
    - correo: encargado@demo.com
    - contraseña: Encargado123!
