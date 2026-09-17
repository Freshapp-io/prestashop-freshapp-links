CREATE TABLE IF NOT EXISTS `PREFIX_freshapplinks_list` (
  `id_freshapplinks_list` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hooks`                 VARCHAR(255) NOT NULL DEFAULT '',
  `position`              INT NOT NULL DEFAULT 0,
  `active`                TINYINT(1) NOT NULL DEFAULT 1,
  `icon_color`            VARCHAR(7) NOT NULL DEFAULT '',
  `link_color`            VARCHAR(7) NOT NULL DEFAULT '',
  `font_weight`           VARCHAR(3) NOT NULL DEFAULT '',
  `font_family`           VARCHAR(255) NOT NULL DEFAULT '',
  `text_transform`        VARCHAR(20) NOT NULL DEFAULT '',
  `font_size`             VARCHAR(20) NOT NULL DEFAULT '',
  `layout`                VARCHAR(10) NOT NULL DEFAULT 'column',
  `show_title`            TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_freshapplinks_list`),
  INDEX (`position`),
  INDEX (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_freshapplinks_list_lang` (
  `id_freshapplinks_list` INT UNSIGNED NOT NULL,
  `id_lang`               INT UNSIGNED NOT NULL,
  `name`                  VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_freshapplinks_list`, `id_lang`),
  INDEX (`id_lang`),
  CONSTRAINT `fk_freshapplinks_list_lang_list`
    FOREIGN KEY (`id_freshapplinks_list`)
    REFERENCES `PREFIX_freshapplinks_list` (`id_freshapplinks_list`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_freshapplinks_link` (
  `id_freshapplinks_link` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_freshapplinks_list` INT UNSIGNED NOT NULL,
  `position`              INT NOT NULL DEFAULT 0,
  `active`                TINYINT(1) NOT NULL DEFAULT 1,
  `target_type`           ENUM('product','category','cms','custom') NOT NULL DEFAULT 'custom',
  `target_id`             INT UNSIGNED NULL,
  `link_target`           VARCHAR(10) NOT NULL DEFAULT '_self',
  `icon_class`            VARCHAR(100) NOT NULL DEFAULT '',
  `icon_image`            VARCHAR(255) NOT NULL DEFAULT '',
  `style_overrides`       TEXT NOT NULL,
  PRIMARY KEY (`id_freshapplinks_link`),
  INDEX (`id_freshapplinks_list`),
  INDEX (`position`),
  INDEX (`active`),
  CONSTRAINT `fk_freshapplinks_link_list`
    FOREIGN KEY (`id_freshapplinks_list`)
    REFERENCES `PREFIX_freshapplinks_list` (`id_freshapplinks_list`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_freshapplinks_link_lang` (
  `id_freshapplinks_link` INT UNSIGNED NOT NULL,
  `id_lang`               INT UNSIGNED NOT NULL,
  `label`                 VARCHAR(255) NOT NULL DEFAULT '',
  `custom_url`            VARCHAR(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_freshapplinks_link`, `id_lang`),
  INDEX (`id_lang`),
  CONSTRAINT `fk_freshapplinks_link_lang_link`
    FOREIGN KEY (`id_freshapplinks_link`)
    REFERENCES `PREFIX_freshapplinks_link` (`id_freshapplinks_link`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
