/**
 * Script de creation de la table d'infos complementaitres utilisateurs du module
 * silex-simpleuser adapte pour la plateforme cortext et le module cortext-auth
 *
 * Inclus la migration en peuplant la nouvelle table
 */

/**
 * Creation de la table contenant les informations utilisateurs
 */
CREATE TABLE IF NOT EXISTS `users_infos` (
  `user_id` INT(11) UNSIGNED NOT NULL,
  `description` TEXT DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `last_connexion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

/**
 * Peuplement de la table users_infos avec les ids de la table users
 */
INSERT IGNORE INTO `users_infos`
SELECT `id` AS `user_id`, 
  NULL AS `description`, 
  NULL AS `location`, 
  NULL AS `website`, 
  NULL AS `birthdate`, 
  NULL AS `last_connexion`
FROM `users`
