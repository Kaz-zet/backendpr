-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 15, 2025 at 06:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `canchappbd`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `nombre`, `email`, `contrasena`) VALUES
(1, 'Ad', 'Ad@gmail.com', '123');

-- --------------------------------------------------------

--
-- Table structure for table `cancha`
--

CREATE TABLE `cancha` (
  `id_cancha` int(11) NOT NULL,
  `id_duenio` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `lugar` varchar(120) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `bio` text NOT NULL,
  `verificado` tinyint(1) NOT NULL,
  `valoracion` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cancha`
--

INSERT INTO `cancha` (`id_cancha`, `id_duenio`, `nombre`, `lugar`, `foto`, `bio`, `verificado`, `valoracion`) VALUES
(73, 2, 'Talleres', 'Alsina 1567', 'cancha_1757704787_8018.png', 'Cancha de Padel 1v1 y 2v2', 0, 5),
(74, 2, 'Club Atlético Boca Juniors', 'Alsina 1244', 'cancha_1757718101_6684.png', 'Cancha de padel 1v1 y 2v2!', 0, 4);

-- --------------------------------------------------------

--
-- Table structure for table `duenio`
--

CREATE TABLE `duenio` (
  `id_duenio` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contrasena` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `duenio`
--

INSERT INTO `duenio` (`id_duenio`, `id_usuario`, `nombre`, `email`, `contrasena`) VALUES
(1, 0, 'Duenio', 'Duenio@gmail.com', '123'),
(2, 1, 'A', 'A@gmail.com', '123456'),
(3, 3, 'Pe', 'Pe@gmail.com', '123');

-- --------------------------------------------------------

--
-- Table structure for table `favoritos`
--

CREATE TABLE `favoritos` (
  `id_favorito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cancha` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reserva`
--

CREATE TABLE `reserva` (
  `id_reserva` int(11) NOT NULL,
  `codigo_reserva` varchar(50) NOT NULL DEFAULT uuid(),
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_final` time NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cancha` int(11) NOT NULL,
  `espacios_reservados` int(11) NOT NULL DEFAULT 1,
  `jugadores_reservados` int(11) NOT NULL DEFAULT 1,
  `telefono` varchar(20) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('activa','cancelada') DEFAULT 'activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reserva`
--

INSERT INTO `reserva` (`id_reserva`, `codigo_reserva`, `fecha`, `hora_inicio`, `hora_final`, `id_usuario`, `id_cancha`, `espacios_reservados`, `jugadores_reservados`, `telefono`, `observaciones`, `estado`) VALUES
(56, 'MNTHZ6', '2025-09-14', '21:00:00', '22:00:00', 5, 73, 2, 1, '124124', '', 'cancelada'),
(57, '6BMSKT', '2025-09-13', '22:00:00', '23:00:00', 2, 74, 1, 1, '12345464564', '', 'cancelada'),
(58, '6AUN9K', '2025-09-13', '22:00:00', '23:00:00', 4, 74, 1, 1, '12434452332', '', 'cancelada'),
(59, 'XQP742', '2025-09-14', '12:00:00', '13:00:00', 4, 73, 2, 1, '12334342123', '', 'cancelada'),
(60, '4TLN0Q', '2025-09-14', '12:00:00', '13:00:00', 6, 73, 1, 1, '12345668', '', 'cancelada'),
(61, 'LCY9W4', '2025-09-14', '12:00:00', '13:00:00', 2, 73, 1, 1, '21321134321', '', 'cancelada'),
(62, 'L1G7FC', '2025-09-14', '15:00:00', '16:00:00', 2, 74, 1, 1, '2131314123', '', 'cancelada'),
(63, 'JTCQFS', '2025-09-14', '15:00:00', '16:00:00', 2, 74, 1, 1, '2131434123131', '', 'cancelada'),
(64, 'LZUGDT', '2025-09-14', '16:00:00', '17:00:00', 4, 73, 2, 1, '3143134321', '', 'cancelada'),
(65, 'HPZ07W', '2025-09-14', '16:00:00', '17:00:00', 6, 73, 1, 1, '13214123', '', 'cancelada'),
(66, '3PA7DT', '2025-09-15', '12:00:00', '13:00:00', 2, 74, 2, 1, '2131413412', '', 'activa');

-- --------------------------------------------------------

--
-- Table structure for table `reserva_jugadores`
--

CREATE TABLE `reserva_jugadores` (
  `id_jugador_reserva` int(11) NOT NULL,
  `id_reserva` int(11) NOT NULL,
  `nombre_jugador` varchar(120) NOT NULL,
  `telefono_jugador` varchar(20) DEFAULT NULL,
  `fecha_union` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `email`, `contrasena`, `foto`) VALUES
(1, 'A', 'A@gmail.com', '123456', NULL),
(2, 'Beti', 'beti@gmail.com', '123456', NULL),
(3, 'Pe', 'Pe@gmail.com', '123', NULL),
(4, 'b', 'b@gmail.com', '123', NULL),
(5, 'CAA', 'CA@gmail.com', '123', 'usuario_5_1757718444.png'),
(6, 'Z', 'Z@gmail.com', '123', 'usuario_6_1757790994.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `valoracion`
--

CREATE TABLE `valoracion` (
  `id_valoracion` int(11) NOT NULL,
  `valor` int(10) NOT NULL,
  `comentario` text NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cancha` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `valoracion`
--

INSERT INTO `valoracion` (`id_valoracion`, `valor`, `comentario`, `id_usuario`, `id_cancha`) VALUES
(2, 4, 'Muy buena gestion!', 6, 74);

-- --------------------------------------------------------

--
-- Table structure for table `verificacion`
--

CREATE TABLE `verificacion` (
  `id_verificacion` int(11) NOT NULL,
  `estado` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `fecha` date NOT NULL,
  `observacion` varchar(180) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cancha`
--
ALTER TABLE `cancha`
  ADD PRIMARY KEY (`id_cancha`);

--
-- Indexes for table `duenio`
--
ALTER TABLE `duenio`
  ADD PRIMARY KEY (`id_duenio`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id_favorito`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`id_cancha`),
  ADD KEY `favoritos_ibfk_2` (`id_cancha`);

--
-- Indexes for table `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id_reserva`),
  ADD UNIQUE KEY `codigo_reserva` (`codigo_reserva`),
  ADD UNIQUE KEY `codigo_reserva_2` (`codigo_reserva`),
  ADD UNIQUE KEY `codigo_reserva_3` (`codigo_reserva`);

--
-- Indexes for table `reserva_jugadores`
--
ALTER TABLE `reserva_jugadores`
  ADD PRIMARY KEY (`id_jugador_reserva`),
  ADD KEY `id_reserva` (`id_reserva`);

--
-- Indexes for table `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `valoracion`
--
ALTER TABLE `valoracion`
  ADD PRIMARY KEY (`id_valoracion`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`id_cancha`);

--
-- Indexes for table `verificacion`
--
ALTER TABLE `verificacion`
  ADD PRIMARY KEY (`id_verificacion`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cancha`
--
ALTER TABLE `cancha`
  MODIFY `id_cancha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `duenio`
--
ALTER TABLE `duenio`
  MODIFY `id_duenio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id_favorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `reserva`
--
ALTER TABLE `reserva`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `reserva_jugadores`
--
ALTER TABLE `reserva_jugadores`
  MODIFY `id_jugador_reserva` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `valoracion`
--
ALTER TABLE `valoracion`
  MODIFY `id_valoracion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `verificacion`
--
ALTER TABLE `verificacion`
  MODIFY `id_verificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_cancha`) REFERENCES `cancha` (`id_cancha`) ON DELETE CASCADE;

--
-- Constraints for table `reserva_jugadores`
--
ALTER TABLE `reserva_jugadores`
  ADD CONSTRAINT `reserva_jugadores_ibfk_1` FOREIGN KEY (`id_reserva`) REFERENCES `reserva` (`id_reserva`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
