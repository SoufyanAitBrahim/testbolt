<?php
include 'includes/config.php';

echo "<h2>Setting up Sushi Website Database</h2>";
echo "<p>This will create all tables and insert sample data...</p>";

try {
    // Create database if it doesn't exist
    $pdo_temp = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
    $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo_temp = null;
    echo "✓ Database created/verified<br>";

    // Now use the database
    $pdo->exec("USE `".DB_NAME."`");

    // Drop existing tables (in reverse order of dependencies)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = [
        'ADMIN_CUSTOMIZED_OFFERS',
        'CLIENT_CUSTOMIZED_OFFERS', 
        'BOOK_EVENT',
        'BOOK_TABLE',
        '`ORDER`',
        'PROPOSE',
        'MEALS',
        'EVENT_BOOKINGS',
        'CLIENTS',
        'RESTAURANTS',
        'ADMINS_OFFERS',
        'CLIENTS_OFFERS',
        'CATEGORIES',
        'ADMINS'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Existing tables dropped<br>";

    // Create tables
    $sql = "
    CREATE TABLE ADMINS (
      `ID_ADMINS` INT AUTO_INCREMENT NOT NULL,
      `FULLNAME` TEXT NOT NULL,
      `EMAIL` TEXT NOT NULL,
      `PASSWORD` TEXT NOT NULL,
      `ROLE` INT NOT NULL,
      `ADDED_DATE` DATE NOT NULL,
      PRIMARY KEY (`ID_ADMINS`)
    ) ENGINE=InnoDB;

    CREATE TABLE ADMINS_OFFERS (
      `ID_ADMINS_OFFERS` INT AUTO_INCREMENT NOT NULL,
      `ADMINS_OFFERS_NAME` TEXT NOT NULL,
      `ADMINS_OFFERS_DESCRIPTION` TEXT NOT NULL,
      `OFFERS_PRICE` DECIMAL(10, 2) NOT NULL,
      PRIMARY KEY (`ID_ADMINS_OFFERS`)
    ) ENGINE=InnoDB;

    CREATE TABLE ADMIN_CUSTOMIZED_OFFERS (
      `ID_ADMINS` INT NOT NULL,
      `ID_MEALS` INT NOT NULL,
      `ID_ADMINS_OFFERS` INT NOT NULL,
      `OFFERS_ADMIN_DATE` DATETIME NOT NULL,
      PRIMARY KEY (`ID_ADMINS`, `ID_MEALS`, `ID_ADMINS_OFFERS`)
    ) ENGINE=InnoDB;

    CREATE TABLE BOOK_EVENT (
      `ID_EVENT_BOOKINGS` INT NOT NULL,
      `ID_CLIENTS` INT NOT NULL,
      `EVENT_BOOK_DATE` DATETIME NOT NULL,
      PRIMARY KEY (`ID_EVENT_BOOKINGS`, `ID_CLIENTS`)
    ) ENGINE=InnoDB;

    CREATE TABLE BOOK_TABLE (
      `ID_CLIENTS` INT NOT NULL,
      `ID_RESTAURANTS` INT NOT NULL,
      `TABLE_BOOK_DATE` DATETIME NOT NULL,
      `FULLNAME` TEXT NOT NULL,
      `PHONE_NUMBER` TEXT NOT NULL,
      `NUMBER_OF_GUESTS` INT NOT NULL,
      `RESTAURANT_LOCATION` TEXT NOT NULL,
      `EVENT_DATE` DATE NOT NULL,
      `EVENT_TIME` TIME NOT NULL,
      PRIMARY KEY (`ID_CLIENTS`, `ID_RESTAURANTS`)
    ) ENGINE=InnoDB;

    CREATE TABLE CATEGORIES (
      `ID_CATEGORIES` INT AUTO_INCREMENT NOT NULL,
      `NAME` TEXT NOT NULL,
      `DESCRIPTION` TEXT NOT NULL,
      PRIMARY KEY (`ID_CATEGORIES`)
    ) ENGINE=InnoDB;

    CREATE TABLE CLIENTS (
      `ID_CLIENTS` INT AUTO_INCREMENT NOT NULL,
      `FULLNAME` TEXT NOT NULL,
      `PHONE_NUMBER` TEXT NOT NULL,
      `EMAIL` TEXT NOT NULL,
      `PASSWORD` TEXT NOT NULL,
      `LOCATION` TEXT NOT NULL,
      PRIMARY KEY (`ID_CLIENTS`)
    ) ENGINE=InnoDB;

    CREATE TABLE CLIENTS_OFFERS (
      `ID_CLIENTS_OFFERS` INT AUTO_INCREMENT NOT NULL,
      `DISCOUNT_PRICE` DECIMAL(10, 2) NOT NULL,
      PRIMARY KEY (`ID_CLIENTS_OFFERS`)
    ) ENGINE=InnoDB;

    CREATE TABLE CLIENT_CUSTOMIZED_OFFERS (
      `ID_CLIENTS_OFFERS` INT NOT NULL,
      `ID_MEALS` INT NOT NULL,
      `ID_CLIENTS` INT NOT NULL,
      `OFFERS_CLIENT_DATE` DATETIME NOT NULL,
      PRIMARY KEY (`ID_CLIENTS_OFFERS`, `ID_MEALS`, `ID_CLIENTS`)
    ) ENGINE=InnoDB;

    CREATE TABLE EVENT_BOOKINGS (
      `ID_EVENT_BOOKINGS` INT AUTO_INCREMENT NOT NULL,
      `FULLNAME` TEXT NOT NULL,
      `PHONE_NUMBER` TEXT NOT NULL,
      `EVENT_TYPE` TEXT NOT NULL,
      `EVENT_DATE` DATE NOT NULL,
      PRIMARY KEY (`ID_EVENT_BOOKINGS`)
    ) ENGINE=InnoDB;

    CREATE TABLE MEALS (
      `ID_MEALS` INT AUTO_INCREMENT NOT NULL,
      `ID_ADMINS` INT NOT NULL,
      `ID_CATEGORIES` INT NOT NULL,
      `NAME` TEXT NOT NULL,
      `DESCRIPTION` TEXT NOT NULL,
      `PRICE` DECIMAL(20, 10) NOT NULL,
      `IMAGE_URL` TEXT NOT NULL,
      PRIMARY KEY (`ID_MEALS`)
    ) ENGINE=InnoDB;

    CREATE TABLE \`ORDER\` (
      `ID_CLIENTS` INT NOT NULL,
      `ID_MEALS` INT NOT NULL,
      `ORDER_TYPE` TINYINT(1) NOT NULL,
      `ORDER_SITUATION` TEXT NOT NULL,
      `PAYMENT_SITUATION` SMALLINT NOT NULL,
      `DATE_ORDER` DATETIME NOT NULL,
      PRIMARY KEY (`ID_CLIENTS`, `ID_MEALS`)
    ) ENGINE=InnoDB;

    CREATE TABLE PROPOSE (
      `ID_MEALS` INT NOT NULL,
      `ID_RESTAURANTS` INT NOT NULL,
      `PROPOSED_DATE` DATE NOT NULL,
      PRIMARY KEY (`ID_MEALS`, `ID_RESTAURANTS`)
    ) ENGINE=InnoDB;

    CREATE TABLE RESTAURANTS (
      `ID_RESTAURANTS` INT AUTO_INCREMENT NOT NULL,
      `NAME` TEXT NOT NULL,
      `ADDRESS` TEXT NOT NULL,
      `PHONE` TEXT NOT NULL,
      `EMAIL` TEXT NOT NULL,
      PRIMARY KEY (`ID_RESTAURANTS`)
    ) ENGINE=InnoDB;
    ";

    // Execute the table creation SQL
    $pdo->exec($sql);
    echo "✓ Tables created<br>";

    // Add Foreign Key Constraints
    $constraints_sql = "
    ALTER TABLE ADMIN_CUSTOMIZED_OFFERS
      ADD CONSTRAINT FK_ADMIN_CU_ADMIN_CUS_ADMINS_O FOREIGN KEY (ID_ADMINS_OFFERS)
      REFERENCES ADMINS_OFFERS (ID_ADMINS_OFFERS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE ADMIN_CUSTOMIZED_OFFERS
      ADD CONSTRAINT FK_ADMIN_CU_ADMIN_CUS_ADMINS FOREIGN KEY (ID_ADMINS)
      REFERENCES ADMINS (ID_ADMINS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE ADMIN_CUSTOMIZED_OFFERS
      ADD CONSTRAINT FK_ADMIN_CU_ADMIN_CUS_MEALS FOREIGN KEY (ID_MEALS)
      REFERENCES MEALS (ID_MEALS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE BOOK_EVENT
      ADD CONSTRAINT FK_BOOK_EVE_BOOK_EVEN_CLIENTS FOREIGN KEY (ID_CLIENTS)
      REFERENCES CLIENTS (ID_CLIENTS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE BOOK_EVENT
      ADD CONSTRAINT FK_BOOK_EVE_BOOK_EVEN_EVENT_BO FOREIGN KEY (ID_EVENT_BOOKINGS)
      REFERENCES EVENT_BOOKINGS (ID_EVENT_BOOKINGS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE BOOK_TABLE
      ADD CONSTRAINT FK_BOOK_TAB_BOOK_TABL_RESTAURA FOREIGN KEY (ID_RESTAURANTS)
      REFERENCES RESTAURANTS (ID_RESTAURANTS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE BOOK_TABLE
      ADD CONSTRAINT FK_BOOK_TAB_BOOK_TABL_CLIENTS FOREIGN KEY (ID_CLIENTS)
      REFERENCES CLIENTS (ID_CLIENTS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE CLIENT_CUSTOMIZED_OFFERS
      ADD CONSTRAINT FK_CLIENT_C_CLIENT_CU_CLIENTS FOREIGN KEY (ID_CLIENTS)
      REFERENCES CLIENTS (ID_CLIENTS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE CLIENT_CUSTOMIZED_OFFERS
      ADD CONSTRAINT FK_CLIENT_C_CLIENT_CU_CLIENTS_ FOREIGN KEY (ID_CLIENTS_OFFERS)
      REFERENCES CLIENTS_OFFERS (ID_CLIENTS_OFFERS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE CLIENT_CUSTOMIZED_OFFERS
      ADD CONSTRAINT FK_CLIENT_C_CLIENT_CU_MEALS FOREIGN KEY (ID_MEALS)
      REFERENCES MEALS (ID_MEALS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE MEALS
      ADD CONSTRAINT FK_MEALS_ADD_MEALS_ADMINS FOREIGN KEY (ID_ADMINS)
      REFERENCES ADMINS (ID_ADMINS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE MEALS
      ADD CONSTRAINT FK_MEALS_CONTAIN_CATEGORI FOREIGN KEY (ID_CATEGORIES)
      REFERENCES CATEGORIES (ID_CATEGORIES) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE \`ORDER\`
      ADD CONSTRAINT FK_ORDER_ORDER_MEALS FOREIGN KEY (ID_MEALS)
      REFERENCES MEALS (ID_MEALS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE \`ORDER\`
      ADD CONSTRAINT FK_ORDER_ORDER2_CLIENTS FOREIGN KEY (ID_CLIENTS)
      REFERENCES CLIENTS (ID_CLIENTS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE PROPOSE
      ADD CONSTRAINT FK_PROPOSE_PROPOSE_RESTAURA FOREIGN KEY (ID_RESTAURANTS)
      REFERENCES RESTAURANTS (ID_RESTAURANTS) ON UPDATE RESTRICT ON DELETE RESTRICT;

    ALTER TABLE PROPOSE
      ADD CONSTRAINT FK_PROPOSE_PROPOSE2_MEALS FOREIGN KEY (ID_MEALS)
      REFERENCES MEALS (ID_MEALS) ON UPDATE RESTRICT ON DELETE RESTRICT;
    ";

    $pdo->exec($constraints_sql);
    echo "✓ Foreign key constraints created<br>";

} catch (PDOException $e) {
    die("ERROR: Could not create database structure. " . $e->getMessage());
}

// Now insert sample data in a separate try-catch block
try {
    echo "<br><strong>Inserting sample data...</strong><br>";

    // Insert sample categories
    $categories = [
        ['Sashimi', 'Fresh raw fish sliced into thin pieces'],
        ['Nigiri', 'Hand-pressed sushi with fish on top of rice'],
        ['Maki', 'Rolled sushi with seaweed on the outside'],
        ['Uramaki', 'Inside-out rolled sushi with rice on the outside'],
        ['Temaki', 'Hand-rolled cone-shaped sushi']
    ];

    $pdo->beginTransaction();

    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT INTO CATEGORIES (NAME, DESCRIPTION) VALUES (?, ?)");
        $stmt->execute($category);
    }
    echo "✓ Categories inserted<br>";

    // Insert sample admin
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO ADMINS (FULLNAME, EMAIL, PASSWORD, ROLE, ADDED_DATE) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Admin User', 'admin@sushi.com', $hashed_password, 1, date('Y-m-d')]);
    $admin_id = $pdo->lastInsertId();
    echo "✓ Admin user created (email: admin@sushi.com, password: admin123)<br>";

    // Insert sample restaurants
    $restaurants = [
        ['Sushi Master Downtown', '123 Main St, City', '555-0101', 'downtown@sushi.com'],
        ['Sushi Master Uptown', '456 High St, City', '555-0202', 'uptown@sushi.com']
    ];

    foreach ($restaurants as $restaurant) {
        $stmt = $pdo->prepare("INSERT INTO RESTAURANTS (NAME, ADDRESS, PHONE, EMAIL) VALUES (?, ?, ?, ?)");
        $stmt->execute($restaurant);
    }
    echo "✓ Restaurants inserted<br>";

    // Insert sample meals
    $meals = [
        [$admin_id, 1, 'Salmon Sashimi', 'Fresh salmon slices', 12.99, 'assets/images/meals/salmon_sashimi.jpg'],
        [$admin_id, 2, 'Tuna Nigiri', 'Premium tuna on rice', 8.99, 'assets/images/meals/tuna_nigiri.jpg'],
        [$admin_id, 3, 'California Roll', 'Crab, avocado, cucumber', 10.99, 'assets/images/meals/california_roll.jpg'],
        [$admin_id, 4, 'Dragon Roll', 'Eel, crab, avocado', 14.99, 'assets/images/meals/dragon_roll.jpg'],
        [$admin_id, 5, 'Spicy Tuna Temaki', 'Spicy tuna in cone', 7.99, 'assets/images/meals/temaki.jpg']
    ];

    foreach ($meals as $meal) {
        $stmt = $pdo->prepare("INSERT INTO MEALS (ID_ADMINS, ID_CATEGORIES, NAME, DESCRIPTION, PRICE, IMAGE_URL) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($meal);
    }
    echo "✓ Meals inserted<br>";

    // Insert sample offers
    $offers = [
        ['Lunch Special', '20% off all rolls from 11am-3pm', 0.20],
        ['Happy Hour', '15% off drinks and appetizers', 0.15],
        ['Family Bundle', '4 rolls for the price of 3', 0.25]
    ];

    foreach ($offers as $offer) {
        $stmt = $pdo->prepare("INSERT INTO ADMINS_OFFERS (ADMINS_OFFERS_NAME, ADMINS_OFFERS_DESCRIPTION, OFFERS_PRICE) VALUES (?, ?, ?)");
        $stmt->execute($offer);
    }
    echo "✓ Offers inserted<br>";

    // Insert sample clients
    $clients = [
        ['John Doe', '555-1001', 'john@example.com', password_hash('john123', PASSWORD_DEFAULT), '123 Client St'],
        ['Jane Smith', '555-1002', 'jane@example.com', password_hash('jane123', PASSWORD_DEFAULT), '456 Customer Ave']
    ];

    foreach ($clients as $client) {
        $stmt = $pdo->prepare("INSERT INTO CLIENTS (FULLNAME, PHONE_NUMBER, EMAIL, PASSWORD, LOCATION) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($client);
    }
    echo "✓ Sample clients created<br>";

    $pdo->commit();
    echo "<br><strong>✅ Database setup completed successfully!</strong><br>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Visit the main website</a></li>";
    echo "<li><a href='login.php'>Login as admin</a> (email: admin@sushi.com, password: admin123)</li>";
    echo "<li><a href='login.php'>Login as client</a> (email: john@example.com, password: john123)</li>";
    echo "</ul>";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("ERROR: Could not insert sample data. " . $e->getMessage());
}
