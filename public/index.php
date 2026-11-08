<?php
declare(strict_types=1);
define('PUBLIC_DIR', __DIR__);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/store.php';
require dirname(__DIR__) . '/app/actions.php';
require dirname(__DIR__) . '/app/oauth.php';

$page = input('p') ?: 'home';
try {
    $customer = user();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePost($customer);
    }
    if (in_array($page, ['oauth','callback'], true)) {
        oauth(input('provider'), $page === 'callback');
    }
    if (!$customer && in_array($page, ['account','order','admin'], true)) {
        go('login');
    }
    if ($page === 'admin' && $customer['role'] !== 'admin') {
        http_response_code(403);
        exit('Acceso restringido.');
    }
    $branch = branch();
    $branches = query('SELECT * FROM branches ORDER BY id')->fetchAll();
    $products = catalog((int)$branch['id']);
    $items = basket($products);
    $count = array_sum(array_column($items, 'quantity'));
    $notice = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    $order = $page === 'order' ? query('SELECT o.*, b.name AS branch_name FROM orders o JOIN branches b ON b.id=o.branch_id WHERE o.code=? AND o.user_id=?',
        [input('code'),$customer['id']])->fetch() : null;
    if (($page === 'order' && !$order) || !in_array($page, ['home','cart','login','register','account','order','admin'], true)) {
        http_response_code(404);
        $page = 'missing';
    }
} catch (DomainException $error) {
    flash($error->getMessage(), true);
    go(match(post('action')) {
        'checkout','coupon','quantity'=>'cart', 'stock','status','product'=>'admin',
        'register'=>'register', 'add','branch'=>'home', default=>'login',
    });
} catch (Throwable $error) {
    error_log('Store failure: ' . get_class($error) . ' / ' . $error->getCode());
    http_response_code(503);
    exit('La tienda no está disponible. Revisa la configuración y el registro del servidor.');
}

require dirname(__DIR__) . '/app/views.php';
