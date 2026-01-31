-- phpMyAdmin SQL Dump
-- version 4.1.4
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 12-Set-2025 às 14:37
-- Versão do servidor: 5.6.15-log
-- PHP Version: 5.5.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sales_module`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `carts`
--

CREATE TABLE IF NOT EXISTS `carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estrutura da tabela `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Extraindo dados da tabela `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `description`, `image`, `category`, `stock`) VALUES
(1, 'Lukscolor TradiÃ§Ã£o Acrilica Branco 18LT ', '420.00', 'Tinta Acrilica Super Premium Lavavel de 18LT', 'p1.png', 'Tintas Acrilicas', 1),
(2, 'Lukscolor Esmalte Sintetico Branco Extra 3,6L', '130.00', 'Tinta Oleosa Esmalte Base Solvente (Dissolver com Agua Raz) de 3,6L na cor Branco Extra', 'p2.png', 'Tintas Esmaltes', 0),
(10, 'carro de formua 1 teste', '88584.00', 'carro de formula 1', 'p3.png', 'quero saber se funcionou', 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `sales`
--

CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `sale_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` int(11) DEFAULT NULL,
  `vendedor_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

--
-- Extraindo dados da tabela `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `quantity`, `total`, `sale_date`, `cliente_id`, `vendedor_id`) VALUES
(1, 1, 2, '59.80', '2024-08-26 17:28:45', NULL, NULL),
(2, 4, 1, '149.90', '2024-08-26 17:28:45', NULL, NULL),
(3, 8, 2, '239.80', '2024-08-26 17:28:45', NULL, NULL),
(4, 5, 1, '129.90', '2024-08-26 17:28:45', NULL, NULL),
(5, 5, 1, '129.90', '2024-08-26 17:38:14', NULL, NULL),
(6, 1, 1, '29.90', '2024-08-26 17:38:14', NULL, NULL),
(7, 1, 1, '420.00', '2025-08-09 13:11:02', NULL, NULL),
(8, 2, 6, '780.00', '2025-09-10 18:05:28', 1, 4),
(9, 2, 6, '780.00', '2025-09-10 18:07:58', 1, 4),
(10, 10, 1, '88584.00', '2025-09-10 18:08:13', 1, 4),
(11, 1, 1, '420.00', '2025-09-10 18:09:35', 1, 4),
(12, 1, 10, '4200.00', '2025-09-10 18:12:25', 1, 4),
(13, 10, 1, '88584.00', '2025-09-10 18:17:26', 1, 7);

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('cliente','funcionario','gerente') DEFAULT 'cliente',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`, `created_at`) VALUES
(1, 'Eduardo', 'a@gmail.com', '$2y$10$UIkY8JKBLWbIE1/x8Oy9GuRIAfZSnGV7yOt4Gn8KpC.OdZU8IvNLe', 'cliente', '2025-08-29 13:00:59'),
(5, 'Gerente Teste', 'g@gmail.com', '$2y$10$UIkY8JKBLWbIE1/x8Oy9GuRIAfZSnGV7yOt4Gn8KpC.OdZU8IvNLe', 'gerente', '2025-09-03 17:03:42'),
(4, 'Vendedor Teste', 'v@gmail.com', '$2y$10$UIkY8JKBLWbIE1/x8Oy9GuRIAfZSnGV7yOt4Gn8KpC.OdZU8IvNLe', 'funcionario', '2025-09-03 17:03:42'),
(6, 'testecliente', 'testec@gmail.com', '$2y$10$8kxXklJGBVLIdsIJ.81YV.EuTwTBzOhkFmb03VPdn3t8FCVJjVKhu', 'cliente', '2025-09-04 00:17:06'),
(7, 'testevendedor', 'testev@gmail.com', '$2y$10$EF28uEGYkKk343.DGR0iSeGWAdn8dFo7/16Ar8ivSeABURqvYSgKe', 'funcionario', '2025-09-04 00:17:28'),
(8, 'testegerente', 'testeg@gmail.com', '$2y$10$P0NQYwqjcEY6dRZVLtFOGOdUin5OqEGbqKocwHHNFx155OGWt0X/S', 'gerente', '2025-09-04 00:17:44');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
