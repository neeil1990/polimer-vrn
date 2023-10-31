CREATE TABLE IF NOT EXISTS `wbs24_ozonexport_offers_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `offer_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `price` int(11) NOT NULL,
  `normal_export_time` int(11) NOT NULL,
  `null_export_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_id_offer_id` (`profile_id`,`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
