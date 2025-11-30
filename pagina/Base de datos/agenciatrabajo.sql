-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-11-2025 a las 21:33:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `agenciatrabajo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `agencias`
--

CREATE TABLE `agencias` (
  `id_agencia` int(11) NOT NULL,
  `nombre_agencia` varchar(60) DEFAULT NULL,
  `ubicacion_agencia` text DEFAULT NULL,
  `numero_telefono_agencia` varchar(15) DEFAULT NULL,
  `email_agencia` varchar(255) DEFAULT NULL,
  `password_agencia` varchar(255) DEFAULT NULL,
  `nombre_contacto` varchar(20) DEFAULT NULL,
  `apellido_contacto` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `agencias`
--

INSERT INTO `agencias` (`id_agencia`, `nombre_agencia`, `ubicacion_agencia`, `numero_telefono_agencia`, `email_agencia`, `password_agencia`, `nombre_contacto`, `apellido_contacto`) VALUES
(3, 'agencia', 'bera-agencia', '911agencia', 'agencia@1', '$2y$10$qfstTXtkMsI/QLqiZiOd7eOS9mya3nNaNutWactC7NE5cJaxio.PO', 'agencianame', 'agenciasur'),
(4, 'a', 'a', 'a', 'a@a', '$2y$10$eAa1eTh0hFDyWb4hA3Ybyu0ej5FqlYgY74Cz09a5XeNT7wOWVK9OG', 'a', 'a'),
(5, 'f', 'f', 'f', 'f@f', '$2y$10$WenWYfGEwMCgDROZT0HZSe9Kc/8LzcIJ5xj/UX16TlLrxk5OXrgo.', 'f', 'f');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `castings`
--

CREATE TABLE `castings` (
  `casting_id` int(11) NOT NULL,
  `agencia_id` int(11) DEFAULT NULL,
  `NombrePuesto` varchar(100) DEFAULT NULL,
  `DescripcionPuesto` text DEFAULT NULL,
  `CantVacantes` int(11) DEFAULT NULL,
  `NivelExperiencia` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `castings`
--

INSERT INTO `castings` (`casting_id`, `agencia_id`, `NombrePuesto`, `DescripcionPuesto`, `CantVacantes`, `NivelExperiencia`) VALUES
(9, 3, 'a', 'a', 0, 'Bajo'),
(10, 4, 'a', 'a', 0, 'Bajo'),
(11, 4, 'a', 'aa', 0, 'Bajo'),
(12, 4, 'a', 'a', 0, 'Bajo'),
(13, 3, 'A', 'A', 0, 'Bajo'),
(14, 3, 'a', 'a', 0, 'Bajo'),
(15, 3, 'a', 'a', 0, 'Bajo'),
(16, 5, 'f', 'f', 0, 'Bajo'),
(17, 5, 'd', 'd', 0, 'Bajo'),
(18, 5, 'd', 'd', 0, 'Bajo'),
(19, 5, 'd', 'd', 0, 'Bajo'),
(20, 5, 'd', 'd', 0, 'Bajo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curriculums`
--

CREATE TABLE `curriculums` (
  `curriculum_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `educacion` text DEFAULT NULL,
  `ciudad` varchar(50) DEFAULT NULL,
  `experiencia` text DEFAULT NULL,
  `habilidades` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `curriculums`
--

INSERT INTO `curriculums` (`curriculum_id`, `usuario_id`, `biografia`, `educacion`, `ciudad`, `experiencia`, `habilidades`) VALUES
(40, 5, 'a', 'a', 'a', 'aaa', 'a'),
(43, 6, 'aa', 'a', 'a', 'a', 'a'),
(47, 8, 'a', 'a', 'a', 'a', 'a'),
(50, 9, 'f', 'f', 'f', 'f', 'f'),
(51, 7, 'c', 'c', 'c', 'c', 'c'),
(54, 10, 'a', 'a', 'a', 'a', 'aa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `notificacion_id` int(11) NOT NULL,
  `curriculum_id` int(11) NOT NULL,
  `agencia_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_gestion` datetime DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `estado` enum('pendiente','invitacion','aceptada','rechazada') DEFAULT 'pendiente',
  `fecha_respuesta` datetime DEFAULT NULL,
  `casting_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`notificacion_id`, `curriculum_id`, `agencia_id`, `fecha`, `fecha_gestion`, `leida`, `estado`, `fecha_respuesta`, `casting_id`) VALUES
