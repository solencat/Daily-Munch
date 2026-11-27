CREATE DATABASE IF NOT EXISTS dailymunch CHARSET UTF8MB4;

USE dailymunch;

CREATE TABLE IF NOT EXISTS role_access(
    id_role_access INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    name VARCHAR(50) NOT NULL UNIQUE
)ENGINE=INNODB;

-- Les identifiants 1 (admin) et 2 (user) sont ecrits en dur dans le code PHP :
-- la table ne peut pas rester vide, sinon l'inscription viole la cle etrangere.
INSERT IGNORE INTO role_access(id_role_access, name) VALUES (1, 'admin'), (2, 'user');

CREATE TABLE IF NOT EXISTS user_identification(
    id_user_identification INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    height INT NOT NULL,
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

/* ------------------------------------------------------------------
   Suivi des repas
   ------------------------------------------------------------------
   product : extrait local de la base Open Food Facts, importe par
             scripts/import-off.py (voir README-SUIVI-REPAS.md).
             Les valeurs nutritionnelles sont celles pour 100 g.
   log_meal : un repas date (petit-dejeuner, dejeuner, diner, collation,
              ou nom libre) rattache a un utilisateur.
   log_meal_product : les produits d'un repas. kcal_100g est recopie a
              l'ajout pour que les repas passes ne changent pas quand
              Open Food Facts met a jour une fiche.
------------------------------------------------------------------ */

CREATE TABLE IF NOT EXISTS product(
    code VARCHAR(24) PRIMARY KEY NOT NULL,
    name VARCHAR(255) NOT NULL,
    name_normalized VARCHAR(255) NOT NULL,
    brands VARCHAR(255) DEFAULT NULL,
    quantity_label VARCHAR(100) DEFAULT NULL,
    serving_size_label VARCHAR(100) DEFAULT NULL,
    serving_quantity_g DECIMAL(8,2) DEFAULT NULL,
    kcal_100g DECIMAL(7,2) DEFAULT NULL,
    protein_100g DECIMAL(7,2) DEFAULT NULL,
    carbs_100g DECIMAL(7,2) DEFAULT NULL,
    fat_100g DECIMAL(7,2) DEFAULT NULL,
    sugars_100g DECIMAL(7,2) DEFAULT NULL,
    salt_100g DECIMAL(7,2) DEFAULT NULL,
    fiber_100g DECIMAL(7,2) DEFAULT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    nutriscore_grade CHAR(1) DEFAULT NULL,
    categories VARCHAR(255) DEFAULT NULL,
    FULLTEXT KEY ft_product_name (name_normalized),
    KEY idx_product_name_normalized (name_normalized)
)ENGINE=INNODB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS log_meal(
    id_log_meal INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    date_meal DATE NOT NULL,
    name VARCHAR(50) NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    id_user_identification INT NOT NULL,
    CONSTRAINT fk_log_meal_user
        FOREIGN KEY(id_user_identification)
        REFERENCES user_identification(id_user_identification)
        ON DELETE CASCADE,
    KEY idx_log_meal_user_date (id_user_identification, date_meal)
)ENGINE=INNODB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS log_meal_product(
    id_log_meal_product INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    id_log_meal INT NOT NULL,
    product_code VARCHAR(24) DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity_g DECIMAL(8,2) NOT NULL,
    kcal_100g DECIMAL(7,2) NOT NULL,
    kcal DECIMAL(9,2) GENERATED ALWAYS AS (ROUND(quantity_g * kcal_100g / 100, 2)) STORED,
    CONSTRAINT fk_log_meal_product_meal
        FOREIGN KEY(id_log_meal)
        REFERENCES log_meal(id_log_meal)
        ON DELETE CASCADE,
    KEY idx_log_meal_product_meal (id_log_meal)
)ENGINE=INNODB DEFAULT CHARSET=UTF8MB4;
