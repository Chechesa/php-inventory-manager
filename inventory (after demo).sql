-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 21-12-2023 a las 19:34:34
-- Versión del servidor: 8.0.31
-- Versión de PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventory`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `items`
--

CREATE TABLE `items` (
  `id` int NOT NULL,
  `item` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `item_type` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `items`
--

INSERT INTO `items` (`id`, `item`, `item_type`) VALUES
(1, 'Pen', 1),
(2, 'Printer', 2),
(3, 'Marker', 1),
(4, 'Scanner', 2),
(5, 'Clear Tape', 1),
(6, 'Standing Table', 2),
(7, 'Shredder', 2),
(8, 'Thumbtack', 1),
(10, 'Paper Clip', 1),
(11, 'A4 Sheet', 1),
(12, 'Notebook', 1),
(13, 'Chair', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requests`
--

CREATE TABLE `requests` (
  `req_id` int NOT NULL,
  `requested_by` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `requested_on` date NOT NULL,
  `ordered_on` date NOT NULL,
  `items` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `requests`
--

INSERT INTO `requests` (`req_id`, `requested_by`, `requested_on`, `ordered_on`, `items`) VALUES
(1, 'maya', '2023-04-01', '2023-05-12', '[{1,1}, {5,1}, {3, 1}] '),
(2, 'kie', '2023-04-03', '2023-05-12', '[{2,2}] '),
(3, 'ron', '2023-04-10', '2023-05-12', '[{3,1},{10,1}] '),
(4, 'maya', '2023-04-20', '2023-05-12', '[{4,2},{2,2}]'),
(5, 'john', '2023-05-01', '2023-05-12', '[{5,1}{12,1}] '),
(6, 'smith', '2023-05-04', '2023-05-12', '[{6,2}] '),
(7, 'john', '2023-05-10', '2023-05-12', '[{7,2}] '),
(8, 'lily', '2023-05-11', '2023-05-12', '[{8,1},{11,1}] '),
(9, 'lily', '2023-05-11', '2023-05-12', '[{7,2}] '),
(10, 'lily', '2023-05-11', '2023-05-12', '[{13, 3}] '),
(11, 'John', '2023-12-16', '2023-12-16', '[{1,1},{3,1},{1,1}]'),
(14, 'John', '2023-12-16', '2023-12-16', '[{1,1},{8,1}]'),
(16, 'John', '2023-12-16', '2023-12-16', '[{3,1},{8,1},{5,1},{10,1}]'),
(18, 'John', '2023-12-16', '2023-12-16', '[{1,1},{8,1}]'),
(20, 'John', '2023-12-16', '2023-12-16', '[{13,3}]'),
(21, 'John', '2023-12-16', '2023-12-16', '[{3,1}]'),
(25, 'Roger', '2023-12-16', '2023-12-16', '[{5,1},{1,1},{3,1},{10,1}]'),
(27, 'Roger', '2023-12-17', '2023-12-17', '[{7,2},{2,2}]'),
(28, 'Camila', '2023-12-17', '2023-12-17', '[{4,2}]'),
(29, 'Camila', '2023-12-17', '2023-12-17', '[{3,1}]'),
(30, 'Claude', '2023-12-17', '2023-12-17', '[{12,1},{5,1},{1,1}]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `summary`
--

CREATE TABLE `summary` (
  `req_id` int NOT NULL,
  `requested_by` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ordered_on` date NOT NULL,
  `items` varchar(1023) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `summary`
--

INSERT INTO `summary` (`req_id`, `requested_by`, `ordered_on`, `items`) VALUES
(1, 'maya', '2023-05-12', '[{1,[1,5,3]},{2,[4,2]}]'),
(2, 'kie', '2023-05-12', '[{2,[2]}]'),
(3, 'ron', '2023-05-12', '[{1,[3,10]}]'),
(5, 'john', '2023-05-12', '[{1,[5,12,1,3,1,1,8,3,8,5,10,1,8,3]},{2,[7]},{3,[13]}]'),
(6, 'smith', '2023-05-12', '[{2,[6]}]'),
(8, 'lily', '2023-05-12', '[{1,[8,11]},{2,[7]},{3,[13]}]'),
(25, 'roger', '2023-12-16', '[{1,[5,1,3,10]},{2,[7,2]}]'),
(28, 'camila', '2023-12-17', '[{2,[4]},{1,[3]}]'),
(30, 'claude', '2023-12-17', '[{1,[12,5,1]}]');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`req_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `items`
--
ALTER TABLE `items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `requests`
--
ALTER TABLE `requests`
  MODIFY `req_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
