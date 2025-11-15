-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: sistema_vencimiento
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alerta`
--

DROP TABLE IF EXISTS `alerta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alerta` (
  `idAlerta` int NOT NULL AUTO_INCREMENT,
  `idLote` int NOT NULL,
  `fechaGenerada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo` varchar(60) NOT NULL DEFAULT 'vencimiento',
  `diasUmbral` int DEFAULT NULL,
  `diasFaltantes` int DEFAULT NULL,
  `mensaje` text,
  `estado` varchar(30) NOT NULL DEFAULT 'nueva',
  `notificada` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`idAlerta`),
  KEY `idx_alerta_lote` (`idLote`),
  KEY `idx_alerta_estado` (`estado`),
  CONSTRAINT `alerta_ibfk_1` FOREIGN KEY (`idLote`) REFERENCES `lote` (`idLote`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alerta`
--

LOCK TABLES `alerta` WRITE;
/*!40000 ALTER TABLE `alerta` DISABLE KEYS */;
INSERT INTO `alerta` VALUES (1,3,'2025-11-11 16:36:05','vencimiento',7,4,'Lote L-AGUA-20251029 (Agua mineral 500ml) vence en 4 días (umbral 7).','descartada',0),(2,2,'2025-11-11 16:36:07','vencimiento',15,12,'Lote L-QUES-20251015 (Queso cheddar 500g) vence en 12 días (umbral 15).','revisada',0),(3,1,'2025-11-11 16:36:09','vencimiento',30,27,'Lote L-CREM-20251101 (Crema de leche 1L) vence en 27 días (umbral 30).','revisada',0),(4,4,'2025-11-11 17:03:50','vencimiento',15,15,'Lote L-COCA-11112025 (Coca-) vence en 15 días (umbral 15).','revisada',0),(5,3,'2025-11-12 22:52:56','vencimiento',7,2,'Lote L-AGUA-20251029 (Agua mineral 500ml) vence en 2 días (umbral 7).','revisada',0),(6,5,'2025-11-12 22:52:58','vencimiento',30,28,'Lote L-FANTA-20251112 (Gaseosa Fanta Naranja 2 Litros) vence en 28 días (umbral 30).','revisada',0),(7,6,'2025-11-13 23:56:25','vencimiento',15,14,'Lote L-QUESO-20251114 (Queso Crema) vence en 14 días (umbral 15).','descartada',0),(8,7,'2025-11-14 21:19:05','vencimiento',7,4,'Lote L-FANTA-20251114 (Fanta Manzana 2 Litros) vence en 4 días (umbral 7).','nueva',0),(9,6,'2025-11-14 21:19:07','vencimiento',15,13,'Lote L-QUESO-20251114 (Queso Crema) vence en 13 días (umbral 15).','revisada',0),(10,8,'2025-11-14 21:38:04','vencimiento',15,14,'Lote L-YOG-20251114 (Yogurt Vainilla 1 Litro) vence en 14 días (umbral 15).','nueva',0);
/*!40000 ALTER TABLE `alerta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `idCategoria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idCategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'Lácteos','Productos derivados de la leche'),(2,'Bebidas','Refrescos, aguas, jugos');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lote`
--

DROP TABLE IF EXISTS `lote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lote` (
  `idLote` int NOT NULL AUTO_INCREMENT,
  `idProducto` int NOT NULL,
  `idProveedor` int DEFAULT NULL,
  `codigoLote` varchar(120) NOT NULL,
  `fechaIngreso` date NOT NULL,
  `fechaVencimiento` date NOT NULL,
  `cantidad` int NOT NULL DEFAULT '0',
  `estado` varchar(30) NOT NULL DEFAULT 'activo',
  `fechaCreacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idLote`),
  UNIQUE KEY `ux_producto_codigo_lote` (`idProducto`,`codigoLote`),
  KEY `idx_fecha_vencimiento` (`fechaVencimiento`),
  KEY `idProveedor` (`idProveedor`),
  CONSTRAINT `lote_ibfk_1` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `lote_ibfk_2` FOREIGN KEY (`idProveedor`) REFERENCES `proveedor` (`idProveedor`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lote`
--

LOCK TABLES `lote` WRITE;
/*!40000 ALTER TABLE `lote` DISABLE KEYS */;
INSERT INTO `lote` VALUES (1,1,1,'L-CREM-20251101','2025-10-01','2025-12-09',100,'activo','2025-11-09 22:08:13'),(2,2,2,'L-QUES-20251015','2025-09-15','2025-11-24',50,'activo','2025-11-09 22:08:13'),(3,3,1,'L-AGUA-20251029','2025-09-29','2025-11-16',200,'activo','2025-11-09 22:08:13'),(4,4,1,'L-COCA-11112025','2025-11-11','2025-11-27',18,'activo','2025-11-11 17:03:37'),(5,5,1,'L-FANTA-20251112','2025-11-12','2025-12-12',12,'activo','2025-11-12 22:52:40'),(6,6,2,'L-QUESO-20251114','2025-11-14','2025-11-29',5,'activo','2025-11-13 23:56:05'),(7,7,3,'L-FANTA-20251114','2025-11-15','2025-11-20',12,'activo','2025-11-14 21:18:54'),(8,8,2,'L-YOG-20251114','2025-11-15','2025-11-30',10,'activo','2025-11-14 21:37:16');
/*!40000 ALTER TABLE `lote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto` (
  `idProducto` int NOT NULL AUTO_INCREMENT,
  `idCategoria` int DEFAULT NULL,
  `codigoProducto` varchar(100) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text,
  `fechaCreacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idProducto`),
  UNIQUE KEY `codigoProducto` (`codigoProducto`),
  KEY `idCategoria` (`idCategoria`),
  CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`idCategoria`) REFERENCES `categoria` (`idCategoria`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
INSERT INTO `producto` VALUES (1,1,'CREM-001','Crema de leche 1L','Crema pasteurizada de 1 litro','2025-11-09 22:08:03'),(2,1,'QUES-500','Queso cheddar 500g','Queso cheddar en bloque','2025-11-09 22:08:03'),(3,2,'AGUA-500','Agua mineral 500ml','Agua mineral sin gas','2025-11-09 22:08:03'),(4,NULL,'COC-001','Coca-','','2025-11-11 01:28:48'),(5,NULL,'FANTA-001','Gaseosa Fanta Naranja 2 Litros','Bebida con gas sabor naranja','2025-11-12 22:50:53'),(6,NULL,'QUESO-002','Queso Crema','Queso crema 500gr','2025-11-13 23:54:39'),(7,NULL,'FANTA-002','Fanta Manzana 2 Litros','Gaseosa fanta manzana de 2 litros','2025-11-14 21:17:25'),(8,NULL,'YOG-001','Yogurt Vainilla 1 Litro','Yogurt bebible sabor vainilla de 1 litro','2025-11-14 21:36:16');
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedor`
--

DROP TABLE IF EXISTS `proveedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedor` (
  `idProveedor` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `cuit` varchar(30) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idProveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedor`
--

LOCK TABLES `proveedor` WRITE;
/*!40000 ALTER TABLE `proveedor` DISABLE KEYS */;
INSERT INTO `proveedor` VALUES (1,'Proveedor Central','30-00000000-0','388-111111','proveedor@empresa.com','Av. San Martín 123'),(2,'Lácteos del Norte','30-22222222-2','388-222222','contacto@lacteos.com','Ruta 9 KM 5'),(3,'Bebidas SRL','20-12131415-0','387-5222555','compras@bebidas.com','Av. Libertador 233');
/*!40000 ALTER TABLE `proveedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `idRol` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idRol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'Administrador','Acceso total al sistema y Recibe alertas de vencimiento'),(2,'Gerente','Acceso total al sistema y recibe alertas de vencimientos'),(3,'Encargado','Encargado de depósito');
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `umbral_alerta`
--

DROP TABLE IF EXISTS `umbral_alerta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `umbral_alerta` (
  `idUmbral` int NOT NULL AUTO_INCREMENT,
  `idRol` int DEFAULT NULL,
  `diasAntes` int NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fechaCreacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUmbral`),
  UNIQUE KEY `ux_rol_dias` (`idRol`,`diasAntes`),
  CONSTRAINT `umbral_alerta_ibfk_1` FOREIGN KEY (`idRol`) REFERENCES `rol` (`idRol`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `umbral_alerta`
--

LOCK TABLES `umbral_alerta` WRITE;
/*!40000 ALTER TABLE `umbral_alerta` DISABLE KEYS */;
INSERT INTO `umbral_alerta` VALUES (1,NULL,30,1,'2025-11-09 22:09:06'),(2,NULL,15,1,'2025-11-09 22:09:06'),(3,NULL,7,1,'2025-11-09 22:09:06');
/*!40000 ALTER TABLE `umbral_alerta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `idUsuario` int NOT NULL AUTO_INCREMENT,
  `idRol` int NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fechaCreacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUsuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `idRol` (`idRol`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`idRol`) REFERENCES `rol` (`idRol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,1,'Administrador Demo','admin@demo.com','$2y$10$qmuZJwE13dd9fy8xRMzJTeZXQmEeER6IGem6KjEo6p7vTsIq/n14K',1,'2025-11-09 22:07:39'),(2,3,'Encargado Demo','encargado@demo.com','$2y$10$qhY96Kv7MU5ndZKzKhT5Iui8TvS28jzMZx7v/wDBWuXQfery44jSq',1,'2025-11-13 23:25:35');
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-15 19:17:24