(55, 40, 3, '2025-11-07 11:20:36', NULL, 1, '', NULL, NULL),
(61, 43, 4, '2025-11-07 19:30:53', NULL, 1, 'aceptada', '2025-11-07 16:31:07', NULL),
(62, 43, 4, '2025-11-07 19:31:07', NULL, 1, '', NULL, NULL),
(63, 43, 3, '2025-11-07 19:31:23', '2025-11-07 16:36:32', 1, 'aceptada', NULL, 9),
(64, 43, 4, '2025-11-07 19:31:47', '2025-11-07 16:32:01', 1, 'aceptada', NULL, 11),
(65, 43, 4, '2025-11-07 19:34:01', '2025-11-07 16:34:10', 1, 'aceptada', NULL, 10),
(78, 47, 3, '2025-11-07 19:57:36', NULL, 1, 'aceptada', '2025-11-07 16:57:43', NULL),
(79, 47, 3, '2025-11-07 19:57:43', NULL, 1, '', NULL, NULL),
(86, 50, 5, '2025-11-07 20:11:12', '2025-11-07 17:30:06', 1, 'aceptada', NULL, 16),
(87, 50, 3, '2025-11-07 20:11:13', NULL, 0, 'pendiente', NULL, 14),
(88, 50, 3, '2025-11-07 20:11:16', NULL, 0, 'pendiente', NULL, 15),
(89, 50, 3, '2025-11-07 20:11:18', NULL, 0, 'pendiente', NULL, 13),
(90, 50, 4, '2025-11-07 20:11:19', NULL, 0, 'pendiente', NULL, 11),
(91, 50, 4, '2025-11-07 20:11:20', NULL, 0, 'pendiente', NULL, 10),
(92, 50, 4, '2025-11-07 20:11:22', NULL, 0, 'pendiente', NULL, 12),
(96, 54, 5, '2025-11-07 20:29:33', NULL, 1, 'aceptada', '2025-11-07 17:29:59', NULL),
(97, 54, 5, '2025-11-07 20:29:43', '2025-11-07 17:29:52', 1, 'rechazada', NULL, 20),
(98, 54, 5, '2025-11-07 20:29:59', NULL, 1, '', NULL, NULL),
(99, 54, 5, '2025-11-07 20:30:16', '2025-11-07 17:30:30', 1, 'aceptada', NULL, 19),
(100, 54, 5, '2025-11-07 20:30:17', '2025-11-07 17:30:23', 1, 'rechazada', NULL, 17);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `talentos`
--

CREATE TABLE `talentos` (
  `id_talento` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `numero_telefono` varchar(20) NOT NULL,
  `tipo_documento` varchar(20) NOT NULL,
  `numero_documento` varchar(20) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `es_premium` tinyint(1) NOT NULL DEFAULT 0,
  `postulaciones_mes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `talentos`
--

INSERT INTO `talentos` (`id_talento`, `nombre`, `email`, `password`, `apellido`, `numero_telefono`, `tipo_documento`, `numero_documento`, `fecha_nacimiento`, `es_premium`, `postulaciones_mes`) VALUES
(5, 'talento', 'talento@1', '$2y$10$dw4wUYeuFTJy6mPiRECOr.aIfyWbpqM7PaUdRa7x1f0W1krXnvtxS', 'talentoso', '911talento', 'DNI', '112323232', '2006-11-09', 0, 5),
(6, 'b', 'b@b', '$2y$10$Xe.JXNf06qJHz8SP6Jwd9uUoMxItgxuJDncDf/7EdWzpoDGqMEYkG', 'b', 'b', 'DNI', 'b', '2025-12-06', 0, 5),
(7, 'c', 'c@c', '$2y$10$cZYd18QvIZh8iaMp3oyMFeuTa2zLAwdCHY77uMSiIfV4GbHhb5Ica', 'c', 'c', 'DNI', 'c', '2025-12-07', 0, 2),
(8, 'D', 'd@d', '$2y$10$5ZReVPTtjgcFGCO.r323OejbMsFLM8gTvI5LrMO8PoX2Ed51rTz76', 'D', 'D', 'DNI', 'D', '2025-12-05', 0, 4),
(9, 'e', 'e@e', '$2y$10$zgtMY18X2vsESjCmgMq90ON/VQf.AgC14PACGwmxtG9z/cW4y9WNW', 'e', 'e', 'DNI', 'e', '2025-11-08', 1, 0),
(10, 'h', 'h@h', '$2y$10$z0dZNNWRmayXzRdOdBmC0uec1cGJT6yyL89ykdkv1UhA0TSEASFta', 'h', 'h', 'DNI', 'h', '2025-12-05', 0, 3);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `agencias`
--
ALTER TABLE `agencias`
  ADD PRIMARY KEY (`id_agencia`);

--
-- Indices de la tabla `castings`
--
ALTER TABLE `castings`
  ADD PRIMARY KEY (`casting_id`),
  ADD KEY `agencia_id` (`agencia_id`);

--
-- Indices de la tabla `curriculums`
--
ALTER TABLE `curriculums`
  ADD PRIMARY KEY (`curriculum_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`notificacion_id`),
  ADD KEY `curriculum_id` (`curriculum_id`),
  ADD KEY `agencia_id` (`agencia_id`);

--
-- Indices de la tabla `talentos`
--
ALTER TABLE `talentos`
  ADD PRIMARY KEY (`id_talento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `agencias`
--
ALTER TABLE `agencias`
  MODIFY `id_agencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `castings`
--
ALTER TABLE `castings`
  MODIFY `casting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `curriculums`
--
ALTER TABLE `curriculums`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `notificacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT de la tabla `talentos`
--
ALTER TABLE `talentos`
  MODIFY `id_talento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `castings`
--
ALTER TABLE `castings`
  ADD CONSTRAINT `castings_ibfk_1` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id_agencia`);

--
-- Filtros para la tabla `curriculums`
--
ALTER TABLE `curriculums`
  ADD CONSTRAINT `curriculums_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `talentos` (`id_talento`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`curriculum_id`),
  ADD CONSTRAINT `notificaciones_ibfk_2` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id_agencia`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
