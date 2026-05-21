-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 14/05/2026 às 21:53
-- Versão do servidor: 8.0.45-0ubuntu0.22.04.1
-- Versão do PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `arena_booking_ia`
--
CREATE DATABASE IF NOT EXISTS `arena_booking_ia` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `arena_booking_ia`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `arenas`
--

CREATE TABLE `arenas` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `arena_configurations`
--

CREATE TABLE `arena_configurations` (
  `id` bigint UNSIGNED NOT NULL,
  `arena_id` bigint UNSIGNED NOT NULL,
  `day_of_week` tinyint DEFAULT NULL COMMENT 'Dia da semana (0-6)',
  `config_data` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `default_price` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Preço padrão para reservas avulsas.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `available_slots`
--

CREATE TABLE `available_slots` (
  `id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_cash_movements`
--

CREATE TABLE `bar_cash_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `bar_cash_session_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `bar_order_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('venda','reforco','sangria','estorno') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_cash_sessions`
--

CREATE TABLE `bar_cash_sessions` (
  `id` bigint UNSIGNED NOT NULL,
  `total_vendas_sistema` decimal(12,2) NOT NULL DEFAULT '0.00',
  `user_id` bigint UNSIGNED NOT NULL,
  `opening_balance` decimal(10,2) NOT NULL,
  `expected_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(10,2) DEFAULT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `opened_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_categories`
--

CREATE TABLE `bar_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_orders`
--

CREATE TABLE `bar_orders` (
  `id` bigint UNSIGNED NOT NULL,
  `bar_table_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `bar_cash_session_id` bigint UNSIGNED DEFAULT NULL,
  `total_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('open','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_order_items`
--

CREATE TABLE `bar_order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `bar_order_id` bigint UNSIGNED NOT NULL,
  `bar_product_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_products`
--

CREATE TABLE `bar_products` (
  `id` bigint UNSIGNED NOT NULL,
  `barcode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_combo` tinyint(1) NOT NULL DEFAULT '0',
  `purchase_price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `min_stock` int NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `manage_stock` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bar_category_id` bigint UNSIGNED DEFAULT NULL,
  `unit_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UN',
  `content_quantity` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_product_compositions`
--

CREATE TABLE `bar_product_compositions` (
  `id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED NOT NULL,
  `child_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_sales`
--

CREATE TABLE `bar_sales` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `bar_cash_session_id` bigint UNSIGNED DEFAULT NULL,
  `total_value` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pago','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pago',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_sale_items`
--

CREATE TABLE `bar_sale_items` (
  `id` bigint UNSIGNED NOT NULL,
  `bar_sale_id` bigint UNSIGNED NOT NULL,
  `bar_product_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `price_at_sale` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_stock_movements`
--

CREATE TABLE `bar_stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `bar_product_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bar_tables`
--

CREATE TABLE `bar_tables` (
  `id` bigint UNSIGNED NOT NULL,
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('available','occupied','reserved','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cache`
--

CREATE TABLE `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cashiers`
--

CREATE TABLE `cashiers` (
  `id` bigint UNSIGNED NOT NULL,
  `arena_id` bigint UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `calculated_amount` decimal(10,2) NOT NULL,
  `actual_amount` decimal(10,2) NOT NULL,
  `difference` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'closed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `reopen_reason` text COLLATE utf8mb4_unicode_ci,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `reopened_by` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `closing_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `company_infos`
--

CREATE TABLE `company_infos` (
  `id` bigint UNSIGNED NOT NULL,
  `modules_active` int NOT NULL DEFAULT '0',
  `nome_fantasia` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnpj` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_suporte` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_contato` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logradouro` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `reserva_id` bigint UNSIGNED DEFAULT NULL,
  `arena_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `manager_id` bigint UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios`
--

CREATE TABLE `horarios` (
  `id` bigint UNSIGNED NOT NULL,
  `day_of_week` tinyint DEFAULT NULL COMMENT '0=Domingo, 6=Sábado. Usado para horários recorrentes.',
  `date` date DEFAULT NULL COMMENT 'Data específica para slots avulsos.',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '025_12_04_999999_add_default_price_to_arena_configurations_table_v3', 1),
(2, '2025_11_19_230002_create_available_slots_table', 1),
(3, '2025_11_19_230003_create_cache_locks_table', 1),
(4, '2025_11_19_230004_create_cache_table', 1),
(5, '2025_11_19_230005_create_failed_jobs_table', 1),
(6, '2025_11_19_230006_create_horarios_table', 1),
(7, '2025_11_19_230007_create_job_batches_table', 1),
(8, '2025_11_19_230008_create_jobs_table', 1),
(9, '2025_11_19_230009_create_password_reset_tokens_table', 1),
(10, '2025_11_19_230010_create_users_table', 1),
(11, '2025_11_19_230011_create_recurrent_series_table', 1),
(12, '2025_11_19_230012_create_reservas_table', 1),
(13, '2025_11_19_230014_create_sessions_table', 1),
(14, '2025_11_20_172815_create_financial_transactions_table', 1),
(15, '2025_12_05_234539_create_cashiers_table', 1),
(16, '2026_01_07_092354_create_arenas_table', 1),
(17, '2026_01_07_092552_add_arena_id_to_funcionamentos_table', 1),
(18, '2026_01_07_092706_add_arena_id_to_reservas_table', 1),
(19, '2026_01_07_135901_add_arena_id_to_financial_transactions_table', 1),
(20, '2026_01_09_132803_adjust_finance_structure_tables', 1),
(21, '2026_01_09_134940_update_foreign_key_on_financial_transactions', 1),
(22, '2026_01_09_190417_add_arena_id_to_users_table', 1),
(23, '2026_01_10_215607_add_arena_id_to_cashiers_table', 1),
(24, '2026_01_11_003037_create_company_infos_table', 1),
(25, '2026_01_22_134756_change_closing_time_to_nullable_on_cashiers_table', 1),
(26, '2026_01_23_082521_adjust_cashiers_unique_index', 1),
(27, '2026_01_28_092008_create_bar_products_table', 1),
(28, '2026_01_28_092051_create_bar_tables_table', 1),
(29, '2026_01_28_092624_create_bar_orders_table', 1),
(30, '2026_01_28_093030_create_bar_order_items_table', 1),
(31, '2026_01_28_093059_create_bar_cash_sessions_table', 1),
(32, '2026_01_28_105719_add_category_to_bar_products_table', 1),
(33, '2026_01_28_113846_create_bar_categories_table', 1),
(34, '2026_01_28_115200_add_bar_category_id_to_products', 1),
(35, '2026_01_28_123429_create_bar_bar_stock_movements_table', 1),
(36, '2026_01_28_125918_add_unit_fields_to_bar_products_table', 1),
(37, '2026_01_28_150823_create_bar_bar_sales_table', 1),
(38, '2026_01_28_150824_create_bar_bar_sale_items_table', 1),
(39, '2026_01_28_160913_update_payment_method_on_bar_sales_table', 1),
(40, '2026_01_29_111328_add_modules_active_to_company_infos_table', 1),
(41, '2026_01_30_100504_add_manage_stock_to_bar_products_table', 1),
(42, '2026_01_30_121805_add_subtotal_to_bar_order_items_table', 1),
(43, '2026_01_31_134218_update_user_roles_list', 1),
(44, '2026_02_02_213425_add_expected_balance_to_bar_cash_sessions_table', 1),
(45, '2026_02_02_213453_create_bar_cash_movements_table', 1),
(46, '2026_02_13_150414_add_cash_session_id_to_bar_tables_v2', 1),
(47, '2026_02_18_171437_update_status_enum_in_bar_tables', 1),
(48, '2026_02_23_094429_add_is_combo_to_bar_products_table', 1),
(49, '2026_02_23_094516_create_bar_product_compositions_table', 1),
(50, '2026_02_23_103648_add_total_vendas_to_bar_cash_sessions_table', 1),
(51, '2026_02_24_170656_add_details_to_bar_orders_table', 1),
(52, '2026_05_14_174904_add_ai_and_payment_columns_to_appointments_table', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recurrent_series`
--

CREATE TABLE `recurrent_series` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `end_date` date DEFAULT NULL COMMENT 'Data de término da série de reservas fixas.',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `reservas`
--

CREATE TABLE `reservas` (
  `id` bigint UNSIGNED NOT NULL,
  `arena_id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  `signal_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `client_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_contact` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_fixed` tinyint(1) NOT NULL DEFAULT '0',
  `day_of_week` tinyint DEFAULT NULL,
  `recurrent_series_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_recurrent` tinyint(1) NOT NULL DEFAULT '0',
  `week_index` tinyint DEFAULT NULL COMMENT '1, 2, 3... - ordem da reserva dentro da série',
  `manager_id` bigint UNSIGNED DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `no_show_reason` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `fixed_slot_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp_contact` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cliente',
  `arena_id` bigint UNSIGNED DEFAULT NULL,
  `is_vip` tinyint(1) NOT NULL DEFAULT '0',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `no_show_count` int NOT NULL DEFAULT '0',
  `customer_qualification` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_messages`
--

CREATE TABLE `whatsapp_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `remote_jid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_me` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `arenas`
--
ALTER TABLE `arenas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `arena_configurations`
--
ALTER TABLE `arena_configurations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `arena_configurations_arena_id_foreign` (`arena_id`);

--
-- Índices de tabela `available_slots`
--
ALTER TABLE `available_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `available_slots_date_start_time_end_time_index` (`date`,`start_time`,`end_time`),
  ADD KEY `available_slots_date_index` (`date`);

--
-- Índices de tabela `bar_cash_movements`
--
ALTER TABLE `bar_cash_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_cash_movements_bar_cash_session_id_foreign` (`bar_cash_session_id`),
  ADD KEY `bar_cash_movements_user_id_foreign` (`user_id`),
  ADD KEY `bar_cash_movements_bar_order_id_foreign` (`bar_order_id`);

--
-- Índices de tabela `bar_cash_sessions`
--
ALTER TABLE `bar_cash_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_cash_sessions_user_id_foreign` (`user_id`);

--
-- Índices de tabela `bar_categories`
--
ALTER TABLE `bar_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bar_categories_name_unique` (`name`);

--
-- Índices de tabela `bar_orders`
--
ALTER TABLE `bar_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_orders_bar_table_id_foreign` (`bar_table_id`),
  ADD KEY `bar_orders_user_id_foreign` (`user_id`),
  ADD KEY `bar_orders_bar_cash_session_id_foreign` (`bar_cash_session_id`);

--
-- Índices de tabela `bar_order_items`
--
ALTER TABLE `bar_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_order_items_bar_order_id_foreign` (`bar_order_id`),
  ADD KEY `bar_order_items_bar_product_id_foreign` (`bar_product_id`);

--
-- Índices de tabela `bar_products`
--
ALTER TABLE `bar_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bar_products_barcode_unique` (`barcode`),
  ADD KEY `bar_products_bar_category_id_foreign` (`bar_category_id`);

--
-- Índices de tabela `bar_product_compositions`
--
ALTER TABLE `bar_product_compositions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_product_compositions_parent_id_foreign` (`parent_id`),
  ADD KEY `bar_product_compositions_child_id_foreign` (`child_id`);

--
-- Índices de tabela `bar_sales`
--
ALTER TABLE `bar_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_sales_user_id_foreign` (`user_id`),
  ADD KEY `bar_sales_bar_cash_session_id_foreign` (`bar_cash_session_id`);

--
-- Índices de tabela `bar_sale_items`
--
ALTER TABLE `bar_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_sale_items_bar_sale_id_foreign` (`bar_sale_id`),
  ADD KEY `bar_sale_items_bar_product_id_foreign` (`bar_product_id`);

--
-- Índices de tabela `bar_stock_movements`
--
ALTER TABLE `bar_stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bar_stock_movements_bar_product_id_foreign` (`bar_product_id`),
  ADD KEY `bar_stock_movements_user_id_foreign` (`user_id`);

--
-- Índices de tabela `bar_tables`
--
ALTER TABLE `bar_tables`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Índices de tabela `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Índices de tabela `cashiers`
--
ALTER TABLE `cashiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cashiers_date_arena_unique` (`date`,`arena_id`),
  ADD KEY `cashiers_closed_by_user_id_foreign` (`user_id`),
  ADD KEY `cashiers_arena_id_foreign` (`arena_id`);

--
-- Índices de tabela `company_infos`
--
ALTER TABLE `company_infos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Índices de tabela `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `financial_transactions_user_id_foreign` (`user_id`),
  ADD KEY `financial_transactions_manager_id_foreign` (`manager_id`),
  ADD KEY `financial_transactions_reserva_id_foreign` (`reserva_id`);

--
-- Índices de tabela `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Índices de tabela `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Índices de tabela `recurrent_series`
--
ALTER TABLE `recurrent_series`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recurrent_series_user_id_foreign` (`user_id`);

--
-- Índices de tabela `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservas_fixed_slot_id_foreign` (`fixed_slot_id`),
  ADD KEY `reservas_recurrent_series_id_foreign` (`recurrent_series_id`),
  ADD KEY `reservas_manager_id_foreign` (`manager_id`),
  ADD KEY `reservas_user_id_foreign` (`user_id`),
  ADD KEY `reservas_arena_id_foreign` (`arena_id`);

--
-- Índices de tabela `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_whatsapp_contact_unique` (`whatsapp_contact`),
  ADD KEY `users_arena_id_foreign` (`arena_id`);

--
-- Índices de tabela `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `arenas`
--
ALTER TABLE `arenas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `arena_configurations`
--
ALTER TABLE `arena_configurations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `available_slots`
--
ALTER TABLE `available_slots`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_cash_movements`
--
ALTER TABLE `bar_cash_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_cash_sessions`
--
ALTER TABLE `bar_cash_sessions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_categories`
--
ALTER TABLE `bar_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_orders`
--
ALTER TABLE `bar_orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_order_items`
--
ALTER TABLE `bar_order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_products`
--
ALTER TABLE `bar_products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_product_compositions`
--
ALTER TABLE `bar_product_compositions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_sales`
--
ALTER TABLE `bar_sales`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_sale_items`
--
ALTER TABLE `bar_sale_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_stock_movements`
--
ALTER TABLE `bar_stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bar_tables`
--
ALTER TABLE `bar_tables`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cashiers`
--
ALTER TABLE `cashiers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `company_infos`
--
ALTER TABLE `company_infos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `financial_transactions`
--
ALTER TABLE `financial_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de tabela `recurrent_series`
--
ALTER TABLE `recurrent_series`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `arena_configurations`
--
ALTER TABLE `arena_configurations`
  ADD CONSTRAINT `arena_configurations_arena_id_foreign` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `bar_cash_movements`
--
ALTER TABLE `bar_cash_movements`
  ADD CONSTRAINT `bar_cash_movements_bar_cash_session_id_foreign` FOREIGN KEY (`bar_cash_session_id`) REFERENCES `bar_cash_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bar_cash_movements_bar_order_id_foreign` FOREIGN KEY (`bar_order_id`) REFERENCES `bar_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bar_cash_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `bar_cash_sessions`
--
ALTER TABLE `bar_cash_sessions`
  ADD CONSTRAINT `bar_cash_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `bar_orders`
--
ALTER TABLE `bar_orders`
  ADD CONSTRAINT `bar_orders_bar_cash_session_id_foreign` FOREIGN KEY (`bar_cash_session_id`) REFERENCES `bar_cash_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bar_orders_bar_table_id_foreign` FOREIGN KEY (`bar_table_id`) REFERENCES `bar_tables` (`id`),
  ADD CONSTRAINT `bar_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `bar_order_items`
--
ALTER TABLE `bar_order_items`
  ADD CONSTRAINT `bar_order_items_bar_order_id_foreign` FOREIGN KEY (`bar_order_id`) REFERENCES `bar_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bar_order_items_bar_product_id_foreign` FOREIGN KEY (`bar_product_id`) REFERENCES `bar_products` (`id`);

--
-- Restrições para tabelas `bar_products`
--
ALTER TABLE `bar_products`
  ADD CONSTRAINT `bar_products_bar_category_id_foreign` FOREIGN KEY (`bar_category_id`) REFERENCES `bar_categories` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `bar_product_compositions`
--
ALTER TABLE `bar_product_compositions`
  ADD CONSTRAINT `bar_product_compositions_child_id_foreign` FOREIGN KEY (`child_id`) REFERENCES `bar_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bar_product_compositions_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `bar_products` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `bar_sales`
--
ALTER TABLE `bar_sales`
  ADD CONSTRAINT `bar_sales_bar_cash_session_id_foreign` FOREIGN KEY (`bar_cash_session_id`) REFERENCES `bar_cash_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bar_sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `bar_sale_items`
--
ALTER TABLE `bar_sale_items`
  ADD CONSTRAINT `bar_sale_items_bar_product_id_foreign` FOREIGN KEY (`bar_product_id`) REFERENCES `bar_products` (`id`),
  ADD CONSTRAINT `bar_sale_items_bar_sale_id_foreign` FOREIGN KEY (`bar_sale_id`) REFERENCES `bar_sales` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `bar_stock_movements`
--
ALTER TABLE `bar_stock_movements`
  ADD CONSTRAINT `bar_stock_movements_bar_product_id_foreign` FOREIGN KEY (`bar_product_id`) REFERENCES `bar_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bar_stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `cashiers`
--
ALTER TABLE `cashiers`
  ADD CONSTRAINT `cashiers_arena_id_foreign` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cashiers_closed_by_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD CONSTRAINT `financial_transactions_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_transactions_reserva_id_foreign` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `recurrent_series`
--
ALTER TABLE `recurrent_series`
  ADD CONSTRAINT `recurrent_series_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

--
-- Restrições para tabelas `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_arena_id_foreign` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_fixed_slot_id_foreign` FOREIGN KEY (`fixed_slot_id`) REFERENCES `reservas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservas_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `reservas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_arena_id_foreign` FOREIGN KEY (`arena_id`) REFERENCES `arenas` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
