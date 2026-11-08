<!doctype html>
<html lang="es-CL">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#a60f28">
<meta name="description" content="Tienda de conveniencia de demostración. Café, snacks y más.">
<title>Tienda OXXO · Demo Chile</title>
<link rel="icon" href="assets/logo.webp">
<link rel="stylesheet" href="assets/store.css">
<script src="assets/store.js" defer></script>
</head>
<body>
<a href="#principal" class="skip">Ir al contenido</a>
<header class="header">
    <div class="wrap top">
        <a href="<?= url() ?>" class="brand" aria-label="Tienda demo, inicio"><img src="assets/logo.webp" width="88" height="45" alt="OXXO"><span>CHILE<br><b>TIENDA DEMO</b></span></a>
        <form method="post" action="./" class="branch">
            <?= fields('branch') ?>
            <label for="branch">Sucursal</label>
            <select id="branch" name="branch">
                <?php foreach ($branches as $option): ?>
                <option value="<?= h($option['id']) ?>" <?= $option['id'] == $branch['id'] ? 'selected' : '' ?>><?= h($option['name']) ?></option>
                <?php endforeach ?>
            </select>
            <button class="quiet" aria-label="Aplicar sucursal">Ir</button>
        </form>
        <nav class="nav" aria-label="Cuenta y carrito">
            <a href="<?= url($customer ? 'account' : 'login') ?>"><?= $customer ? 'Mi cuenta' : 'Ingresar' ?></a>
            <a class="bag" href="<?= url('cart') ?>">Mi bolsa <span><?= $count ?></span></a>
        </nav>
    </div>
</header>
<main class="wrap" id="principal">
<?php if ($notice): ?><p class="notice <?= $notice[1] ? 'error' : '' ?>" role="status"><?= h($notice[0]) ?></p><?php endif ?>
<?php if ($page === 'home'):
    $category = input('category');
    $search = substr(input('q'), 0, 100);
    $categories = [''=>'Todo','Café'=>'Café','Snacks'=>'Snacks','Bebidas'=>'Bebidas','Comida'=>'Comida'];
    $results = array_filter($products, fn($product) => ($category === '' || $product['category'] === $category)
        && ($search === '' || preg_match('/' . preg_quote($search,'/') . '/iu', $product['name'] . ' ' . $product['detail'])));
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">TU TIENDA DE CONVENIENCIA</p>
        <h1>Tu antojo,<br><em>a la vuelta.</em></h1>
        <p>Café, snacks y todo para tu pausa. Elige tu sucursal y arma tu pedido en minutos.</p>
        <a class="button yellow" href="#catalogo">Explorar catálogo <span aria-hidden="true">↗</span></a>
    </div>
    <div class="hero-art" aria-hidden="true">
        <div class="orbit"></div>
        <img class="hero-chips" src="assets/chips.png" alt="" width="400" height="330">
        <img class="hero-coffee" src="assets/latte.png" alt="" width="400" height="330">
        <span class="hero-stamp">LA PAUSA<br>QUE QUERÍAS</span>
    </div>
</section>
<div class="perks">
    <div><span>01</span><b>Retiro en tienda</b><small>Elige tu sucursal</small></div>
    <div><span>02</span><b>Despacho demo</b><small>Recíbelo a domicilio</small></div>
    <div><span>03</span><b>Precios en CLP</b><small>Compra en pesos chilenos</small></div>
