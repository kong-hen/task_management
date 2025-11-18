-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-10-09 15:36:52
-- 服务器版本： 5.7.44-log
-- PHP 版本： 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `task_dev_xma_run`
--

-- --------------------------------------------------------

--
-- 表的结构 `domains`
--

CREATE TABLE `domains` (
  `id` int(11) NOT NULL COMMENT '域名ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '域名名称',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '域名地址',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用，0禁用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='域名表';

-- --------------------------------------------------------

--
-- 表的结构 `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL COMMENT '链接ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '链接名称',
  `domain` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '域名',
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '奖励类型：1链接，2文本',
  `award` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '奖励内容',
  `click` tinyint(1) NOT NULL DEFAULT '1' COMMENT '强制点击广告：0需要，1不需要',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用，0禁用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='链接表';

-- --------------------------------------------------------

--
-- 表的结构 `task_stats`
--

CREATE TABLE `task_stats` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL COMMENT '任务ID',
  `did` bigint(20) NOT NULL DEFAULT '0' COMMENT '访问设备统计',
  `view` bigint(20) NOT NULL DEFAULT '0' COMMENT '广告观看量',
  `click` bigint(20) NOT NULL DEFAULT '0' COMMENT '广告点击量',
  `created_at` date NOT NULL COMMENT '日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `task_view`
--

CREATE TABLE `task_view` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL COMMENT '任务id',
  `ip` varchar(140) DEFAULT NULL COMMENT '访问ip',
  `did` varchar(100) DEFAULT NULL COMMENT '访问设备标识',
  `award_type` int(11) DEFAULT NULL COMMENT '奖励类型',
  `award` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '奖励内容',
  `status` tinyint(1) DEFAULT '0' COMMENT '是否观看了广告：0未观看，1观看广告，2点击广告',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '任务创建时间',
  `completed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '任务完成时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `xcx`
--

CREATE TABLE `xcx` (
  `id` int(11) NOT NULL COMMENT '小程序ID',
  `type` tinyint(1) DEFAULT '1' COMMENT '小程序类型：1非个人，0非个人',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '小程序名称',
  `appid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '小程序AppID',
  `secret` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '小程序Secret',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '小程序广告页面路径',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用，0禁用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序表';

--
-- 转储表的索引
--

--
-- 表的索引 `domains`
--
ALTER TABLE `domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_url` (`url`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `task_stats`
--
ALTER TABLE `task_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_task_date` (`task_id`,`created_at`);

--
-- 表的索引 `task_view`
--
ALTER TABLE `task_view`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_ip` (`task_id`,`ip`),
  ADD KEY `idx_viewed_at` (`created_at`),
  ADD KEY `status` (`status`);

--
-- 表的索引 `xcx`
--
ALTER TABLE `xcx`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_appid` (`appid`),
  ADD KEY `idx_status` (`status`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `domains`
--
ALTER TABLE `domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '域名ID';

--
-- 使用表AUTO_INCREMENT `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '链接ID';

--
-- 使用表AUTO_INCREMENT `task_stats`
--
ALTER TABLE `task_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `task_view`
--
ALTER TABLE `task_view`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `xcx`
--
ALTER TABLE `xcx`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '小程序ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
