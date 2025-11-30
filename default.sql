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
    quantity INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM("in_cart", "purchased", "cancelled"),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (plate_id) REFERENCES Plates(id)
) ENGINE=InnoDB;

CREATE TABLE DonatedOrders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    quantity_available INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(id)
) ENGINE=InnoDB;

CREATE TABLE DonatedOrderClaims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donated_order_id INT NOT NULL,
    quantity INT NOT NULL,
    in_need_user_id INT NOT NULL,
    status ENUM("in_cart", "claimed", "cancelled"),
    FOREIGN KEY (donated_order_id) REFERENCES DonatedOrders(id),
    FOREIGN KEY (in_need_user_id) REFERENCES Users(id)
) ENGINE=InnoDB;

/*
Test data 
This is not for production
Ideally, this would be separated out into something like test.sql
*/

-- Create a test account for each role 
-- Username and password is simply the role
INSERT INTO Users (role, username, password_hash, name, address, phone) 
VALUES ("admin", "admin", SHA2("admin", 256), "Mr. Administrator", "100 Admin St", "555-0001"),
("restaurant", "restaurant", SHA2("restaurant", 256), "Mr. Restaurant", "200 Food Ave", "555-1111"),
("customer", "customer", SHA2("customer", 256), "Mr. Customer", "201 Hungry Ave", "555-1112"),
("donor", "donor", SHA2("donor", 256), "Mr. Donor", "400 Charity Ln", "555-3333"),
("in_need", "in_need", SHA2("in_need", 256), "Mr. In Need", "500 Shelter St", "555-4444");

-- Give Mr. Customer a credit card (but not Mr. Donor)
INSERT INTO CreditCards (user_id, card_number, card_expiry, card_cvv)
VALUES
    (3, "4111111111115555", "12/30", "123");

-- Give Mr. Restaurant 4 dishes (but 1 is expired)
INSERT INTO Plates (owner_id, description, available_from, available_to, price, quantity_available)
VALUES
    (2, "Spaghetti Carbonara", "2024-01-01 11:00:00", "2024-01-01 16:00:00", 12.50, 5),
    (2, "Grilled Chicken Meal", "2024-01-02 11:00:00", "2026-01-02 18:00:00", 10.00, 10),
    (2, "Vegan Buddha Bowl", "2024-01-01 10:00:00", "2026-01-01 20:00:00", 11.75, 15),
    (2, "Beef Stir Fry", "2024-01-03 09:00:00", "2026-01-03 21:00:00", 13.00, 20);

