-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2025 at 10:21 PM
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
-- Database: `dictionary`
--

-- --------------------------------------------------------

--
-- Table structure for table `labels`
--

CREATE TABLE `labels` (
  `id` int(11) NOT NULL,
  `parent` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_dialect` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `meanings` (
  `id` int(11) NOT NULL,
  `word_id` int(11) NOT NULL,
  `definition` varchar(1023) NOT NULL,
  `example` varchar(1023) DEFAULT NULL,
  `priority` int(11) NOT NULL,
  `synonyms` varchar(1023) DEFAULT NULL,
  `antonyms` varchar(1023) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `name` varchar(31) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meanings_forms`
--

CREATE TABLE `meanings_forms` (
  `meaning_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `words_forms`
--

CREATE TABLE `words_forms` (
  `word_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `meanings_labels`
--

CREATE TABLE `meanings_labels` (
  `meaning_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `words`
--

CREATE TABLE `words` (
  `id` int(11) NOT NULL,
  `word` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `ipa` varchar(127) DEFAULT NULL,
  `syllables` int(2) DEFAULT NULL,
  `similar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `words_labels`
--

CREATE TABLE `words_labels` (
  `word_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `labels`
--
ALTER TABLE `labels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `label_id_FK` (`parent`);

--
-- Indexes for table `meanings`
--
ALTER TABLE `meanings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_word_id_1` (`word_id`);

--
-- Indexes for table `forms`
--

ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_form_name` (`name`);

--
-- Indexes for table `meanings_forms`
--

ALTER TABLE `meanings_forms`
  ADD UNIQUE KEY `unique_meaning_form` (`meaning_id`,`form_id`),
  ADD KEY `meanings_forms_form_id` (`form_id`);

--
-- Indexes for table `words_forms`
--
ALTER TABLE `words_forms`
  ADD UNIQUE KEY `unique_word_form` (`word_id`,`form_id`),
  ADD KEY `words_forms_form_id` (`form_id`);

--
-- Indexes for table `meanings_labels`
--
ALTER TABLE `meanings_labels`
  ADD KEY `meanings_labels_meaning_id` (`meaning_id`),
  ADD KEY `meanings_labels_label_id` (`label_id`) USING BTREE;

--
-- Indexes for table `words`
--
ALTER TABLE `words`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_words` (`word`);

--
-- Indexes for table `words_labels`
--
ALTER TABLE `words_labels`
  ADD KEY `words_labels_word_id` (`word_id`),
  ADD KEY `words_labels_label_id` (`label_id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `labels`
--
ALTER TABLE `labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meanings`
--
ALTER TABLE `meanings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forms`
--

ALTER TABLE `forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `words`
--
ALTER TABLE `words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `labels`
--
ALTER TABLE `labels`
  ADD CONSTRAINT `label_id_FK` FOREIGN KEY (`parent`) REFERENCES `labels` (`id`);

--
-- Constraints for table `meanings`
--
ALTER TABLE `meanings`
  ADD CONSTRAINT `FK_word_id_1` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`);

--
-- Constraints for table `meanings_forms`
--

ALTER TABLE `meanings_forms`
  ADD CONSTRAINT `meanings_forms_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  ADD CONSTRAINT `meanings_forms_meaning` FOREIGN KEY (`meaning_id`) REFERENCES `meanings` (`id`);

--
-- Constraints for table `words_forms`
--
ALTER TABLE `words_forms`
  ADD CONSTRAINT `words_forms_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  ADD CONSTRAINT `words_forms_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`);

--
-- Constraints for table `meanings_labels`
--
ALTER TABLE `meanings_labels`
  ADD CONSTRAINT `meanings_labels_label` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`),
  ADD CONSTRAINT `meanings_labels_meaning` FOREIGN KEY (`meaning_id`) REFERENCES `meanings` (`id`);

--
-- Constraints for table `words_labels`
--
ALTER TABLE `words_labels`
  ADD CONSTRAINT `words_labels_label` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`),
  ADD CONSTRAINT `words_labels_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
