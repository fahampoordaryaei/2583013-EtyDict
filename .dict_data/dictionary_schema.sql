-- Dictionary schema refresh with stronger relational guarantees
-- Includes stricter uniqueness, composite primary keys for join tables,
-- cascading foreign keys, additional helpful indexes, and essential CHECK constraints.

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

START TRANSACTION;
SET @old_character_set_client=@@character_set_client;
SET @old_character_set_results=@@character_set_results;
SET @old_collation_connection=@@collation_connection;
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `words_labels`;
DROP TABLE IF EXISTS `words_forms`;
DROP TABLE IF EXISTS `meanings_labels`;
DROP TABLE IF EXISTS `meanings_forms`;
DROP TABLE IF EXISTS `views`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `edits`;
DROP TABLE IF EXISTS `wotd`;
DROP TABLE IF EXISTS `user_messages`;
DROP TABLE IF EXISTS `tokens`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `meanings`;
DROP TABLE IF EXISTS `labels`;
DROP TABLE IF EXISTS `forms`;
DROP TABLE IF EXISTS `words`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(32) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  CONSTRAINT `chk_users_is_verified` CHECK (`is_verified` IN (0,1)),
  CONSTRAINT `chk_users_is_active` CHECK (`is_active` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `words` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `word` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `ipa` VARCHAR(128) DEFAULT NULL,
  `syllables` TINYINT UNSIGNED DEFAULT NULL,
  `similar` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_words_word` (`word`),
  CONSTRAINT `chk_words_syllables` CHECK (`syllables` IS NULL OR `syllables` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `forms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_forms_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `labels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `is_dialect` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_labels_parent` (`parent`),
  CONSTRAINT `fk_labels_parent`
    FOREIGN KEY (`parent`) REFERENCES `labels` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_labels_is_dialect` CHECK (`is_dialect` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `meanings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `word_id` INT UNSIGNED NOT NULL,
  `definition` VARCHAR(1023) NOT NULL,
  `example` VARCHAR(1023) DEFAULT NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `synonyms` VARCHAR(1023) DEFAULT NULL,
  `antonyms` VARCHAR(1023) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_meanings_word_priority` (`word_id`,`priority`),
  CONSTRAINT `fk_meanings_word`
    FOREIGN KEY (`word_id`) REFERENCES `words` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_meanings_priority` CHECK (`priority` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tokens_token` (`token`),
  KEY `idx_tokens_user_expires` (`user_id`,`expires_at`),
  CONSTRAINT `fk_tokens_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_tokens_expiration` CHECK (`expires_at` > `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token` (`token_hash`),
  KEY `idx_password_resets_user` (`user_id`,`expires_at`),
  CONSTRAINT `fk_password_resets_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_password_resets_expiration` CHECK (`expires_at` > `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(64) NOT NULL,
  `message` VARCHAR(1023) NOT NULL,
  `date_sent` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_messages_user_date` (`user_id`,`date_sent`),
  CONSTRAINT `fk_user_messages_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `wotd` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `word_id` INT UNSIGNED NOT NULL,
  `feature_date` DATE NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wotd_feature_date` (`feature_date`),
  KEY `idx_wotd_word` (`word_id`),
  CONSTRAINT `fk_wotd_word`
    FOREIGN KEY (`word_id`) REFERENCES `words` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `favorites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `word_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_favorites_user_word` (`user_id`,`word_id`),
  KEY `idx_favorites_word` (`word_id`),
  CONSTRAINT `fk_favorites_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorites_word`
    FOREIGN KEY (`word_id`) REFERENCES `words` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `edits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `word_id` INT UNSIGNED NOT NULL,
  `summary` VARCHAR(255) NOT NULL,
  `date_edited` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edits_user` (`user_id`),
  KEY `idx_edits_word` (`word_id`),
  CONSTRAINT `fk_edits_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_edits_word`
    FOREIGN KEY (`word_id`) REFERENCES `words` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `views` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `word_id` INT UNSIGNED NOT NULL,
  `viewed` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_views_word_viewed` (`word_id`,`viewed`),
  KEY `idx_views_user` (`user_id`,`viewed`),
  CONSTRAINT `fk_views_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_views_word`
    FOREIGN KEY (`word_id`) REFERENCES `words` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `meanings_forms` (
  `meaning_id` INT UNSIGNED NOT NULL,
  `form_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`meaning_id`,`form_id`),
  KEY `idx_meanings_forms_form` (`form_id`),
  CONSTRAINT `fk_meanings_forms_meaning`
    FOREIGN KEY (`meaning_id`) REFERENCES `meanings` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meanings_forms_form`
    FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `meanings_labels` (
  `meaning_id` INT UNSIGNED NOT NULL,
  `label_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`meaning_id`,`label_id`),
  KEY `idx_meanings_labels_label` (`label_id`),
  CONSTRAINT `fk_meanings_labels_meaning`
    FOREIGN KEY (`meaning_id`) REFERENCES `meanings` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meanings_labels_label`
    FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `words_forms` (
  `word_id` INT UNSIGNED NOT NULL,
  `form_id` INT UNSIGNED NOT NULL,
  `priority` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`word_id`,`form_id`),
  KEY `idx_words_forms_form` (`form_id`),
  CONSTRAINT `fk_words_forms_word`
    FOREIGN KEY (`word_id`) REFERENCES `words` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_words_forms_form`
    FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_words_forms_priority` CHECK (`priority` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `words_labels` (
  `word_id` INT UNSIGNED NOT NULL,
  `label_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`word_id`,`label_id`),
  KEY `idx_words_labels_label` (`label_id`),
  CONSTRAINT `fk_words_labels_word`
    FOREIGN KEY (`word_id`) REFERENCES `words` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_words_labels_label`
    FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

SET CHARACTER_SET_CLIENT=@old_character_set_client;
SET CHARACTER_SET_RESULTS=@old_character_set_results;
SET collation_connection=@old_collation_connection;