</div>
<section id="catalogo" class="catalog">
    <div class="catalog-head"><div><p class="eyebrow accent">DISPONIBLE EN <?= h($branch['name']) ?></p><h2>Elige tu antojo</h2></div>
    <form class="search" method="get" action="./#catalogo">
        <input type="hidden" name="category" value="<?= h($category) ?>">
        <input type="search" name="q" value="<?= h($search) ?>" maxlength="100" aria-label="Buscar productos" placeholder="Buscar productos">
        <button class="quiet">Buscar</button>
    </form></div>
    <nav class="filters" aria-label="Categorías">
        <?php foreach ($categories as $key=>$label): ?>
        <a <?= $category === $key ? 'aria-current="true"' : '' ?> href="<?= h(url('home',['category'=>$key,'q'=>$search])) ?>#catalogo"><?= h($label) ?></a>
        <?php endforeach ?>
    </nav>
    <div class="grid">
        <?php foreach ($results as $product): ?>
        <article class="product">
            <div class="product-art tone-<?= (int)$product['id'] ?>"><span><?= h($product['category']) ?></span>
                <img src="assets/<?= h(basename($product['image'])) ?>" width="400" height="330" loading="lazy" alt="<?= h($product['name']) ?>">
            </div>
            <div class="product-body"><h3><?= h($product['name']) ?></h3><p><?= h($product['detail']) ?></p>
                <div class="buy"><b><?= clp((int)$product['price']) ?></b>
                    <form action="./" method="post"><?= fields('add') ?>
                        <input type="hidden" name="id" value="<?= h($product['id']) ?>">
                        <input type="hidden" name="category" value="<?= h($category) ?>">
                        <input type="hidden" name="q" value="<?= h($search) ?>">
                        <button class="button add" <?= $product['stock'] <= ($_SESSION['cart'][$product['id']] ?? 0) ? 'disabled' : '' ?> aria-label="Agregar <?= h($product['name']) ?>"><?= $product['stock'] ? '+' : 'Agotado' ?></button>
                    </form>
                </div>
            </div>
        </article>
        <?php endforeach ?>
    </div>
    <?php if (!$results): ?><p class="panel">No encontramos productos con esa búsqueda.</p><?php endif ?>
</section>
<?php elseif (in_array($page, ['login','register'],true)): $register = $page === 'register'; ?>
<section class="panel auth">
    <p class="eyebrow accent">MI CUENTA</p><h1><?= $register ? 'Crea tu cuenta' : 'Bienvenido de vuelta' ?></h1>
    <p>Usa tus datos de prueba para explorar la tienda.</p>
    <form method="post" action="<?= url($page) ?>" class="stack">
        <?= fields($page) ?>
        <?php if ($register): ?><label>Nombre<input name="name" autocomplete="name" required maxlength="100"></label><?php endif ?>
        <label>Correo electrónico<input type="email" name="email" autocomplete="email" required maxlength="254"></label>
        <label>Contraseña<input type="password" name="password" autocomplete="<?= $register ? 'new-password' : 'current-password' ?>" required <?= $register ? 'minlength="10"' : '' ?> maxlength="72"></label>
        <button class="button"><?= $register ? 'Registrarme' : 'Iniciar sesión' ?></button>
    </form>
    <p class="divider">O continúa con</p>
    <div class="social">
        <?php foreach (providers() as $key=>$provider): ?>
        <?php if (!empty($config['oauth'][$key]['id']) && !empty($config['oauth'][$key]['secret'])): ?>
        <a class="quiet" href="<?= h(url('oauth',['provider'=>$key])) ?>"><?= h($provider[0]) ?></a>
        <?php else: ?><span class="quiet inactive" title="Requiere configuración"><?= h($provider[0]) ?></span><?php endif ?>
        <?php endforeach ?>
    </div>
    <p><?= $register ? '¿Ya tienes cuenta?' : '¿Primera vez aquí?' ?> <a class="accent" href="<?= url($register ? 'login' : 'register') ?>"><?= $register ? 'Ingresa' : 'Regístrate' ?></a></p>
</section>
<?php elseif ($page === 'cart'):
    $coupon = $_SESSION['coupon'] ?? '';
    [$subtotal,$shipping,$discount,$total] = totals($items,'pickup',$coupon);
