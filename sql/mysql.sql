/**
 * Script de creation des tables pour la gestion des utilisateurs du module
 * silex-simpleuser adapte pour la plateforme cortext et le module cortext-auth
 */

/**
 * Creation de la table utilisateurs 
 * 
 * La table contient les informations d'identification de chaque utilisateurs,
 * ainsi que les rôles attribués à l'utilisateur
 */
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL DEFAULT '',
  `password` VARCHAR(255) NOT NULL DEFAULT '',
  `salt` VARCHAR(255) NOT NULL DEFAULT '',
  `roles` VARCHAR(255) NOT NULL DEFAULT '',
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `time_created` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/**
 * Creation de la table contenant les informations utilisateurs
 *
 * 
 */
CREATE TABLE IF NOT EXISTS `users_infos` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `user_description` TEXT CHARACTER SET utf8 DEFAULT NULL,
  `user_location` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `user_website` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL,
  `user_birthdate` date DEFAULT NULL,
  `user_last_connexion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
