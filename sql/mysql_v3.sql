/**
 * Ajout des nouveaux champs utilisateurs
 */

ALTER TABLE  `users_infos` ADD  `city` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD  `country` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD  `institution` VARCHAR( 500 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD  `activity_domain` VARCHAR( 500 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
ADD  `research_domain` VARCHAR( 500 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

/**
 * Suppression du champ location
 */

ALTER TABLE `users_infos`
  DROP `location`;
