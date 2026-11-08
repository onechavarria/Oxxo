CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
 email VARCHAR(254) NOT NULL UNIQUE, password_hash VARCHAR(255),
 role ENUM('customer','admin') NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE identities (
 provider VARCHAR(20) NOT NULL, subject VARCHAR(128) NOT NULL, user_id INT UNSIGNED NOT NULL,
 PRIMARY KEY(provider,subject), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE branches (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, address VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, category VARCHAR(40) NOT NULL,
 detail VARCHAR(150) NOT NULL, price INT UNSIGNED NOT NULL, image VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE inventory (
 branch_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, stock INT UNSIGNED NOT NULL,
 PRIMARY KEY(branch_id,product_id), FOREIGN KEY(branch_id) REFERENCES branches(id),
 FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, branch_id INT UNSIGNED NOT NULL,
 code VARCHAR(24) NOT NULL UNIQUE, checkout_key CHAR(48) NOT NULL UNIQUE,
 method ENUM('pickup','delivery') NOT NULL, address VARCHAR(240) NOT NULL,
 subtotal INT UNSIGNED NOT NULL, shipping INT UNSIGNED NOT NULL, discount INT UNSIGNED NOT NULL,
 total INT UNSIGNED NOT NULL, status ENUM('Confirmado','En preparación','Listo','Entregado') NOT NULL DEFAULT 'Confirmado',
 mail_status ENUM('pending','sent','failed','disabled') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(branch_id) REFERENCES branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE order_items (
 order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL, price INT UNSIGNED NOT NULL, quantity INT UNSIGNED NOT NULL,
 image VARCHAR(80) NOT NULL, PRIMARY KEY(order_id,product_id),
 FOREIGN KEY(order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE login_limits (
 bucket CHAR(64) PRIMARY KEY, attempts INT UNSIGNED NOT NULL, window_start INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO branches(name,address) VALUES ('Providencia','Av. Providencia 1208, Santiago'),('Ñuñoa','Av. Irarrázaval 3050, Santiago');
INSERT INTO products(name,category,detail,price,image) VALUES
 ('Café latte','Café','Vaso caliente · 300 ml',1790,'latte.png'),
 ('Papas crujientes','Snacks','Sal de mar · 120 g',1490,'chips.png'),
 ('Bebida cola','Bebidas','Lata helada · 350 ml',1190,'cola.png'),
 ('Sándwich de pollo','Comida','Pollo y queso · 180 g',3290,'sandwich.png'),
 ('Galletas de chocolate','Snacks','Chips de chocolate · 90 g',1290,'cookies.png'),
 ('Agua mineral','Bebidas','Sin gas · 600 ml',990,'water.png'),
 ('Café americano','Café','Vaso caliente · 300 ml',1490,'americano.png'),
 ('Wrap vegetariano','Comida','Vegetales frescos · 200 g',3490,'wrap.png');
INSERT INTO inventory SELECT branches.id,products.id,12+MOD(branches.id*products.id,17) FROM branches CROSS JOIN products;
