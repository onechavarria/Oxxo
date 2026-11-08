<?php
declare(strict_types=1);

function branch(): array
{
    return query('SELECT * FROM branches WHERE id=?', [$_SESSION['branch'] ?? 1])->fetch();
}

function catalog(int $branch): array
{
    return query('SELECT p.*, i.stock FROM products p JOIN inventory i ON i.product_id=p.id WHERE i.branch_id=? ORDER BY p.id', [$branch])->fetchAll();
}

function basket(array $catalog): array
{
    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    foreach ($catalog as $product) {
        if (isset($cart[$product['id']])) {
            $items[] = $product + ['quantity'=>$cart[$product['id']]];
        }
    }
    return $items;
}

function totals(array $items, string $method, string $coupon): array
{
    $subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items));
    $discount = $coupon === 'BIENVENIDA10' ? intdiv($subtotal, 10) : 0;
    $shipping = $method === 'delivery' ? 1990 : 0;
    return [$subtotal, $shipping, $discount, $subtotal + $shipping - $discount];
}

function orderItems(int $id): array
{
    return query('SELECT * FROM order_items WHERE order_id=? ORDER BY product_id', [$id])->fetchAll();
}

function receipt(array $order, array $customer): void
{
    global $config;
    $smtp = $config['smtp'];
    $status = 'disabled';
    if ($smtp['host'] !== '') {
        try {
            require_once dirname(__DIR__) . '/vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->Port = (int)$smtp['port'];
            $mail->SMTPAuth = $smtp['user'] !== '';
            $mail->Username = $smtp['user'];
            $mail->Password = $smtp['password'];
            $mail->SMTPSecure = $smtp['encryption'];
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($smtp['from'], 'Tienda Demo');
            $mail->addAddress($customer['email'], $customer['name']);
            $mail->Subject = 'Tienda Demo · Comprobante ' . $order['code'];
            $rows = '';
            $plain = '';
            foreach (orderItems((int)$order['id']) as $item) {
                $cid = 'producto-' . $item['product_id'];
                $image = PUBLIC_DIR . '/assets/' . basename($item['image']);
                $mail->addEmbeddedImage($image, $cid, basename($image), 'base64', 'image/png');
                $lineTotal = clp($item['price'] * $item['quantity']);
                $rows .= '<tr><td style="padding:10px"><img width="64" src="cid:' . $cid . '" alt="' . h($item['name'])
                    . '"></td><td><strong>' . h($item['name']) . '</strong><br>Cantidad: ' . h($item['quantity'])
                    . '</td><td style="text-align:right">' . $lineTotal . '</td></tr>';
                $plain .= $item['quantity'] . ' × ' . $item['name'] . ' · ' . $lineTotal . "\n";
            }
            $mail->isHTML(true);
            $mail->Body = '<div style="max-width:620px;margin:auto;font:16px Arial,sans-serif;color:#261719">'
                . '<div style="background:#ae0626;color:#fff;padding:24px"><strong>TIENDA DEMO</strong><h1>Pago de prueba confirmado</h1></div>'
                . '<p>Hola ' . h($customer['name']) . ', tu pedido <strong>' . h($order['code']) . '</strong> se registró correctamente.</p>'
                . '<table width="100%" cellpadding="8" cellspacing="0">' . $rows . '</table><hr>'
                . '<p>Subtotal: ' . clp((int)$order['subtotal']) . '<br>Despacho: ' . clp((int)$order['shipping'])
                . '<br>Descuento: ' . clp((int)$order['discount']) . '</p>'
                . '<p><strong>Total: ' . clp((int)$order['total']) . ' CLP</strong></p><p>'
                . ($order['method'] === 'pickup' ? 'Retiro: ' : 'Despacho: ') . h($order['address'])
                . '</p><p>Proyecto independiente de portafolio. Comprobante de demostración.</p></div>';
            $mail->AltBody = 'Pago de prueba confirmado · ' . $order['code'] . "\n" . $plain
                . 'Total: ' . clp((int)$order['total']) . " CLP\n" . $order['address'];
            $mail->send();
            $status = 'sent';
        } catch (Throwable $error) {
            error_log('Receipt failure for ' . $order['code'] . ' (' . get_class($error) . ')');
            $status = 'failed';
        }
    }
    query('UPDATE orders SET mail_status=? WHERE id=?', [$status,$order['id']]);
}

function checkout(array $customer): never
{
    $key = post('checkout_key');
    if (!preg_match('/^[a-f0-9]{48}$/D', $key)) {
        throw new DomainException('Recarga el carrito antes de confirmar.');
    }
    $existing = query('SELECT code FROM orders WHERE user_id=? AND checkout_key=?', [$customer['id'],$key])->fetchColumn();
    if ($existing) {
        go('order', ['code'=>$existing]);
    }
    if (!hash_equals($_SESSION['checkout_key'] ?? '', $key)) {
        throw new DomainException('El carrito cambió. Revisa el total antes de confirmar.');
    }
    limitAuth('checkout');
    $branch = branch();
    $method = post('method');
    if (!in_array($method, ['pickup','delivery'], true)) {
        throw new DomainException('Selecciona un método de entrega.');
    }
    $address = $method === 'pickup' ? $branch['address'] : post('address');
    if (strlen($address) < 6 || strlen($address) > 240) {
        throw new DomainException('Indica una dirección válida con comuna.');
    }
    $code = 'OXX-' . strtoupper(bin2hex(random_bytes(6)));
    db()->beginTransaction();
    try {
        $items = basket(catalog((int)$branch['id']));
        if (!$items) {
            throw new DomainException('Agrega productos al carrito.');
        }
        foreach ($items as $item) {
            $updated = query('UPDATE inventory SET stock=stock-? WHERE branch_id=? AND product_id=? AND stock>=?',
                [$item['quantity'],$branch['id'],$item['id'],$item['quantity']]);
            if ($updated->rowCount() !== 1) {
                throw new DomainException('Stock insuficiente para ' . $item['name'] . '. Actualiza tu carrito.');
            }
        }
        [$subtotal,$shipping,$discount,$total] = totals($items, $method, $_SESSION['coupon'] ?? '');
        query('INSERT INTO orders(user_id,branch_id,code,checkout_key,method,address,subtotal,shipping,discount,total) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$customer['id'],$branch['id'],$code,$key,$method,$address,$subtotal,$shipping,$discount,$total]);
        $id = (int)db()->lastInsertId();
        foreach ($items as $item) {
            query('INSERT INTO order_items VALUES (?,?,?,?,?,?)',
                [$id,$item['id'],$item['name'],$item['price'],$item['quantity'],$item['image']]);
        }
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    unset($_SESSION['cart'], $_SESSION['coupon'], $_SESSION['checkout_key']);
    session_write_close();
    receipt(compact('id','code','method','address','subtotal','shipping','discount','total'), $customer);
    go('order', ['code'=>$code]);
}
