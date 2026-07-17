CREATE TABLE IF NOT EXISTS `dr_kr_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `section_id` int(10) unsigned DEFAULT NULL,
  `owner` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_dr_kr_item_entity` (`entity_id`),
  UNIQUE KEY `ux_dr_kr_item_section` (`section_id`),
  KEY `ix_dr_kr_item_owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `dr_kr_right` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `edit` int(1) unsigned NOT NULL,
  `blocked` int(1) unsigned NOT NULL,
  `timed` DATETIME DEFAULT NULL,
  `user` int(10) unsigned DEFAULT NULL,
  `group` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `ix_dr_kr_right_user` (`user`),
  KEY `ix_dr_kr_right_group` (`group`),
  KEY `ix_dr_kr_right_timed` (`timed`),
  CONSTRAINT `fk_dr_kr_right_item` FOREIGN KEY (`item_id`) REFERENCES `dr_kr_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;
