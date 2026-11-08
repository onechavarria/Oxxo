<?php
declare(strict_types=1);

function handlePost(?array $customer): never
{
    if (!hash_equals(token(), post('csrf'))) {
        http_response_code(403);
        exit('Sesión vencida. Vuelve a cargar la página.');
    }
    $action = post('action');
    switch ($action) {
        case 'branch':
            $id = (int)post('branch');
            if (query('SELECT id FROM branches WHERE id=?', [$id])->fetchColumn()) {
                $_SESSION['branch'] = $id;
                unset($_SESSION['checkout_key']);
                flash('Sucursal actualizada. Las cantidades se validarán al comprar.');
            }
            go('home');
        case 'add':
        case 'quantity':
            $branch = branch();
            $id = (int)post('id');
            $count = $action === 'add' ? 1 + ($_SESSION['cart'][$id] ?? 0) : (int)post('quantity');
            $stock = (int)query('SELECT stock FROM inventory WHERE branch_id=? AND product_id=?', [$branch['id'],$id])->fetchColumn();
            if ($count < 0 || $count > min(99,$stock)) {
                throw new DomainException('Esa cantidad supera el stock disponible.');
            }
            if ($count === 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id] = $count;
            }
            unset($_SESSION['checkout_key']);
            if ($action === 'add') {
                flash('Producto agregado a tu bolsa.');
                header('Location: ' . url('home', ['category'=>post('category'),'q'=>post('q')]) . '#catalogo', true, 303);
                exit;
            }
            go('cart');
        case 'coupon':
            $coupon = strtoupper(post('coupon'));
            if (!in_array($coupon, ['', 'BIENVENIDA10'], true)) {
                throw new DomainException('Cupón inválido.');
            }
            $_SESSION['coupon'] = $coupon;
            unset($_SESSION['checkout_key']);
            flash($coupon === '' ? 'Cupón eliminado.' : 'Cupón aplicado. Ahorras un 10%.');
            go('cart');
        case 'login':
        case 'register':
            limitAuth($action);
            $email = strtolower(post('email'));
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>254 || strlen($password)>72 || str_contains($password,"\0")) {
                throw new DomainException('Revisa el correo y la contraseña.');
            }
            $account = query('SELECT * FROM users WHERE email=?', [$email])->fetch();
            if ($action === 'login') {
                $hash = $account['password_hash'] ?? '';
                if (!$hash || !password_verify($password, $hash)) {
                    throw new DomainException('Correo o contraseña incorrectos.');
                }
                signIn((int)$account['id']);
            } else {
                $name = post('name');
                if ($name === '' || strlen($name)>100 || strlen($password)<10) {
                    throw new DomainException('Escribe tu nombre y una contraseña de al menos 10 caracteres.');
                }
                if ($account) {
                    throw new DomainException('El correo ya tiene una cuenta.');
                }
                query('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)', [$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
                signIn((int)db()->lastInsertId());
            }
            go(empty($_SESSION['cart']) ? 'account' : 'cart');
        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            go('home');
        case 'checkout':
            if (!$customer) {
                go('login');
            }
            checkout($customer);
        case 'stock':
        case 'status':
        case 'product':
            if (($customer['role'] ?? '') !== 'admin') {
                http_response_code(403);
                exit('Acceso restringido.');
            }
            if ($action === 'stock') {
                $stock = filter_var(post('stock'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>99999]]);
                if ($stock === false) {
                    throw new DomainException('Ingresa un stock entre 0 y 99.999.');
                }
                query('UPDATE inventory SET stock=? WHERE branch_id=? AND product_id=?', [$stock,branch()['id'],(int)post('id')]);
            } elseif ($action === 'product') {
                $name = post('name');
                $detail = post('detail');
                $price = filter_var(post('price'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>999999]]);
                if ($name === '' || strlen($name)>120 || strlen($detail)>150 || $price === false) {
                    throw new DomainException('Revisa el nombre, la descripción y el precio del producto.');
                }
                query('UPDATE products SET name=?,detail=?,price=? WHERE id=?', [$name,$detail,$price,(int)post('id')]);
            } else {
                $status = post('status');
                if (!in_array($status, orderStatuses(), true)) {
                    throw new DomainException('Estado inválido.');
                }
                query('UPDATE orders SET status=? WHERE id=?', [$status,(int)post('id')]);
            }
            flash('Cambios guardados.');
            go('admin');
        default:
            http_response_code(400);
            exit('Acción inválida.');
    }
}

function orderStatuses(): array
{
    return ['Confirmado','En preparación','Listo','Entregado'];
}