?>
<section class="page-section">
<p class="eyebrow accent">TU COMPRA</p><h1>Mi bolsa</h1>
<?php if (!$items): ?><div class="panel"><h2>Tu bolsa está vacía</h2><p>Agrega algo rico del catálogo.</p><a class="button" href="./#catalogo">Ver catálogo</a></div>
<?php else: ?>
<div class="checkout">
<div class="stack">
<div class="panel">
<?php foreach ($items as $item): ?>
<div class="line">
    <img src="assets/<?= h(basename($item['image'])) ?>" width="78" height="65" alt="">
    <div><strong><?= h($item['name']) ?></strong><small><?= clp((int)$item['price']) ?> c/u · <?= h($item['stock']) ?> disponibles</small></div>
    <form method="post" class="quantity"><?= fields('quantity') ?><input type="hidden" name="id" value="<?= h($item['id']) ?>">
        <input name="quantity" type="number" min="0" max="<?= min(99,(int)$item['stock']) ?>" value="<?= h($item['quantity']) ?>" aria-label="Cantidad de <?= h($item['name']) ?>" required>
        <button class="quiet">Actualizar</button>
    </form>
    <form method="post"><?= fields('quantity') ?><input type="hidden" name="id" value="<?= h($item['id']) ?>"><input type="hidden" name="quantity" value="0"><button class="quiet" aria-label="Quitar <?= h($item['name']) ?>">×</button></form>
</div>
<?php endforeach ?>
</div>
<form class="panel coupon" method="post"><?= fields('coupon') ?>
    <label>Cupón <input name="coupon" value="<?= h($coupon) ?>" maxlength="30" placeholder="BIENVENIDA10"></label>
    <button class="quiet">Aplicar</button>
</form>
</div>
<div class="panel summary">
<h2>Resumen</h2>
<dl><div><dt>Subtotal</dt><dd><?= clp($subtotal) ?></dd></div><div><dt>Descuento</dt><dd><?= clp($discount) ?></dd></div><div><dt>Despacho</dt><dd id="shipping"><?= clp($shipping) ?></dd></div><div class="total"><dt>Total CLP</dt><dd id="total" data-base="<?= $total ?>"><?= clp($total) ?></dd></div></dl>
<?php if ($customer): ?>
<form method="post" class="stack" id="checkout">
    <?= fields('checkout') ?><input type="hidden" name="checkout_key" value="<?= h($_SESSION['checkout_key'] ??= bin2hex(random_bytes(24))) ?>">
    <label>Entrega<select name="method" id="method"><option value="pickup">Retiro en <?= h($branch['name']) ?></option><option value="delivery">Despacho demo (+$1.990)</option></select></label>
    <label id="address-field">Dirección para despacho<input name="address" maxlength="240" placeholder="Calle, número y comuna"></label>
    <p class="muted">Pago simulado. Sin datos bancarios ni cobros.</p>
    <button class="button">Confirmar pago de prueba</button>
