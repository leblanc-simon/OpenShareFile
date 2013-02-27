-- For MySQL
CREATE TABLE `file` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `upload_id` INT UNSIGNED NOT NULL,
    `slug` VARCHAR( 255 ) NOT NULL ,
    `file` VARCHAR( 255 ) NOT NULL ,
    `filename` VARCHAR( 255 ) NOT NULL ,
    `filesize` BIGINT UNSIGNED NOT NULL ,
    `created_at` DATETIME NOT NULL ,
    `is_deleted` BOOLEAN NOT NULL ,
    UNIQUE (
        `slug`
    ) ,
    KEY `upload_id_not_deleted` (
        `upload_id`,
        `is_deleted`
    )
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `upload` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `member_id` INT UNSIGNED NULL DEFAULT NULL ,
    `slug` VARCHAR( 255 ) NOT NULL ,
    `lifetime` INT UNSIGNED NOT NULL ,
    `passwd` VARCHAR( 255 ) NOT NULL ,
    `crypt` BOOLEAN NOT NULL ,
    `created_at` DATETIME NOT NULL ,
    `is_deleted` BOOLEAN NOT NULL ,
    UNIQUE (
        `slug`
    ) ,
    KEY `slug_not_deleted` (
        `slug`,
        `is_deleted`
    )
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `member` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `email` VARCHAR( 255 ) NOT NULL ,
    `passwd` VARCHAR( 255 ) NOT NULL ,
    `name` VARCHAR( 255 ) NOT NULL ,
    `prefs` TEXT NULL,
    `created_at` DATETIME NOT NULL ,
    UNIQUE (
        `email`
    )
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_bin;