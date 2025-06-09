-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-05-2025 a las 06:14:30
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12


CREATE DATABASE tuinventarioya;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `stockcerca`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

USE tuinventarioya;

CREATE TABLE `devoluciones` (
  `id_Devolucion` int(11) NOT NULL,
  `id_Negocio` int(11) NOT NULL,
  `FolioVenta` varchar(150) NOT NULL,
  `FolioDevolucion` varchar(150) NOT NULL,
  `FechaDev` date NOT NULL,
  `Descripcion` text NOT NULL,
  `Cantidades` text NOT NULL,
  `PrecioU` text NOT NULL,
  `CodigoBarras` text NOT NULL,
  `Marcas` text NOT NULL,
  `PrecioFinalDev` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `negocios`
--

CREATE TABLE `negocios` (
  `id_Negocio` int(11) NOT NULL,
  `Nombre` varchar(200) NOT NULL,
  `Propietario` varchar(200) NOT NULL,
  `NumTelefono` varchar(15) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Horarios` text NOT NULL,
  `Ubicacion` varchar(200) NOT NULL,
  `CP` varchar(15) NOT NULL,
  `EstadoActividad` varchar(100) NOT NULL,
  `Tipo` varchar(150) NOT NULL,
  `InicioSuscripcion` timestamp NOT NULL DEFAULT current_timestamp(),
  `FinSuscripcion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_Producto` int(11) NOT NULL,
  `Tipo` varchar(200) NOT NULL,
  `Nombre` varchar(200) NOT NULL,
  `CodigoBarras` varchar(200) NOT NULL,
  `CodigoProducto` varchar(200) NOT NULL,
  `CodigoPrincipal` varchar(200) NOT NULL,
  `Marca` varchar(250) NOT NULL,
  `Existencia` int(11) NOT NULL,
  `Precio` double NOT NULL,
  `UltimaFecha` date NOT NULL,
  `id_Negocio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendedor`
--

CREATE TABLE `vendedor` (
  `id_Vendedor` int(11) NOT NULL,
  `Nombre` varchar(200) NOT NULL,
  `ApeMaterno` varchar(200) NOT NULL,
  `ApePaterno` varchar(200) NOT NULL,
  `CURP` varchar(20) NOT NULL,
  `Clave` varchar(150) NOT NULL,
  `Pass` text NOT NULL,
  `NumTelefono` varchar(20) NOT NULL,
  `Email` varchar(200) NOT NULL,
  `Rol` varchar(100) NOT NULL,
  `id_Negocio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_Venta` int(11) NOT NULL,
  `Descripcion` text NOT NULL,
  `PreciosU` text NOT NULL,
  `Cantidades` text NOT NULL,
  `CodigosBarras` text NOT NULL,
  `Marcas` text NOT NULL,
  `PrecioFinal` double NOT NULL,
  `Fecha` date NOT NULL,
  `Folio` varchar(150) NOT NULL,
  `id_Negocio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id_Devolucion`),
  ADD KEY `id_Negocio` (`id_Negocio`);

--
-- Indices de la tabla `negocios`
--
ALTER TABLE `negocios`
  ADD PRIMARY KEY (`id_Negocio`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_Producto`),
  ADD KEY `id_Negocio` (`id_Negocio`);

--
-- Indices de la tabla `vendedor`
--
ALTER TABLE `vendedor`
  ADD PRIMARY KEY (`id_Vendedor`),
  ADD KEY `id_Negocio` (`id_Negocio`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_Venta`),
  ADD KEY `id_Negocio` (`id_Negocio`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id_Devolucion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `negocios`
--
ALTER TABLE `negocios`
  MODIFY `id_Negocio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_Producto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vendedor`
--
ALTER TABLE `vendedor`
  MODIFY `id_Vendedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_Venta` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `devoluciones_ibfk_1` FOREIGN KEY (`id_Negocio`) REFERENCES `negocios` (`id_Negocio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`id_Negocio`) REFERENCES `negocios` (`id_Negocio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `vendedor`
--
ALTER TABLE `vendedor`
  ADD CONSTRAINT `vendedor_ibfk_1` FOREIGN KEY (`id_Negocio`) REFERENCES `negocios` (`id_Negocio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_Negocio`) REFERENCES `negocios` (`id_Negocio`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
