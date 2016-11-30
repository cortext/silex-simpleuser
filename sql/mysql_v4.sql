/**
 *  Added authorizations field for User
 */

ALTER TABLE  `users_infos` 
ADD  `authorizations` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'json formatted list of authorization, for exemple URI that the user can access';