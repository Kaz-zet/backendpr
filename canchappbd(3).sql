-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 07:18 PM
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
(44, 2, 'Talleres', 'Rivadavia 1234', '', 'El mejor lugar!', 0, 0);

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

--
-- Dumping data for table `favoritos`
--

INSERT INTO `favoritos` (`id_favorito`, `id_usuario`, `id_cancha`, `fecha_agregado`) VALUES
(18, 2, 44, '2025-09-10 13:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `reserva`
--

CREATE TABLE `reserva` (
  `id_reserva` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_final` time NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cancha` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `email`, `contrasena`) VALUES
(1, 'A', 'A@gmail.com', '123456'),
(2, 'Beti', 'beti@gmail.com', '123456'),
(3, 'Pe', 'Pe@gmail.com', '123'),
(4, 'b', 'b@gmail.com', '123');

-- --------------------------------------------------------

--
-- Table structure for table `valoracion`
--

CREATE TABLE `valoracion` (
  `id_valoracion` int(11) NOT NULL,
  `valor` int(10) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cancha` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Dumping data for table `verificacion`
--

INSERT INTO `verificacion` (`id_verificacion`, `estado`, `fecha`, `observacion`, `id_usuario`) VALUES
(2, 'aprobado', '2025-08-31', '', 1),
(3, 'aprobado', '2025-08-31', '', 3);

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
  ADD KEY `id_cancha` (`id_cancha`);

--
-- Indexes for table `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id_reserva`);

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
  MODIFY `id_cancha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `duenio`
--
ALTER TABLE `duenio`
  MODIFY `id_duenio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id_favorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `reserva`
--
ALTER TABLE `reserva`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `valoracion`
--
ALTER TABLE `valoracion`
  MODIFY `id_valoracion` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_cancha`) REFERENCES `cancha` (`id_cancha`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
