-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 02-08-2025 a las 17:44:06
-- Versión del servidor: 11.3.2-MariaDB
-- Versión de PHP: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `base_incidencias`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contador_diario`
--

DROP TABLE IF EXISTS `contador_diario`;
CREATE TABLE IF NOT EXISTS `contador_diario` (
  `fecha` date NOT NULL,
  `contador` int(11) NOT NULL,
  PRIMARY KEY (`fecha`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `contador_diario`
--

INSERT INTO `contador_diario` (`fecha`, `contador`) VALUES
('2025-07-28', 6),
('2025-07-29', 4),
('2025-07-30', 7),
('2025-07-31', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `divisiones`
--

DROP TABLE IF EXISTS `divisiones`;
CREATE TABLE IF NOT EXISTS `divisiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `divisiones`
--

INSERT INTO `divisiones` (`id`, `nombre`) VALUES
(1, 'División de Ingenierías 1'),
(2, 'División de Ingenierías 2'),
(3, 'División de Ingenierías 3'),
(4, 'Subdirección Académica'),
(5, 'Director Académico'),
(6, 'Director General');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

DROP TABLE IF EXISTS `incidencias`;
CREATE TABLE IF NOT EXISTS `incidencias` (
  `id` char(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_solicitada` varchar(50) NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `tipo_incidencia` varchar(50) NOT NULL,
  `tipo_permiso` enum('PERSONAL','INSTITUCIONAL') NOT NULL,
  `motivo` text DEFAULT NULL,
  `estado` varchar(10) NOT NULL DEFAULT 'NO',
  `firma_jefe` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incidencias`
--

INSERT INTO `incidencias` (`id`, `usuario_id`, `division_id`, `fecha_creacion`, `fecha_solicitada`, `hora_inicio`, `hora_fin`, `tipo_incidencia`, `tipo_permiso`, `motivo`, `estado`, `firma_jefe`) VALUES
('20250728001', 3, 1, '2025-07-28 11:35:36', '2025-07-31', NULL, NULL, 'PERMISO', 'PERSONAL', 'CALADO', 'PENDIENTE', 0),
('20250728002', 3, 1, '2025-07-28 11:43:28', '2025-08-09', NULL, NULL, 'CAMBIO DE HORARIO', 'PERSONAL', 'ME EQUIVOQUÉ DE MOTIVO', 'PENDIENTE', 0),
('20250728005', 2, 1, '2025-07-28 17:24:11', '2025-07-31', NULL, NULL, 'OMISIÓN DE CHECADA', 'INSTITUCIONAL', 'ANDABA EN HONORES', 'ACEPTADO', 3),
('20250729001', 3, 1, '2025-07-29 20:33:01', '2025-07-31', NULL, NULL, 'CAMBIO DE HORARIO', 'PERSONAL', 'PRUEBA DE ALERTAS CON EL JEFE', 'ACEPTADO', 4),
('20250729004', 2, 1, '2025-07-29 20:54:13', '2025-07-30', NULL, NULL, 'PASE DE SALIDA', 'INSTITUCIONAL', 'YA ME QUIERO SALIR', 'PENDIENTE', 3),
('20250730001', 2, 1, '2025-07-30 12:50:20', '2025-07-31', NULL, NULL, 'PERMISO', 'INSTITUCIONAL', 'PRUEBA DE MODIFICACIONES 2', 'ACEPTADO', 6),
('20250730002', 1, 1, '2025-07-30 12:51:32', '2025-08-01', NULL, NULL, 'JUSTIFICANTE', 'INSTITUCIONAL', 'OTRA PRUEBA DE DIVISIONES', 'ACEPTADO', 3),
('20250730003', 4, 1, '2025-07-30 16:52:16', '2025-07-31', NULL, NULL, 'OMISIÓN DE CHECADA', 'PERSONAL', 'PRUEBA DE SUBDIRECCION', 'PENDIENTE', 0),
('20250730004', 1, 1, '2025-07-30 17:14:21', '2025-07-31,2025-08-01', NULL, NULL, 'PERMISO', 'INSTITUCIONAL', 'PRUEBA DE FECHAS', 'ACEPTADO', 3),
('20250730006', 1, 1, '2025-07-30 20:48:41', '2025-07-30,2025-07-31,2025-08-01', NULL, NULL, 'PERMISO', 'PERSONAL', 'NUEVA PRUEBA DE FECHAS', 'ACEPTADO', 3),
('20250730007', 8, 1, '2025-07-30 21:30:30', '2025-07-30,2025-07-31,2025-08-01', NULL, NULL, 'PERMISO', 'PERSONAL', 'PRUEBA DE DIVISION 3', 'ACEPTADO', 7),
('20250731001', 3, 1, '2025-07-31 12:06:07', '2025-07-17,2025-07-18', '07:05:00', '15:06:00', 'PERMISO', 'PERSONAL', 'VOY AL MEDICO', 'PENDIENTE', 0);

--
-- Disparadores `incidencias`
--
DROP TRIGGER IF EXISTS `generar_id_incidencia`;
DELIMITER $$
CREATE TRIGGER `generar_id_incidencia` BEFORE INSERT ON `incidencias` FOR EACH ROW BEGIN
    DECLARE contador_actual INT;
    DECLARE prefijo CHAR(8); -- YYYYMMDD
    DECLARE sufijo CHAR(3);  -- 001, 002, etc.
    DECLARE la_fecha DATE;

    -- 1) Extraemos solo la parte de fecha (sin hora)
    SET la_fecha = DATE(NEW.fecha_creacion);

    -- 2) El prefijo es YYYYMMDD de esa fecha
    SET prefijo = DATE_FORMAT(la_fecha, '%Y%m%d');

    -- 3) Actualizar o insertar el contador diario
    IF EXISTS (
        SELECT 1 FROM contador_diario WHERE fecha = la_fecha
    ) THEN
        UPDATE contador_diario
        SET contador = contador + 1
        WHERE fecha = la_fecha;

        SELECT contador INTO contador_actual
        FROM contador_diario
        WHERE fecha = la_fecha;
    ELSE
        INSERT INTO contador_diario (fecha, contador)
        VALUES (la_fecha, 1);
        SET contador_actual = 1;
    END IF;

    -- 4) Formatear sufijo a 3 dígitos
    SET sufijo = LPAD(contador_actual, 3, '0');

    -- 5) Asignar el nuevo id
    SET NEW.id = CONCAT(prefijo, sufijo);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` enum('docente','administrativo','jefe','jefe_division','subdireccion','direccion') NOT NULL,
  `superior_id` int(11) DEFAULT NULL,
  `clave_unica` varchar(13) NOT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `firma` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `clave_unica` (`clave_unica`),
  KEY `division_id` (`division_id`),
  KEY `fk_superior` (`superior_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `telefono`, `rol`, `superior_id`, `clave_unica`, `contrasena`, `division_id`, `activo`, `firma`) VALUES
