CREATE DATABASE IF NOT EXISTS dailymunch CHARSET UTF8MB4;

USE dailymunch;

CREATE TABLE IF NOT EXISTS role_access(
    id_role_access INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    name VARCHAR(50) NOT NULL UNIQUE
)ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS user_identification(
    id_user_identification INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    size INT NOT NULL,
    weight INT NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    pass VARCHAR(100) NOT NULL,
    id_role_access INT NOT NULL,
    CONSTRAINT fk_user_identification_role_access
	   FOREIGN KEY(id_role_access)
	    REFERENCES role_access(id_role_access)
)ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS log_weight(
    id_log_weight INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    weight INT NOT NULL,
    id_user_identification INT NOT NULL,
    CONSTRAINT fk_record_user
   	    FOREIGN KEY(id_user_identification)
		REFERENCES user_identification(id_user_identification)
)ENGINE=INNODB;
