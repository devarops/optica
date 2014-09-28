<?php
	require_once('../../db_connect.php');

	echo 'Updating datbase ', $db_name, PHP_EOL, str_repeat('=', 17 + strlen($db_name)), PHP_EOL, PHP_EOL;

	/* Create new tables */
	$new_tables = [
		'image' => "CREATE TABLE `image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `path` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`),
  UNIQUE KEY `path_2` (`path`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `image_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8",

		'tag' => "CREATE TABLE `tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8",

		'ct_image_tag' => "CREATE TABLE `ct_image_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_id` (`image_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `ct_image_tag_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ct_image_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8",

		'user' => " CREATE TABLE `user` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `password` char(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `created` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8"
	];

	foreach($new_tables as $name => $query) {
		if($db->query($query)) {
			echo '[SUCCESS] Created table `', $name, '`', PHP_EOL;
		} else {
			echo '[ERROR] Could not create table `', $name, '`: ', join(' ', $db->errorInfo()), PHP_EOL;
		}
	}

	$queries = [
		/* Lens table
	 	 * 1. Add column `ignored`
	 	 * 2. Flag all current entries ignored
	 	 * 3. Insert new set of lenses (need default prices!)
	 	 */
		'Adding `lens`.`ignored`' => "ALTER TABLE `lens` ADD COLUMN `ignored` tinyint(1) DEFAULT '0'",
		'Flagging old data' => "UPDATE `lens` SET `ignored` = 1",
		//'Insert new data' => "INSERT INTO `lens` (name, default_price) VALUES (), ()",

		/* Patent table
	 	 * 1. Add `merged_with` column
	 	 * 2. Add foreign key constraint
	 	 */
		'Adding `patient`.`merged_with`' => "ALTER TABLE `patient` ADD COLUMN `merged_with` int(11) DEFAULT NULL",
		'Creating foreign key constraint' => "ALTER TABLE `patient` ADD FOREIGN KEY(`merged_with`) REFERENCES patient(id)",

		/* Remission note
		 * 1. Salesperson ID
		 * 2. Commission
		 * 3. Commission claimed TS
		 * 4. Foreign key constraint
		 */
		'Adding salesperson_id field' => "ALTER TABLE `remission_note` ADD COLUMN `salesperson_id` tinyint(3) unsigned DEFAULT NULL",
		'Adding commission field' => "ALTER TABLE `remission_note` ADD COLUMN `commission` float DEFAULT NULL",
		'Adding commission TS field' => "ALTER TABLE `remission_note` ADD COLUMN `commission_claimed` datetime DEFAULT NULL",
		'Creating foreign key constraint' => "ALTER TABLE `remission_note` ADD FOREIGN KEY(`salesperson_id`) REFERENCES user(id)",
	];

	foreach($queries as $desc => $query) {
		if($db->query($query)) {
			echo '[SUCCESS] ', $desc, PHP_EOL;
		} else {
			echo '[ERROR] Could not ', $desc, ': ', join(' ', $db->errorInfo()), PHP_EOL;
		}
	}


	echo PHP_EOL, PHP_EOL, '[DONE] Update completed.', PHP_EOL;
?>
