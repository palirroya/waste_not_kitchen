DROP DATABASE IF EXISTS wnk;
CREATE DATABASE wnk;
USE wnk;

CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM("admin", "restaurant", "customer", "donor", "in_need"),
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(64) NOT NULL,
    name VARCHAR(50),
    address VARCHAR(50),
    phone VARCHAR(17)
) ENGINE=InnoDB;

CREATE TABLE CreditCards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_number VARCHAR(19) NOT NULL,
    card_expiry VARCHAR(5) NOT NULL,
    card_cvv VARCHAR(4) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id)
) ENGINE=InnoDB;

CREATE TABLE Plates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    description VARCHAR(256),
    available_from DATETIME NOT NULL,
    available_to DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity_available INT NOT NULL,
    FOREIGN KEY (owner_id) REFERENCES Users(id)
) ENGINE=InnoDB;

CREATE TABLE Orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plate_id INT NOT NULL,
    is_donation BOOLEAN NOT NULL,
    reserved_id INT,
    quantity INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status enum("in_cart", "purchased", "cancelled"),
    FOREIGN KEY (reserved_id) REFERENCES Users(id),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (plate_id) REFERENCES Plates(id)
) ENGINE=InnoDB;

INSERT INTO Users (role, username, password_hash, name) VALUES ("admin", "admin", SHA2("admin", 256), "Admin");