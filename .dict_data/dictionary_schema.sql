-- Dictionary schema (standardized build)
-- Generated: 2025-12-03

SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `meanings_labels`;
DROP TABLE IF EXISTS `meanings_forms`;
DROP TABLE IF EXISTS `words_labels`;
DROP TABLE IF EXISTS `words_forms`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `views`;
DROP TABLE IF EXISTS `ety_views`;
DROP TABLE IF EXISTS `ety_favorites`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `tokens`;
DROP TABLE IF EXISTS `wotd`;
DROP TABLE IF EXISTS `meanings`;
DROP TABLE IF EXISTS `words`;
DROP TABLE IF EXISTS `labels`;
DROP TABLE IF EXISTS `forms`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `forms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_forms_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `labels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent` int unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_dialect` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_labels_name` (`name`),
  KEY `idx_labels_parent` (`parent`),
  CONSTRAINT `fk_labels_parent` FOREIGN KEY (`parent`) REFERENCES `labels` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `words` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `ipa` varchar(128) DEFAULT NULL,
  `syllables` tinyint unsigned DEFAULT NULL,
  `similar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_words_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `meanings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word_id` int unsigned NOT NULL,
  `definition` text NOT NULL,
  `example` text DEFAULT NULL,
  `priority` int unsigned NOT NULL DEFAULT 0,
  `synonyms` text DEFAULT NULL,
  `antonyms` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_meanings_word_id` (`word_id`),
  CONSTRAINT `fk_meanings_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_meanings_priority` CHECK (`priority` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favorites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `word_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_favorites_user_word` (`user_id`,`word_id`),
  KEY `idx_favorites_word_id` (`word_id`),
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorites_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `views` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `word_id` int unsigned NOT NULL,
  `viewed` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_views_user_id` (`user_id`),
  KEY `idx_views_word_id` (`word_id`),
  CONSTRAINT `fk_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_views_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wotd` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word_id` int unsigned NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wotd_date` (`date`),
  KEY `idx_wotd_word_id` (`word_id`),
  CONSTRAINT `fk_wotd_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `words_forms` (
  `word_id` int unsigned NOT NULL,
  `form_id` int unsigned NOT NULL,
  `priority` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`word_id`,`form_id`),
  KEY `idx_words_forms_form_id` (`form_id`),
  CONSTRAINT `fk_words_forms_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_words_forms_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_words_forms_priority` CHECK (`priority` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `words_labels` (
  `word_id` int unsigned NOT NULL,
  `label_id` int unsigned NOT NULL,
  PRIMARY KEY (`word_id`,`label_id`),
  KEY `idx_words_labels_label_id` (`label_id`),
  CONSTRAINT `fk_words_labels_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_words_labels_label` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `meanings_forms` (
  `meaning_id` int unsigned NOT NULL,
  `form_id` int unsigned NOT NULL,
  PRIMARY KEY (`meaning_id`,`form_id`),
  KEY `idx_meanings_forms_form_id` (`form_id`),
  CONSTRAINT `fk_meanings_forms_meaning` FOREIGN KEY (`meaning_id`) REFERENCES `meanings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meanings_forms_form` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `meanings_labels` (
  `meaning_id` int unsigned NOT NULL,
  `label_id` int unsigned NOT NULL,
  PRIMARY KEY (`meaning_id`,`label_id`),
  KEY `idx_meanings_labels_label_id` (`label_id`),
  CONSTRAINT `fk_meanings_labels_meaning` FOREIGN KEY (`meaning_id`) REFERENCES `meanings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meanings_labels_label` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token` (`token_hash`),
  KEY `idx_password_resets_user_id` (`user_id`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tokens_token` (`token`),
  KEY `idx_tokens_user_id` (`user_id`),
  CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ety_favorites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `word` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ety_favorites_user_word` (`user_id`,`word`),
  CONSTRAINT `fk_ety_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ety_views` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `word` varchar(255) NOT NULL,
  `viewed` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ety_views_user_id` (`user_id`),
  KEY `idx_ety_views_word` (`word`),
  CONSTRAINT `fk_ety_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
