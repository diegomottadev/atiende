-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: atiende_demo
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `atiende_demo`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `atiende_demo` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `atiende_demo`;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `area` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
INSERT INTO `areas` VALUES (1,'Comercial','',1),(2,'Logistica','',1),(3,'Gerencia','',1),(4,'Facturacion','',1),(5,'Produccion','',1),(6,'Recursos humanos','',1),(7,'Finanzas','',1);
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `areas_consultas`
--

DROP TABLE IF EXISTS `areas_consultas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `areas_consultas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `area` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas_consultas`
--

LOCK TABLES `areas_consultas` WRITE;
/*!40000 ALTER TABLE `areas_consultas` DISABLE KEYS */;
INSERT INTO `areas_consultas` VALUES (1,'Comercial','',1),(2,'Logistica','',1),(3,'Gerencia','',1),(4,'Facturacion','',1),(5,'Produccion','',1),(6,'Recursos humanos','',1),(7,'Finanzas','',1),(8,'CANCELACIONES','',1);
/*!40000 ALTER TABLE `areas_consultas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articulos`
--

DROP TABLE IF EXISTS `articulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `articulos` (
  `codigo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL,
  `descripcion` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `lista1` decimal(10,2) DEFAULT NULL,
  `lista2` decimal(10,2) DEFAULT NULL,
  `lista3` decimal(10,2) DEFAULT NULL,
  `lista4` decimal(10,2) DEFAULT NULL,
  `lista5` decimal(10,2) DEFAULT NULL,
  `lista6` decimal(10,2) DEFAULT NULL,
  `lista7` decimal(10,2) DEFAULT NULL,
  `linea` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `rubro` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `subrubro` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `marca` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `kilos` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `litros` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `color` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `tamano` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `palet` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `capacidad` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `pack` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `impInt` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `codBarra` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `topecant` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `iva` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `deposito` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `stock` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `orden` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articulos`
--

LOCK TABLES `articulos` WRITE;
/*!40000 ALTER TABLE `articulos` DISABLE KEYS */;
INSERT INTO `articulos` VALUES ('1101033','Go Scott Ph Elegante 2P 4Pqx12Pz',621.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1102015','Go Hogar Sp Servilleta 6X500 Hjs',350.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Tohalla','Papel Tohalla','Papel Tohalla','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1102021','Go Scott Servilleta 24X50 Pure',1200.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Tohalla','Papel Tohalla','Papel Tohalla','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1102044','Nacional Dh Classic 6X8 48R',350.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1107103','Go Huracan Jabon Blanco 5X200Gr',256.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1107157','Go Rexona Jab Antibacterial 90Gr',330.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1107263','Io Jaboncillo Tocador 96X80Gr',250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1108043','Go Patito Deter Polvo Limon 150Gr',450.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1108083','Go Surf Polvo Fragancia Limon 150G',620.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1108090','Go Omo Polvo Limon 700G',125.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1108096','Go Ola Maximus Antigrasa Rec 850Ml',256.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1108205','Go Surf Pvo Frag Coco 60X150G',1250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1109050','Nacional Ph Natural 1X50',125.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1110004','Go Liz Sanitizador Neutro 360Ml',236.00,0.00,0.00,0.00,0.00,0.00,0.00,'Sanitizadores','Sanitizadores','Sanitizadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1110011','Go Liz Sanitizador Lavanda 360Ml',3265.00,0.00,0.00,0.00,0.00,0.00,0.00,'Sanitizadores','Sanitizadores','Sanitizadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1110018','Go Rexona Sanitizador Original 1Lt',2103.00,0.00,0.00,0.00,0.00,0.00,0.00,'Sanitizadores','Sanitizadores','Sanitizadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1110043','Go Guabira Alcohol Liquido 70% 1Lt',250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Sanitizadores','Sanitizadores','Sanitizadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1110059','Go Aguai  Alcohol Etilico 70% 1Lt',400.00,0.00,0.00,0.00,0.00,0.00,0.00,'Sanitizadores','Sanitizadores','Sanitizadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1112067','Go Ola Aromatic Aer Anttabac 300Ml',360.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1112128','Go Poett Amb Aer Bebe 360Ml',256.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1112150','Go Sapolio Amb Aer Vainilla 360Ml',2415.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('1207019','Go Esc Escobasa Escoba Plastica',250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Mopas, Escobas','Mopas, Escobas','Mopas, Escobas','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('AAP-0006','Poett Amb.  Aerosol J. Rosas 360Cc',360.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ALO-0010','Ola  Aromatic Marina 12X900Ml',621.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ALO-0015','Ola  Aromatic Flores 6X1800Ml',103.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ALO-0017','Ola  Aromatic Lavanda 6X1800Ml',1520.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ALO-0020','Ola Aromatic Pino 6X1800Ml',3265.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ALO-0021','Ola Aromatic Marina 6X1800Ml',2103.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ALO-0027','Ola  Aromatic Flores 2X5L',400.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ASO-0001','Ola  Past Tanque 48X40G',350.00,0.00,0.00,0.00,0.00,0.00,0.00,'Amientadores','Amientadores','Amientadores','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('DPM-0023','Omo Polvo Limon 6X2.1Kg',360.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('DPM-0052','Omo Polvo Limon 3X3.8Kg',940.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('DPS-0025','Surf Polvo Fragancia Limon 6X2.1Kg',250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ESC-0003','Palma Limpia Techo  1X12Unid',410.00,0.00,0.00,0.00,0.00,0.00,0.00,'Mopas, Escobas','Mopas, Escobas','Mopas, Escobas','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('ESP-0003','Todo Util Escoba Peque??a Cjax24',236.00,0.00,0.00,0.00,0.00,0.00,0.00,'Mopas, Escobas','Mopas, Escobas','Mopas, Escobas','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JAG-0001','Guaira Jabon Barra 30X260Gr',360.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JAM-0002','Omo Jab Limon 50X200G',940.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JAT-0001','Top Nal. Jabon Barra 10X5 50Unid',250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JAZ-0005','Zote Jabon Lavand 200G Bl 1X50',125.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JBI-0002','Io Jaboncillo Tocador Io 60X125Gr',2415.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JBI-0003','Io Jaboncillo Tocador 96X75Gr',1250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JBP-0013','Protex Jabon Limp 3X90Gr',410.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('JLM-0012','Mr Flash Jabon  Liq Surtido 4X5Lt',2103.00,0.00,0.00,0.00,0.00,0.00,0.00,'Jabon Y Jaboncillos','Jabon Y Jaboncillos','Jabon Y Jaboncillos','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('MPR-0002','Profit Mopa Mechudo 24 1X12',330.00,0.00,0.00,0.00,0.00,0.00,0.00,'Mopas, Escobas','Mopas, Escobas','Mopas, Escobas','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('PHH-0015','Hogar Dh Papel Hig. 2X20',3625.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('PHN-0036','Nacional Ph Blanco Rc 1-500X4',103.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('PHS-0005','Ph Scott Rindem 8X6 Verde -Xg',1520.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('PHS-0006','Ph Scott Rindem 4X12 Verde -Xg',3265.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('PHS-0009','Ph Scott Rindem 8X6 Naranja -G',2103.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Higienico','Papel Higienico','Papel Higienico','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('PKS-0001','Kleenex Pa??uelo Super 3P 36X80',250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Tohalla','Papel Tohalla','Papel Tohalla','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('PKS-0002','Kleenex Pa??uelo Cubo 32X60',400.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Tohalla','Papel Tohalla','Papel Tohalla','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('SEH-0002','Hogar Sp Servilleta 6X500 Hjs',940.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Tohalla','Papel Tohalla','Papel Tohalla','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('SES-0005','Servilleta Scott 12X100 Unidad',330.00,0.00,0.00,0.00,0.00,0.00,0.00,'Papel Tohalla','Papel Tohalla','Papel Tohalla','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('SUB-0008','Borita Doypack Suav. Bebe 12X900',2415.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('SUB-0012','Borita Suav. Tradicional 3X4000Cc',1250.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('SUO-0005','Ola Suavecito Azul Original 6X1.7L',410.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('TGO-0001','Max Hogar Rodo Doble Goma 12X42',236.00,0.00,0.00,0.00,0.00,0.00,0.00,'Mopas, Escobas','Mopas, Escobas','Mopas, Escobas','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('TGO-0003','Goma Negra C/Palo Dx12',621.00,0.00,0.00,0.00,0.00,0.00,0.00,'Mopas, Escobas','Mopas, Escobas','Mopas, Escobas','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('TGO-0004','Haragan Goma Negra Argentina',103.00,0.00,0.00,0.00,0.00,0.00,0.00,'Mopas, Escobas','Mopas, Escobas','Mopas, Escobas','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('VAF-0005','Mr Flash Antigrasa C/Gat 12X850',1200.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('VAO-0003','Ola Lava Vajilla Limon 2X5L',360.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0'),('VAO-0011','Ola Lava Vajilla Limon 6X2L',800.00,0.00,0.00,0.00,0.00,0.00,0.00,'Detergentes','Detergentes','Detergentes','Varios','0','0','0','0','0','0','0','0','0','0','0','1','0','0');
/*!40000 ALTER TABLE `articulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bot_config`
--

DROP TABLE IF EXISTS `bot_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bot_config` (
  `id` tinyint(1) NOT NULL DEFAULT '1',
  `menu_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_single_row` CHECK ((`id` = 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bot_config`
--

LOCK TABLES `bot_config` WRITE;
/*!40000 ALTER TABLE `bot_config` DISABLE KEYS */;
INSERT INTO `bot_config` VALUES (1,'[{\"menuId\":\"0\",\"menuIdB\":\"100\",\"consigna\":\"\",\"finaliza\":\"false\",\"menuItem\":[]},{\"menuId\":\"100\",\"consigna\":\"Bienvenido a *<empresa>*, *<nombre>*!!\\n\\nPara comenzar, elige una opción escribiendo solo el número:\\n\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"1\",\"opcion\":\"Ya soy Cliente\",\"menuId\":\"101\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"2\",\"opcion\":\"Quiero ser Cliente\",\"menuId\":\"102\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"3\",\"opcion\":\"No recuerdo mi número de cliente\",\"menuId\":\"103\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"4\",\"opcion\":\"Salir\",\"menuId\":\"2.2\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"101\",\"consigna\":\"Por favor ingresa tu *código de cliente*:\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"200\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"registraNumero\"}]},{\"menuId\":\"103\",\"consigna\":\"Sin problema 🙂\\n\\nEscribime tu *nombre completo* y tu *dirección* así un agente identifica tu cuenta.\\n\\nRecordá que no puedo escuchar audios, ni ver fotos y videos.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"104\",\"guardar\":\"true\",\"area\":\"\",\"motivo\":\"Cliente no recuerda su código\"}]},{\"menuId\":\"104\",\"consigna\":\"¡Gracias *<nombre>*! Registramos tu solicitud.\\n\\nUn agente se va a comunicar a la brevedad para ayudarte a identificar tu cuenta.\",\"finaliza\":\"true\",\"menuItem\":[]},{\"menuId\":\"102\",\"consigna\":\"¿Cuál es tu *nombre completo*?\\n\\nRecordá que no puedo escuchar audios, ni ver fotos y videos.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"8.1\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"8.1\",\"consigna\":\"Ahora escribí tu *dirección completa*.\\n\\nRecordá que no puedo escuchar audios, ni ver fotos y videos.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"802\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"802\",\"consigna\":\"Por último, compartí tu ubicación desde WhatsApp.\\n\\n📎 Adjuntar › Ubicación › Enviar mi ubicación actual\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"8.2\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"8.2\",\"consigna\":\"¡Ya tenemos tus datos! Enviá *SI* para confirmar el alta.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"3\",\"guardar\":\"false\",\"accion\":\"registraClientes\"}]},{\"menuId\":\"200\",\"consigna\":\"<saludo> *<nombre>*! ¿En qué podemos ayudarte?\\n\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"1\",\"opcion\":\"Hacer un pedido\",\"menuId\":\"350\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"2\",\"opcion\":\"Hacer un reclamo\",\"menuId\":\"1\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"3\",\"opcion\":\"Hacer una consulta\",\"menuId\":\"300\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"4\",\"opcion\":\"Consultar reclamo\",\"menuId\":\"400\",\"guardar\":\"false\",\"area\":\"\"},{\"opcionId\":\"5\",\"opcion\":\"Salir\",\"menuId\":\"2.2\",\"guardar\":\"false\",\"area\":\"\"}]},{\"menuId\":\"1\",\"consigna\":\"Tu reclamo es sobre:\\n\",\"finaliza\":\"false\",\"menuItem\":[]},{\"menuId\":\"5\",\"consigna\":\"Escribí el *detalle* de tu reclamo:\\n\\n*Recordá que no puedo escuchar audios, ni ver fotos y videos.*\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"3\",\"guardar\":\"true\",\"area\":\"\"}]},{\"menuId\":\"350\",\"consigna\":\"Te enviaremos el link para tu pedido. <linkPedidos>\",\"finaliza\":\"true\",\"palabraClave\":[\"pedido\",\"comprar\"],\"menuItem\":[]},{\"menuId\":\"400\",\"consigna\":\"Ingresa el número de reclamo a consultar:\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"2.2\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"consultarReclamo\"}]},{\"menuId\":\"3\",\"consigna\":\"*<nombre>* Nos estamos ocupando de inmediato.\\n¡Hasta pronto!\",\"finaliza\":\"true\",\"menuItem\":[]},{\"menuId\":\"2.2\",\"consigna\":\"Gracias *<nombre>*. ¡Hasta pronto!\",\"finaliza\":\"true\",\"menuItem\":[]},{\"menuId\":\"4\",\"consigna\":\"*Upps!!* Ingresaste una opción no válida, intenta nuevamente.\",\"finaliza\":\"false\",\"menuItem\":[]},{\"menuId\":\"300\",\"consigna\":\"¿Sobre qué necesitás consultar?\\n\",\"finaliza\":\"false\",\"menuItem\":[]},{\"menuId\":\"15\",\"consigna\":\"Escribí el *detalle* de tu consulta:\\n\\nRecordá que no puedo escuchar audios, ni ver fotos y videos.\",\"finaliza\":\"false\",\"menuItem\":[{\"opcionId\":\"\",\"opcion\":\"\",\"menuId\":\"2.2\",\"guardar\":\"false\",\"area\":\"\",\"accion\":\"registrarConsulta\"}]}]','1122973195','2026-06-04 14:41:23');
/*!40000 ALTER TABLE `bot_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_cart_item`
--

DROP TABLE IF EXISTS `cart_cart_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_cart_item` (
  `cart_id` bigint unsigned NOT NULL,
  `cart_item_id` bigint unsigned NOT NULL,
  KEY `cart_cart_item_cart_id_index` (`cart_id`) USING BTREE,
  KEY `cart_cart_item_cart_item_id_index` (`cart_item_id`) USING BTREE,
  CONSTRAINT `cart_cart_item_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `cart_cart_item_cart_item_id_foreign` FOREIGN KEY (`cart_item_id`) REFERENCES `cart_items` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_cart_item`
--

LOCK TABLES `cart_cart_item` WRITE;
/*!40000 ALTER TABLE `cart_cart_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_cart_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int unsigned NOT NULL,
  `brand` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kilos` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `liters` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `cart_items_cart_id_index` (`cart_id`) USING BTREE,
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `link_delivery_id` int NOT NULL,
  `auth_user` int unsigned DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `coupon_id` int unsigned DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `codigo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL,
  `razonSocial` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `direccion` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `zona` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `vendedor` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL,
  `supervisor` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `telefono` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `lista` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `orden` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `ramo` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `subramo` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `localidad` varchar(300) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `provincia` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `pais` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `deposito` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL,
  `latitud` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL,
  `longitud` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL,
  `id` bigint NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES ('0002','DIEGO M:','av.ose artigfas214','','1','','5493764278402','1','','','','','','','1','-27.038595199585','-55.220291137695',1),('0001','DIEGO MOTTA','AV.Jose Artigas 234','','1','','5493764278402','1','','','','','','','1','-27.038595199585','-55.220291137695',2);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consultas`
--

DROP TABLE IF EXISTS `consultas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultas` (
  `consultaId` int NOT NULL AUTO_INCREMENT,
  `empresa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `clienteId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nick` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  `resolucion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `notificado` int NOT NULL DEFAULT '0',
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`consultaId`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consultas`
--

LOCK TABLES `consultas` WRITE;
/*!40000 ALTER TABLE `consultas` DISABLE KEYS */;
/*!40000 ALTER TABLE `consultas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contactos`
--

DROP TABLE IF EXISTS `contactos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contactos` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresaId` int DEFAULT NULL,
  `nombre` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `menu` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esperaRespuesta` int NOT NULL DEFAULT '0',
  `anterior` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `mensaje` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fechaHora` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contactos`
--

LOCK TABLES `contactos` WRITE;
/*!40000 ALTER TABLE `contactos` DISABLE KEYS */;
INSERT INTO `contactos` VALUES ('5493764278402',NULL,'Diego Motta🧑🏻‍💻⚡️','5493764278402','0',0,'{\"opcionId\":\"1\",\"opcion\":\"Hacer un pedido\",\"menuId\":\"350\",\"guardar\":\"false\",\"area\":\"\"}',NULL,'2026-06-04 18:40:49');
/*!40000 ALTER TABLE `contactos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contactosb2c`
--

DROP TABLE IF EXISTS `contactosb2c`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contactosb2c` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `razonSocial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `localidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ramo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zona` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cuit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4553 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contactosb2c`
--

LOCK TABLES `contactosb2c` WRITE;
/*!40000 ALTER TABLE `contactosb2c` DISABLE KEYS */;
/*!40000 ALTER TABLE `contactosb2c` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emprendedores`
--

DROP TABLE IF EXISTS `emprendedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emprendedores` (
  `id` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rubro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emprendedores`
--

LOCK TABLES `emprendedores` WRITE;
/*!40000 ALTER TABLE `emprendedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `emprendedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fidelizar`
--

DROP TABLE IF EXISTS `fidelizar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fidelizar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `clienteid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pedidoid` int NOT NULL,
  `tipo` int NOT NULL,
  `mensaje` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fidelizar`
--

LOCK TABLES `fidelizar` WRITE;
/*!40000 ALTER TABLE `fidelizar` DISABLE KEYS */;
/*!40000 ALTER TABLE `fidelizar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `link_pedidos`
--

DROP TABLE IF EXISTS `link_pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `link_pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clienteId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` datetime NOT NULL,
  `estado` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2025 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `link_pedidos`
--

LOCK TABLES `link_pedidos` WRITE;
/*!40000 ALTER TABLE `link_pedidos` DISABLE KEYS */;
INSERT INTO `link_pedidos` VALUES (2019,'0002','5493764278402','demo','2026-06-02 23:03:58',1),(2020,'0002','5493764278402','demo','2026-06-02 23:06:34',1),(2021,'0002','5493764278402','demo','2026-06-04 15:00:07',1),(2022,'0002','5493764278402','demo','2026-06-04 16:06:14',1),(2023,'0002','5493764278402','demo','2026-06-04 16:08:18',1),(2024,'0002','5493764278402','demo','2026-06-04 18:40:49',0);
/*!40000 ALTER TABLE `link_pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajeb2c_contactob2c`
--

DROP TABLE IF EXISTS `mensajeb2c_contactob2c`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajeb2c_contactob2c` (
  `mensajeb2c_id` bigint DEFAULT NULL,
  `contactob2c_id` bigint DEFAULT NULL,
  KEY `fk_mensajeb2c` (`mensajeb2c_id`) USING BTREE,
  KEY `fk_contactob2c` (`contactob2c_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajeb2c_contactob2c`
--

LOCK TABLES `mensajeb2c_contactob2c` WRITE;
/*!40000 ALTER TABLE `mensajeb2c_contactob2c` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensajeb2c_contactob2c` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes`
--

DROP TABLE IF EXISTS `mensajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` datetime NOT NULL,
  `cantidad` int NOT NULL,
  `destino` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes`
--

LOCK TABLES `mensajes` WRITE;
/*!40000 ALTER TABLE `mensajes` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensajes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajesb2c`
--

DROP TABLE IF EXISTS `mensajesb2c`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensajesb2c` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mensaje` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `fecha` datetime DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajesb2c`
--

LOCK TABLES `mensajesb2c` WRITE;
/*!40000 ALTER TABLE `mensajesb2c` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensajesb2c` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menuitem`
--

DROP TABLE IF EXISTS `menuitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menuitem` (
  `id` int NOT NULL AUTO_INCREMENT,
  `opcionId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opcion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `menuId` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guardar` tinyint(1) NOT NULL,
  `area` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menuitem`
--

LOCK TABLES `menuitem` WRITE;
/*!40000 ALTER TABLE `menuitem` DISABLE KEYS */;
/*!40000 ALTER TABLE `menuitem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motivo_consultas`
--

DROP TABLE IF EXISTS `motivo_consultas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motivo_consultas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `opcionId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opcion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `menuId` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guardar` tinyint(1) NOT NULL,
  `area` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motivo_consultas`
--

LOCK TABLES `motivo_consultas` WRITE;
/*!40000 ALTER TABLE `motivo_consultas` DISABLE KEYS */;
INSERT INTO `motivo_consultas` VALUES (1,'1','Realizan envios a domicilio?','15',0,'1'),(2,'2','Tienen una lista de productos?','15',0,'1'),(3,'3','Que formas de pagos aceptan?','15',0,'1');
/*!40000 ALTER TABLE `motivo_consultas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motivo_reclamos`
--

DROP TABLE IF EXISTS `motivo_reclamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motivo_reclamos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `opcionId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opcion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `menuId` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guardar` tinyint(1) NOT NULL,
  `area` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motivo_reclamos`
--

LOCK TABLES `motivo_reclamos` WRITE;
/*!40000 ALTER TABLE `motivo_reclamos` DISABLE KEYS */;
INSERT INTO `motivo_reclamos` VALUES (1,'1','Tu pedido aun no ha llegado?','5',0,'2'),(2,'2','Te llego un producto equivocado?','5',0,'4'),(3,'3','Tu pedido llego con otro importe?','5',0,'1'),(4,'4','Tienes alguna sugerencia?','5',0,'3');
/*!40000 ALTER TABLE `motivo_reclamos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `msj_consultas`
--

DROP TABLE IF EXISTS `msj_consultas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `msj_consultas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_consulta` int NOT NULL,
  `tipo` int NOT NULL,
  `fecha` datetime NOT NULL,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `respondido` int NOT NULL,
  `estado` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal` int DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `msj_consultas`
--

LOCK TABLES `msj_consultas` WRITE;
/*!40000 ALTER TABLE `msj_consultas` DISABLE KEYS */;
/*!40000 ALTER TABLE `msj_consultas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `msj_reclamos`
--

DROP TABLE IF EXISTS `msj_reclamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `msj_reclamos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_reclamo` int NOT NULL,
  `tipo` int NOT NULL,
  `fecha` datetime NOT NULL,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `respondido` int NOT NULL,
  `estado` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal` int DEFAULT '-1',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `msj_reclamos`
--

LOCK TABLES `msj_reclamos` WRITE;
/*!40000 ALTER TABLE `msj_reclamos` DISABLE KEYS */;
/*!40000 ALTER TABLE `msj_reclamos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_access_tokens`
--

DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `oauth_access_tokens_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_access_tokens`
--

LOCK TABLES `oauth_access_tokens` WRITE;
/*!40000 ALTER TABLE `oauth_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_auth_codes`
--

DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `scopes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `oauth_auth_codes_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_auth_codes`
--

LOCK TABLES `oauth_auth_codes` WRITE;
/*!40000 ALTER TABLE `oauth_auth_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_auth_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_clients`
--

DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `oauth_clients_user_id_index` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_clients`
--

LOCK TABLES `oauth_clients` WRITE;
/*!40000 ALTER TABLE `oauth_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_personal_access_clients`
--

DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_personal_access_clients`
--

LOCK TABLES `oauth_personal_access_clients` WRITE;
/*!40000 ALTER TABLE `oauth_personal_access_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_personal_access_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_refresh_tokens`
--

DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_refresh_tokens`
--

LOCK TABLES `oauth_refresh_tokens` WRITE;
/*!40000 ALTER TABLE `oauth_refresh_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_refresh_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clienteId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `producto` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pedidoid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pagado` tinyint(1) DEFAULT '0',
  `telefono` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dato5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dato6` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dato7` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dato8` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dato9` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `subtotal` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` int DEFAULT NULL,
  `vendedorId` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repartidor_id` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_asignacion` datetime DEFAULT NULL,
  `fecha_notificacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=274 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (258,'0002','2026-06-02 23:04:19','ALO-0027','ola  aromatic flores 2x5l','1','400','0','2019',0,'5493764278402',NULL,NULL,NULL,NULL,'','400.00',2,'',NULL,NULL,NULL),(259,'0002','2026-06-02 23:04:19','1112128','go poett amb aer bebe 360ml','1','256','0','2019',0,'5493764278402',NULL,NULL,NULL,NULL,'','256.00',2,'',NULL,NULL,NULL),(260,'0002','2026-06-02 23:04:19','1112067','go ola aromatic aer anttabac 300ml','1','360','0','2019',0,'5493764278402',NULL,NULL,NULL,NULL,'','360.00',2,'',NULL,NULL,NULL),(261,'0002','2026-06-02 23:06:58','1112128','go poett amb aer bebe 360ml','1','256','0','2020',0,'5493764278402',NULL,NULL,NULL,NULL,'','256.00',0,'',NULL,NULL,NULL),(262,'0002','2026-06-02 23:06:58','1112067','go ola aromatic aer anttabac 300ml','1','360','0','2020',0,'5493764278402',NULL,NULL,NULL,NULL,'','360.00',0,'',NULL,NULL,NULL),(263,'0002','2026-06-02 23:06:58','AAP-0006','poett amb.  aerosol j. rosas 360cc','1','360','0','2020',0,'5493764278402',NULL,NULL,NULL,NULL,'','360.00',0,'',NULL,NULL,NULL),(264,'0002','2026-06-02 23:06:58','ALO-0027','ola  aromatic flores 2x5l','1','400','0','2020',0,'5493764278402',NULL,NULL,NULL,NULL,'','400.00',0,'',NULL,NULL,NULL),(265,'0002','2026-06-04 15:00:34','ALO-0027','ola  aromatic flores 2x5l','1','400','0','2021',0,'5493764278402',NULL,NULL,NULL,NULL,'','400.00',0,'',NULL,NULL,NULL),(266,'0002','2026-06-04 15:00:34','1112128','go poett amb aer bebe 360ml','1','256','0','2021',0,'5493764278402',NULL,NULL,NULL,NULL,'','256.00',0,'',NULL,NULL,NULL),(267,'0002','2026-06-04 15:00:34','1112067','go ola aromatic aer anttabac 300ml','1','360','0','2021',0,'5493764278402',NULL,NULL,NULL,NULL,'','360.00',0,'',NULL,NULL,NULL),(268,'0002','2026-06-04 16:06:48','ALO-0027','ola  aromatic flores 2x5l','1','400','0','2022',0,'5493764278402',NULL,NULL,NULL,NULL,'','400.00',0,'',NULL,NULL,NULL),(269,'0002','2026-06-04 16:06:48','1112128','go poett amb aer bebe 360ml','1','256','0','2022',0,'5493764278402',NULL,NULL,NULL,NULL,'','256.00',0,'',NULL,NULL,NULL),(270,'0002','2026-06-04 16:06:48','1112067','go ola aromatic aer anttabac 300ml','1','360','0','2022',0,'5493764278402',NULL,NULL,NULL,NULL,'','360.00',0,'',NULL,NULL,NULL),(271,'0002','2026-06-04 17:03:51','ALO-0027','ola  aromatic flores 2x5l','1','400','0','2023',0,'5493764278402',NULL,NULL,NULL,NULL,'','400.00',0,'',NULL,NULL,NULL),(272,'0002','2026-06-04 17:03:51','1112128','go poett amb aer bebe 360ml','1','256','0','2023',0,'5493764278402',NULL,NULL,NULL,NULL,'','256.00',0,'',NULL,NULL,NULL),(273,'0002','2026-06-04 17:03:51','1112067','go ola aromatic aer anttabac 300ml','1','360','0','2023',0,'5493764278402',NULL,NULL,NULL,NULL,'','360.00',0,'',NULL,NULL,NULL);
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permiso`
--

DROP TABLE IF EXISTS `permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permiso` (
  `idpermiso` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`idpermiso`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permiso`
--

LOCK TABLES `permiso` WRITE;
/*!40000 ALTER TABLE `permiso` DISABLE KEYS */;
INSERT INTO `permiso` VALUES (1,'Panel de control'),(2,'Reclamos'),(3,'Consultas'),(4,'Ventas'),(5,'Seguridad'),(6,'Mensajes masivos'),(8,'Base de datos'),(9,'Vendedores'),(10,'Repartos'),(11,'Configuración');
/*!40000 ALTER TABLE `permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`) USING BTREE,
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reclamos`
--

DROP TABLE IF EXISTS `reclamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reclamos` (
  `reclamoId` int NOT NULL AUTO_INCREMENT,
  `empresa` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `clienteId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nick` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_resolucion` datetime DEFAULT NULL,
  `resolucion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `notificado` int NOT NULL DEFAULT '0',
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`reclamoId`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reclamos`
--

LOCK TABLES `reclamos` WRITE;
/*!40000 ALTER TABLE `reclamos` DISABLE KEYS */;
INSERT INTO `reclamos` VALUES (30,'demo','2026-06-04 15:25:06','0002','5493764278402','Diego Motta🧑🏻‍💻⚡️','Te llego un producto equivocado?','4','dsdssadsa',NULL,'','Pendiente',0,0);
/*!40000 ALTER TABLE `reclamos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repartidores`
--

DROP TABLE IF EXISTS `repartidores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repartidores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repartidores`
--

LOCK TABLES `repartidores` WRITE;
/*!40000 ALTER TABLE `repartidores` DISABLE KEYS */;
/*!40000 ALTER TABLE `repartidores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rubros_emprendedores`
--

DROP TABLE IF EXISTS `rubros_emprendedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rubros_emprendedores` (
  `id` int NOT NULL,
  `opcionId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rubro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `menuId` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`,`opcionId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rubros_emprendedores`
--

LOCK TABLES `rubros_emprendedores` WRITE;
/*!40000 ALTER TABLE `rubros_emprendedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `rubros_emprendedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes`
--

DROP TABLE IF EXISTS `solicitudes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `localidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint unsigned NOT NULL DEFAULT '0',
  `fecha` timestamp(6) NULL DEFAULT NULL,
  `cuit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitud` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `longitud` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes`
--

LOCK TABLES `solicitudes` WRITE;
/*!40000 ALTER TABLE `solicitudes` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telefonos`
--

DROP TABLE IF EXISTS `telefonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telefonos` (
  `clienteId` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` int NOT NULL,
  PRIMARY KEY (`telefono`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telefonos`
--

LOCK TABLES `telefonos` WRITE;
/*!40000 ALTER TABLE `telefonos` DISABLE KEYS */;
INSERT INTO `telefonos` VALUES ('0002','5493764278402',1);
/*!40000 ALTER TABLE `telefonos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `users_email_unique` (`email`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `idusuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_documento` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `num_documento` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cargo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `login` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clave` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `imagen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `condicion` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`idusuario`) USING BTREE,
  UNIQUE KEY `login_UNIQUE` (`login`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (6,'Diego Motta','DNI','0',NULL,NULL,NULL,'Administrador','demo@test.com','$2y$10$EvuuhVCIWK9CAwwmK6mjCOwg/ygBuodoohupWMvcef1QwOFg4J0vu','',1);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario_permiso`
--

DROP TABLE IF EXISTS `usuario_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_permiso` (
  `idusuario_permiso` int NOT NULL AUTO_INCREMENT,
  `idusuario` int NOT NULL,
  `idpermiso` int NOT NULL,
  PRIMARY KEY (`idusuario_permiso`) USING BTREE,
  KEY `fk_u_permiso_usuario_idx` (`idusuario`) USING BTREE,
  KEY `fk_usuario_permiso_idx` (`idpermiso`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=311 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario_permiso`
--

LOCK TABLES `usuario_permiso` WRITE;
/*!40000 ALTER TABLE `usuario_permiso` DISABLE KEYS */;
INSERT INTO `usuario_permiso` VALUES (300,6,1),(301,6,2),(302,6,3),(303,6,4),(304,6,5),(305,6,6),(307,6,8),(308,6,9),(309,6,10),(310,6,11);
/*!40000 ALTER TABLE `usuario_permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendedores`
--

DROP TABLE IF EXISTS `vendedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendedores` (
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `atencion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`codigo`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendedores`
--

LOCK TABLES `vendedores` WRITE;
/*!40000 ALTER TABLE `vendedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wa_mensajes_procesados`
--

DROP TABLE IF EXISTS `wa_mensajes_procesados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wa_mensajes_procesados` (
  `wam_id` varchar(128) NOT NULL,
  `procesado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wa_mensajes_procesados`
--

LOCK TABLES `wa_mensajes_procesados` WRITE;
/*!40000 ALTER TABLE `wa_mensajes_procesados` DISABLE KEYS */;
INSERT INTO `wa_mensajes_procesados` VALUES ('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFDNBMTFGQjJFNDY5NzIwQUVBMjU4AA==','2026-06-04 15:02:16'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA0MEM2Q0YwNUYyMDg5OENDQkIA','2026-06-02 23:03:56'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA0N0Q4QzhBRURCRTYyQkQxQkYA','2026-06-04 15:00:06'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA0NTVBN0UxRDYwOTlGMEE1NDAA','2026-06-04 16:08:16'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA0ODc3NjA5MDJBRDAwRTQyREUA','2026-06-04 15:01:06'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA1NTI2MURBN0M3ODlFRThBQjgA','2026-06-02 23:06:33'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA1RUQyRjg4OThDRDE2MjhCREMA','2026-06-02 23:00:05'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA2RDYwMDYzNDA5RUQ3MjIxNzEA','2026-06-04 15:24:58'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA3MjkxODE3OUVDRUQ4MjExMEQA','2026-06-04 14:59:50'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA3RDUzN0ZFMzczRDk4QTA0NTYA','2026-06-02 22:50:40'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA4NDZCQzAyOENFMzQxNzMzQjQA','2026-06-02 22:50:53'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA4NjBFN0E2RUZCRjdBQTYyN0IA','2026-06-04 14:59:57'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA5NTk5ODE0M0I5RDI0QzhFRjcA','2026-06-02 22:59:32'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA5QkExOENEREZFMDE0QjYyMTAA','2026-06-02 23:02:27'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA5QTA0MDMyQjc2OTMxN0RCQzIA','2026-06-04 18:36:11'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjA5RkU0NjBEQjZEM0E1MENBMjYA','2026-06-04 15:01:32'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjAwMEQ3QzEwQzI0QzJCOTgzRjIA','2026-06-04 16:06:06'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjAwNjhDN0UzNkVGN0YzMTJFRjcA','2026-06-04 15:25:06'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjAxNTAwREE4MUJEMDIwNTBDNjgA','2026-06-04 18:40:48'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjAxNTlEMkZERTM2NEJDRTA2RDQA','2026-06-04 16:08:11'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjAxRjk4NEY1MUQ3N0QzODFFRTkA','2026-06-04 14:28:48'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjAyMTlDRkMyMTJFOTc3Qjg3NkIA','2026-06-02 23:03:01'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjAyOUY1MjY1Q0VFMjRENUI3MzIA','2026-06-04 15:24:52'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBCMDcxOUQ2MUE5MDA2RkZEMUIA','2026-06-02 22:59:50'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBCQzJEMzJERDk2Qzg2OTlFQzMA','2026-06-04 16:06:27'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBDOTUzNEQzQkQ0RUMwNkY4OUIA','2026-06-02 23:06:21'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBDRTYxRjNFQzQ1Qzk5RTA5MTcA','2026-06-04 15:01:14'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBEMzY2MUQ5Q0ZGNTFCN0M4RjYA','2026-06-04 15:01:19'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBFRDAxQTM2NjU3RUUyMjA1NzAA','2026-06-03 20:11:50'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBFRDYyQkYwOTlBRjlGOEI1NEIA','2026-06-04 18:40:36'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBFRkMzRjgxRjAzQzlFM0NGQkIA','2026-06-02 23:02:48'),('wamid.HBgNNTQ5Mzc2NDI3ODQwMhUCABIYFjNFQjBGNjQyQTMwN0I1RUE1REQzNUEA','2026-06-04 16:06:13');
/*!40000 ALTER TABLE `wa_mensajes_procesados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'atiende_demo'
--

--
-- Dumping routines for database 'atiende_demo'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-04 18:42:27