(1, 'Joel Leyva Mares', 'joelleyva@gmail.com', '6181259336', 'docente', 3, 'LEMJ760113ES4', '12345', 1, 1, 'firmas/firmaJoel'),
(2, 'Rudy Gamboa Ramírez', 'rudygr@gmail.com', '6741068218', 'docente', 3, 'GARR9610124XD', '12345', 2, 1, 'firmas/firmaRudy'),
(3, 'Juan Bustamante ', 'bustos@gmail.com', '6741203369', 'jefe_division', 4, 'BUJC801912M56', 'admin', 1, 1, 'firmas/firmaJuan'),
(4, 'Amsi Raquel Galindo Vázquez', 'amsibb@gmail.com', '6778857921', 'subdireccion', 5, 'GAV030607VS17', 'admin', 4, 1, 'firmas/firmaAmsi'),
(5, 'Miguel Ángel de la Rosa Medina', 'mike@gmail.com', '6185072866', 'direccion', NULL, 'ROMM030315L17', 'admin', 6, 1, 'firmas/firmaMiguel'),
(6, 'Edrei Zabdiel Estrada Corral', 'edrei@gmail.com', '6741123182', 'jefe_division', 4, 'ESCE032107PW3', 'admin', 2, 1, 'firmas/firmaEdrei'),
(7, 'Edgar Meraz Michel', 'gari@gmail.com', '6741108513', 'jefe_division', 4, 'MEME030707PO9', 'admin', 3, 1, 'firmas/firmaEdgar'),
(8, 'Jesús Sánchez Meraz', 'chuyon@gmail.com', '6741111444', 'docente', 3, 'SAMJ030119KAK', '12345', 3, 1, 'firmas/firmaJesus');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
