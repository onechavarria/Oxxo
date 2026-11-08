# Tienda OXXO Demo Chile

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-InnoDB-4479A1?logo=mysql&logoColor=white)
![HTML](https://img.shields.io/badge/HTML-5-E34F26?logo=html5&logoColor=white)
![CSS](https://img.shields.io/badge/CSS-Responsive-663399?logo=css&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![Composer](https://img.shields.io/badge/Composer-PHPMailer-885630?logo=composer&logoColor=white)
![GitHub](https://img.shields.io/badge/GitHub-Despliegue-181717?logo=github&logoColor=white)

Proyecto independiente de portafolio inspirado en una tienda de conveniencia chilena. Usa el logotipo facilitado como referencia visual y un catálogo ilustrado con precios y sucursales ficticios. La interfaz identifica la tienda como demostración. Los pedidos, retiros, despachos y pagos son simulados. El comprobante tiene fines de demostración.

## Funcionalidades

- Catálogo adaptable con búsqueda, categorías, precios CLP y stock por sucursal.
- Carrito, cantidades, cupón `BIENVENIDA10`, retiro en tienda o despacho simulado por $1.990.
- Registro con correo y contraseña, inicio de sesión y OAuth con Google, Facebook y GitHub.
- Checkout transaccional con decremento de stock, clave de idempotencia e historial por cuenta.
- Comprobante SMTP con HTML y texto alternativo. Las imágenes PNG se adjuntan dentro del mensaje mediante CID.
- Panel de administración para actualizar catálogo, stock por sucursal y estados de pedido.

## Ejecutar en local

Requiere PHP 8.2 o superior con PDO MySQL, cURL y OpenSSL, MySQL o MariaDB y Composer 2.

1. Extrae el ZIP y abre una terminal en la carpeta del proyecto.
2. Crea una base de datos y un usuario con permisos sobre ella. Importa `db/schema.sql` en una base vacía mediante phpMyAdmin o desde la terminal.
3. Copia `app/config.example.php` a `app/config.local.php`. Configura el DSN y las credenciales.
4. Instala las dependencias y sirve la carpeta pública.

```bash
composer install --no-dev --optimize-autoloader
php -S localhost:8000 -t public
```

Entra en <http://localhost:8000>. Puedes registrar una cuenta y completar una compra de prueba desde el primer arranque con base de datos. El checkout muestra el estado del envío del comprobante.

Para habilitar el panel, registra tu cuenta y ejecuta en tu base de datos con tu propio correo:

```sql
UPDATE users SET role = 'admin' WHERE email = 'admin@ejemplo.cl';
```

## Configurar OAuth

Crea una aplicación web en cada proveedor y completa el ID y secreto correspondientes en `app/config.local.php`. Usa HTTPS en Hostinger y define `url` con la URL pública exacta. Estos son ejemplos de las URI de retorno que deben registrarse literalmente:

| Proveedor | URI de retorno |
|---|---|
| Google | `https://tienda-demo.alcindo.tech/?p=callback&provider=google` |
| Facebook | `https://tienda-demo.alcindo.tech/?p=callback&provider=facebook` |
| GitHub | `https://tienda-demo.alcindo.tech/?p=callback&provider=github` |

Para Google configura un cliente OAuth de aplicación web y añade tus usuarios de prueba si la pantalla de consentimiento está en pruebas. En Meta configura Facebook Login, habilita el callback y prueba con una cuenta con rol en la app si sigue en desarrollo. En GitHub crea una OAuth App y registra su callback. Para ambientes distintos, usa credenciales propias de cada ambiente según las opciones de cada proveedor.

La primera entrada social crea la cuenta si el proveedor entrega un correo utilizable. Si el correo ya está asociado a otro método, la tienda conserva el método original para evitar vincular cuentas automáticamente.

Documentación oficial de [Google](https://developers.google.com/identity/protocols/oauth2/web-server), [Facebook](https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/) y [GitHub](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps).

## Configurar SMTP

Completa `host`, `port`, `encryption`, `user`, `password` y `from` con los datos de tu proveedor. Los valores habituales son `tls` con puerto 587 para STARTTLS y `ssl` con puerto 465 para TLS implícito. Usa una dirección remitente autorizada.

Para probar, registra una cuenta con un correo al que tengas acceso o utiliza un buzón SMTP de pruebas. Completa una compra y revisa que el mensaje incluya los productos, cantidades, imágenes, total en CLP y modalidad de entrega. Si SMTP falla, el pedido y el descuento de stock se conservan y el detalle web indica el error de envío. El servidor registra el código de pedido.

## Desplegar en Hostinger desde GitHub

1. Crea un **Sitio web PHP/HTML**, configura PHP 8.2 o superior, una base MySQL y HTTPS. Importa el SQL con phpMyAdmin.
2. Sube el código a GitHub sin credenciales. Mediante SSH, clona el repositorio en un directorio privado, separado de `public_html`. Para un repositorio privado puedes usar una clave de despliegue de solo lectura.
3. En el repositorio clonado, ejecuta `composer install --no-dev --optimize-autoloader`.
4. En la raíz privada del dominio, al mismo nivel que `public_html`, deja `app/` y `vendor/`. Crea allí `app/config.local.php` con el DSN de Hostinger, la URL HTTPS, OAuth y SMTP. Copia **solo el contenido de `public/`** a `public_html/`, incluido `.htaccess`.

```text
dominio/
├── app/
│   └── config.local.php
├── vendor/
└── public_html/
    ├── .htaccess
    ├── index.php
    └── assets/
```

Con esta estructura puedes actualizar haciendo `git pull --ff-only` en el clon privado, instalando dependencias y copiando los cambios de `app/`, `vendor/` y `public/` a sus destinos. Conserva `config.local.php` en el servidor. Si automatizas el despliegue con GitHub Actions, ejecuta esos pasos sobre SSH con secretos del repositorio y un host verificado. También puedes subir las carpetas con SFTP si instalas las dependencias en local.

[Documentación Git de Hostinger](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/) · [Directorio web y SSH](https://www.hostinger.com/support/5973000-how-to-open-the-website-s-root-directory-via-ssh-in-hostinger/)

## Estructura

| Ruta | Contenido |
|---|---|
| `public/` | Entrada HTTP y recursos visuales |
| `app/` | Sesión, consultas, OAuth, pedidos, correo y plantillas |
| `db/schema.sql` | Esquema, catálogo, sucursales y stock inicial |
| `composer.json` y `composer.lock` | Requisitos y versión fijada de PHPMailer |
| `vendor/` | Dependencia incluida en el ZIP. Composer la instala al desplegar desde GitHub. |

**Modo demo.** Para una exposición pública usa datos de prueba y, si habilitas SMTP, un buzón de pruebas o un entorno de acceso restringido.
