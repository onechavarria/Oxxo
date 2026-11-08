<?php
declare(strict_types=1);

function providers(): array
{
    return [
        'google' => ['Google','https://accounts.google.com/o/oauth2/v2/auth','https://oauth2.googleapis.com/token','openid email profile'],
        'facebook' => ['Facebook','https://www.facebook.com/v24.0/dialog/oauth','https://graph.facebook.com/v24.0/oauth/access_token','email,public_profile'],
        'github' => ['GitHub','https://github.com/login/oauth/authorize','https://github.com/login/oauth/access_token','read:user user:email'],
    ];
}

function requestJson(string $url, ?array $form = null, ?string $access = null): array
{
    $headers = ['Accept: application/json', 'User-Agent: Alcindo-Store-Demo'];
    if ($access !== null) {
        $headers[] = 'Authorization: Bearer ' . $access;
    }
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    if ($form !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($form));
    }
    $body = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    $data = is_string($body) ? json_decode($body, true) : null;
    if ($code < 200 || $code >= 300 || !is_array($data)) {
        throw new DomainException('El proveedor no respondió correctamente. Intenta otra vez.');
    }
    return $data;
}

function oauth(string $provider, bool $callback): never
{
    global $config;
    $settings = $config['oauth'][$provider] ?? [];
    if (!isset(providers()[$provider]) || empty($settings['id']) || empty($settings['secret'])) {
        throw new DomainException('Este proveedor requiere configuración.');
    }
    [, $authorize, $tokenUrl, $scope] = providers()[$provider];
    $redirect = rtrim($config['url'], '/') . '/?p=callback&provider=' . $provider;
    $pkce = $provider !== 'facebook';
    if (!$callback) {
        $state = bin2hex(random_bytes(24));
        $verifier = bin2hex(random_bytes(32));
        $_SESSION['oauth'][$provider] = [$state, $verifier, time()];
        $params = ['client_id'=>$settings['id'], 'redirect_uri'=>$redirect, 'response_type'=>'code',
            'scope'=>$scope, 'state'=>$state];
        if ($pkce) {
            $params['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            $params['code_challenge_method'] = 'S256';
        }
        header('Location: ' . $authorize . '?' . http_build_query($params));
        exit;
    }
    $pending = $_SESSION['oauth'][$provider] ?? null;
    unset($_SESSION['oauth'][$provider]);
    if (!$pending || time()-$pending[2]>600 || !hash_equals($pending[0], input('state')) || input('code') === '') {
        throw new DomainException('La autorización se canceló o venció.');
    }
    $form = ['client_id'=>$settings['id'], 'client_secret'=>$settings['secret'], 'code'=>input('code'),
        'redirect_uri'=>$redirect, 'grant_type'=>'authorization_code'];
    if ($pkce) {
        $form['code_verifier'] = $pending[1];
    }
    $result = $provider === 'facebook'
        ? requestJson($tokenUrl . '?' . http_build_query($form))
        : requestJson($tokenUrl, $form);
    $access = $result['access_token'] ?? '';
    if (!is_string($access) || $access === '' || preg_match('/[\r\n]/', $access)) {
        throw new DomainException('El proveedor no concedió acceso.');
    }
    if ($provider === 'google') {
        $profile = requestJson('https://openidconnect.googleapis.com/v1/userinfo', null, $access);
        $subject = $profile['sub'] ?? '';
        $email = ($profile['email_verified'] ?? false) === true ? ($profile['email'] ?? '') : '';
    } elseif ($provider === 'facebook') {
        $proof = hash_hmac('sha256', $access, $settings['secret']);
        $profile = requestJson('https://graph.facebook.com/v24.0/me?fields=id,name,email&appsecret_proof=' . $proof, null, $access);
        $subject = $profile['id'] ?? '';
        $email = $profile['email'] ?? '';
    } else {
        $profile = requestJson('https://api.github.com/user', null, $access);
        $subject = $profile['id'] ?? '';
        $email = '';
        foreach (requestJson('https://api.github.com/user/emails', null, $access) as $candidate) {
            if (!empty($candidate['primary']) && !empty($candidate['verified'])) {
                $email = $candidate['email'] ?? '';
                break;
            }
        }
    }
    if ((string)$subject === '') {
        throw new DomainException('El proveedor no devolvió una identidad.');
    }
    $existing = query('SELECT user_id FROM identities WHERE provider=? AND subject=?', [$provider,$subject])->fetchColumn();
    if ($existing) {
        signIn((int)$existing);
        go('account');
    }
    if (!is_string($email) || strlen($email)>254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new DomainException('El proveedor debe compartir un correo válido.');
    }
    $email = strtolower($email);
    if (query('SELECT id FROM users WHERE email=?', [$email])->fetchColumn()) {
        throw new DomainException('El correo ya tiene una cuenta. Ingresa con el método que usaste al registrarte.');
    }
    db()->beginTransaction();
    try {
        query('INSERT INTO users(name,email) VALUES (?,?)', [
            preg_replace('/^(.{1,100}).*$/us', '$1', ($profile['name'] ?? '') ?: ($profile['login'] ?? 'Cliente')),
            $email,
        ]);
        $id = (int)db()->lastInsertId();
        query('INSERT INTO identities VALUES (?,?,?)', [$provider,$subject,$id]);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    signIn($id);
    go('account');
}