</form>
<?php else: ?><a class="button" href="<?= url('login') ?>">Ingresa para continuar</a><?php endif ?>
</div>
</div>
<?php endif ?>
</section>
<?php elseif ($page === 'account'): ?>
<section class="page-section">
<p class="eyebrow accent">MI CUENTA</p><h1>Hola, <?= h($customer['name']) ?></h1>
<div class="account-actions">
<?php if ($customer['role'] === 'admin'): ?><a class="quiet" href="<?= url('admin') ?>">Administración</a><?php endif ?>
<form method="post"><?= fields('logout') ?><button class="quiet">Cerrar sesión</button></form>
</div>
<div class="panel"><h2>Mis pedidos</h2>
<?php $orders = query('SELECT code,status,total FROM orders WHERE user_id=? ORDER BY id DESC',[$customer['id']])->fetchAll(); ?>
<?php foreach ($orders as $entry): ?><a class="order-link" href="<?= h(url('order',['code'=>$entry['code']])) ?>"><b><?= h($entry['code']) ?></b><span><?= h($entry['status']) ?></span><strong><?= clp((int)$entry['total']) ?> →</strong></a><?php endforeach ?>
<?php if (!$orders): ?><p>Aquí aparecerán tus compras de prueba.</p><?php endif ?>
</div>
</section>
<?php elseif ($page === 'order'): ?>
<section class="page-section receipt panel">
<p class="eyebrow accent">PEDIDO <?= h($order['code']) ?></p><h1>Pago de prueba confirmado</h1>
<p><?= h($order['status']) ?> · <?= h($order['branch_name']) ?></p>
<p><b><?= $order['method'] === 'pickup' ? 'Retiro' : 'Despacho' ?></b> · <?= h($order['address']) ?></p>
<?php foreach (orderItems((int)$order['id']) as $item): ?>
<div class="order-link"><img src="assets/<?= h(basename($item['image'])) ?>" alt="" width="72" height="60"><span><?= h($item['quantity']) ?> × <?= h($item['name']) ?></span><strong><?= clp($item['price']*$item['quantity']) ?></strong></div>
<?php endforeach ?>
<dl><div><dt>Subtotal</dt><dd><?= clp((int)$order['subtotal']) ?></dd></div><div><dt>Despacho</dt><dd><?= clp((int)$order['shipping']) ?></dd></div><div><dt>Descuento</dt><dd><?= clp((int)$order['discount']) ?></dd></div><div class="total"><dt>Total CLP</dt><dd><?= clp((int)$order['total']) ?></dd></div></dl>
<p class="muted"><?= match($order['mail_status']) {
    'sent'=>'Comprobante enviado a ' . h($customer['email']) . '.',
    'failed'=>'El pedido está confirmado. No se pudo enviar el correo.',
    'disabled'=>'El comprobante está disponible aquí. SMTP requiere configuración.',
    default=>'Envío de correo pendiente.',
} ?></p>
<a class="button" href="./#catalogo">Volver al catálogo</a>
</section>
<?php elseif ($page === 'admin'): ?>
<section class="page-section">
<p class="eyebrow accent">PANEL DE ADMINISTRACIÓN</p><h1>Operación de tienda</h1>
<div class="checkout">
<div class="panel"><h2>Stock · <?= h($branch['name']) ?></h2>
<?php foreach ($products as $item): ?>
<form method="post" class="manage"><?= fields('stock') ?><input type="hidden" name="id" value="<?= h($item['id']) ?>"><label><?= h($item['name']) ?><input type="number" name="stock" value="<?= h($item['stock']) ?>" required min="0" max="99999"></label><button class="quiet">Guardar</button></form>
<?php endforeach ?>
</div>
<div class="panel"><h2>Pedidos recientes · <?= h($branch['name']) ?></h2>
<?php foreach (query('SELECT id,code,status,total FROM orders WHERE branch_id=? ORDER BY id DESC LIMIT 50',[$branch['id']])->fetchAll() as $entry): ?>
<form method="post" class="manage"><?= fields('status') ?><input type="hidden" name="id" value="<?= h($entry['id']) ?>"><label><?= h($entry['code']) ?> · <?= clp((int)$entry['total']) ?><select name="status">
<?php foreach (orderStatuses() as $status): ?><option <?= $status === $entry['status'] ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach ?>
</select></label><button class="quiet">Guardar</button></form>
<?php endforeach ?>
</div>
<div class="panel"><h2>Editar catálogo</h2>
<?php foreach ($products as $item): ?>
<form method="post" class="manage"><?= fields('product') ?>
<input type="hidden" name="id" value="<?= h($item['id']) ?>">
<div class="stack">
<label>Producto<input name="name" value="<?= h($item['name']) ?>" maxlength="120" required></label>
<label>Detalle<input name="detail" value="<?= h($item['detail']) ?>" maxlength="150"></label>
<label>Precio CLP<input type="number" name="price" value="<?= h($item['price']) ?>" min="1" max="999999" required></label>
</div><button class="quiet">Guardar</button></form>
<?php endforeach ?>
</div>
</div>
</section>
<?php else: ?><section class="panel page-section"><h1>Página no encontrada</h1><a href="./">Volver a la tienda</a></section><?php endif ?>
</main>
<footer><div class="wrap footer"><a href="./" aria-label="Inicio"><img src="assets/logo.webp" alt="OXXO" width="62" height="32"></a><p><strong>Tienda de conveniencia</strong> · Catálogo de muestra y compras simuladas</p><span>Santiago, Chile · CLP</span></div></footer>
</body>
</html>
