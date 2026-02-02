<?php
$config = require __DIR__ . '/config.php';

function evoRequest(string $method, string $path, array $query = [], $body = null, array $opts = []): array {
  global $config;
  $url = rtrim($config['evolution']['url'], '/') . '/' . ltrim($path, '/');
  if (!empty($query)) {
    $url .= '?' . http_build_query($query);
  }
  $ch = curl_init();
  $authMode = isset($opts['auth']) ? (string)$opts['auth'] : 'apikey';
  $headers = [];
  if ($authMode === 'bearer') {
    $token = (string)($config['evolution']['manager_token'] ?? '');
    if ($token !== '') { $headers[] = 'Authorization: Bearer ' . $token; }
  } else {
    $headers[] = 'apikey: ' . $config['evolution']['apikey'];
  }
  if ($body !== null) {
    if (is_array($body)) { $body = json_encode($body); }
    $headers[] = 'Content-Type: application/json';
  }
  $timeout = isset($opts['timeout']) ? (int)$opts['timeout'] : 20;
  $connectTimeout = isset($opts['connect_timeout']) ? (int)$opts['connect_timeout'] : 8;
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_CUSTOMREQUEST => strtoupper($method),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_CONNECTTIMEOUT => $connectTimeout,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
  ]);
  if ($body !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
  }
  $res = curl_exec($ch);
  $err = curl_error($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  unset($ch);
  if ($err) { return ['ok' => false, 'error' => $err, 'status' => $status, 'raw' => $res]; }
  $json = json_decode($res, true);
  return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'data' => $json, 'raw' => $res];
}

function evoCreateInstance(string $name): array {
  global $config;
  $opts = ['timeout' => 5, 'connect_timeout' => 3];
  $prefixes = evoPreferredPrefixes();
  $createCfg = $config['evolution']['create'] ?? [];
  $last = null; $lastRoute = '';

  // If single-route config provided, use it exclusively
  if (is_array($createCfg) && !empty($createCfg['path'])) {
    $method = strtoupper((string)($createCfg['method'] ?? 'POST'));
    $pathBase = trim((string)$createCfg['path'], '/');
    $nameField = (string)($createCfg['name_field'] ?? 'instanceName');
    $sendIn = (string)($createCfg['send_in'] ?? 'body'); // 'body' or 'query'
    $nameInPath = !empty($createCfg['name_in_path']);
    $extra = is_array($createCfg['payload'] ?? null) ? $createCfg['payload'] : [];
    // Use only configured prefix (or root if empty) for a single attempt
    $cfgPref = trim((string)($config['evolution']['prefix'] ?? ''), '/');
    $pf = $cfgPref === '' ? '' : ($cfgPref . '/');
    $path = rtrim($pf, '/') . '/' . $pathBase . ($nameInPath ? '/' . rawurlencode($name) : '');
    $body = null; $query = [];
    if ($method === 'POST') {
      if ($sendIn === 'query') { $query = array_merge([$nameField => $name], $extra); }
      else { $body = array_merge([$nameField => $name], $extra); }
    } else { $query = array_merge([$nameField => $name], $extra); }
    // Do not inject API key into query/body; rely on header only
    // Choose auth mode for create: bearer (manager_token) or apikey
    $authMode = (string)($createCfg['auth'] ?? 'apikey');
    $res = evoRequest($method, $path, $query, $body, array_merge($opts, ['auth' => $authMode]));
    $last = $res; $lastRoute = $method . ' ' . $path . (empty($query)?'':'?' . http_build_query($query));
    if (!empty($res['ok'])) { $res['route'] = $lastRoute; return $res; }
    // If single attempt fails, try auth fallback then multi-probe variants
    // 1) Try alternate auth header once (switch bearer<->apikey)
    $altAuth = ($authMode === 'bearer') ? 'apikey' : 'bearer';
    $res2 = evoRequest($method, $path, $query, $body, array_merge($opts, ['auth' => $altAuth]));
    $last = $res2; $lastRoute = $method . ' ' . $path . (empty($query)?'':'?' . http_build_query($query));
    if (!empty($res2['ok'])) { $res2['route'] = $lastRoute; return $res2; }

    // 2) Multi-probe across common prefixes, paths and name fields
    $paths = ['instance/create', 'instances/create', 'session/create', 'instances/add', 'instance/new'];
    $bodies = [
      ['instanceName' => $name] + $extra,
      ['instance' => $name] + $extra,
      ['name' => $name] + $extra,
      ['sessionName' => $name] + $extra,
    ];
    $queries = [ [], [$nameField => $name] + $extra, ['instanceName' => $name] + $extra ];
    foreach ($prefixes as $pf2) {
      foreach ($paths as $p) {
        foreach ([$p, $p . '/' . rawurlencode($name)] as $p2) {
          $path2 = rtrim($pf2, '/') . '/' . $p2;
          foreach ($bodies as $b2) {
            foreach ($queries as $q2) {
              foreach ([$authMode, $altAuth] as $tryAuth) {
                $res3 = evoRequest('POST', $path2, $q2, $b2, array_merge($opts, ['auth' => $tryAuth]));
                $last = $res3; $lastRoute = 'POST ' . $path2 . (empty($q2)?'':'?' . http_build_query($q2));
                if (!empty($res3['ok'])) { $res3['route'] = $lastRoute; return $res3; }
                $msg = strtolower(json_encode($res3['data'] ?? ''));
                if (strpos($msg, 'exist') !== false || strpos($msg, 'already') !== false) {
                  return ['ok' => true, 'status' => $res3['status'] ?? 200, 'data' => $res3['data'] ?? ['message' => 'instance_exists'], 'route' => $lastRoute];
                }
                if (strpos($msg, 'created') !== false || strpos($msg, 'success') !== false || strpos($msg, 'ok') !== false) {
                  return ['ok' => true, 'status' => $res3['status'] ?? 200, 'data' => $res3['data'] ?? ['message' => 'instance_created'], 'route' => $lastRoute];
                }
              }
            }
          }
          foreach ($queries as $q2) {
            foreach ([$authMode, $altAuth] as $tryAuth) {
              $res4 = evoRequest('GET', $path2, $q2, null, array_merge($opts, ['auth' => $tryAuth]));
              $last = $res4; $lastRoute = 'GET ' . $path2 . (empty($q2)?'':'?' . http_build_query($q2));
              if (!empty($res4['ok'])) { $res4['route'] = $lastRoute; return $res4; }
            }
          }
        }
      }
    }
    return ['ok' => false, 'error' => 'create_instance_failed', 'status' => $last['status'] ?? 0, 'data' => $last['data'] ?? null, 'route' => $lastRoute];
  }

  // Fallback multi-attempt (legacy)
  $paths = ['instance/create', 'instances/create', 'session/create', 'instances/add', 'instance/new'];
  $bodies = [
    ['instanceName' => $name],
    ['instance' => $name],
    ['name' => $name],
    ['sessionName' => $name],
  ];
  $queries = [ [], ['instanceName' => $name] ];
  foreach ($prefixes as $pf) {
    foreach ($paths as $p) {
      foreach ([$p, $p . '/' . rawurlencode($name)] as $p2) {
        $path = rtrim($pf, '/') . '/' . $p2;
        foreach ($bodies as $b) {
          foreach ($queries as $q) {
            $res = evoRequest('POST', $path, $q, $b, $opts);
            $last = $res; $lastRoute = 'POST ' . $path . (empty($q)?'':'?' . http_build_query($q));
            if (!empty($res['ok'])) { $res['route'] = $lastRoute; return $res; }
            $msg = strtolower(json_encode($res['data'] ?? ''));
            if (strpos($msg, 'exist') !== false || strpos($msg, 'already') !== false) {
              return ['ok' => true, 'status' => $res['status'] ?? 200, 'data' => $res['data'] ?? ['message' => 'instance_exists'], 'route' => $lastRoute];
            }
            if (strpos($msg, 'created') !== false || strpos($msg, 'success') !== false || strpos($msg, 'ok') !== false) {
              return ['ok' => true, 'status' => $res['status'] ?? 200, 'data' => $res['data'] ?? ['message' => 'instance_created'], 'route' => $lastRoute];
            }
          }
        }
        foreach ($queries as $q) {
          $res = evoRequest('GET', $path, $q, null, $opts);
          $last = $res; $lastRoute = 'GET ' . $path . (empty($q)?'':'?' . http_build_query($q));
          if (!empty($res['ok'])) { $res['route'] = $lastRoute; return $res; }
        }
      }
    }
  }
  return ['ok' => false, 'error' => 'create_instance_failed', 'status' => $last['status'] ?? 0, 'data' => $last['data'] ?? null, 'route' => $lastRoute];
}

// Fetch Evolution API info to detect correct base path and version
function evoGetInfo(): array {
  $prefixes = [
    '', '/',
    'api', 'api/',
    'v1', 'v1/',
    'api/v1', 'api/v1/',
    // Manager-hosted variants
    'manager', 'manager/',
    'manager/api', 'manager/api/',
    'manager/v1', 'manager/v1/',
    'manager/api/v1', 'manager/api/v1/',
  ];
  $opts = ['timeout' => 4, 'connect_timeout' => 2];
  $last = null; $route = '';
  foreach ($prefixes as $pf) {
    $path = trim($pf, '/');
    $res = evoRequest('GET', $path, [], null, $opts);
    $last = $res; $route = 'GET ' . ($path === '' ? '/' : '/' . $path);
    if (!empty($res['ok'])) { $res['route'] = $route; return $res; }
    // Some servers return welcome even with 200 but non-JSON
    $msg = strtolower($res['raw'] ?? '');
    if (strpos($msg, 'welcome to the evolution api') !== false) {
      return ['ok' => true, 'status' => $res['status'] ?? 200, 'data' => ['message' => 'welcome'], 'route' => $route];
    }
  }
  return ['ok' => false, 'error' => 'info_fetch_failed', 'status' => $last['status'] ?? 0, 'data' => $last['data'] ?? null, 'route' => $route];
}

// Derive preferred base prefixes from evoGetInfo result
function evoPreferredPrefixes(): array {
  static $cache = null;
  if (is_array($cache)) return $cache;
  global $config;
  // If config has explicit prefix, honor it strictly
  $cfgPref = trim((string)($config['evolution']['prefix'] ?? ''), '/');
  if ($cfgPref !== '') {
    $cache = [$cfgPref . '/'];
    return $cache;
  }
  // Otherwise infer from info and include sensible fallbacks
  $info = evoGetInfo();
  $route = strtolower((string)($info['route'] ?? ''));
  $pref = [''];
  if (strpos($route, '/api/v1') !== false) { $pref = ['api/v1/']; }
  elseif (strpos($route, '/api') !== false) { $pref = ['api/']; }
  elseif (strpos($route, '/v1') !== false) { $pref = ['v1/']; }
  // Append common fallbacks to be safe
  $all = array_unique(array_merge($pref, ['api/', 'v1/', 'api/v1/']));
  $cache = $all;
  return $all;
}

function evoGetQrCode(string $name): array {
  // Try endpoints that return QR content across known prefixes
  $prefixes = evoPreferredPrefixes();
  $routes = [
    // Common GET variants
    ['method' => 'GET', 'path' => 'instance/qr/%NAME%', 'query' => ['image' => 'true']],
    ['method' => 'GET', 'path' => 'instance/qr', 'query' => ['instanceName' => '%NAME%', 'image' => 'true']],
    // Some servers return QR or pairing code via connect/open
    ['method' => 'GET', 'path' => 'instance/connect/%NAME%', 'query' => []],
    ['method' => 'GET', 'path' => 'instances/connect/%NAME%', 'query' => []],
    // Alternative paths
    ['method' => 'GET', 'path' => 'instances/qr/%NAME%', 'query' => ['image' => 'true']],
    ['method' => 'GET', 'path' => 'instances/qr', 'query' => ['instanceName' => '%NAME%', 'image' => 'true']],
    ['method' => 'GET', 'path' => 'instance/qrcode/%NAME%', 'query' => ['image' => 'true']],
    ['method' => 'GET', 'path' => 'instance/qrcode', 'query' => ['instanceName' => '%NAME%', 'image' => 'true']],
    ['method' => 'GET', 'path' => 'instance/qrCode/%NAME%', 'query' => ['image' => 'true']],
    ['method' => 'GET', 'path' => 'instance/qrCode', 'query' => ['instanceName' => '%NAME%', 'image' => 'true']],
    // Base64 hints via GET
    ['method' => 'GET', 'path' => 'instance/qr', 'query' => ['instanceName' => '%NAME%', 'image' => 'false', 'type' => 'base64']],
    ['method' => 'GET', 'path' => 'instance/qrcode', 'query' => ['instanceName' => '%NAME%', 'format' => 'base64']],
    // Some servers expose POST to generate fresh QR (prefer base64)
    ['method' => 'POST', 'path' => 'instance/qr/%NAME%', 'body' => ['image' => 'false', 'type' => 'base64']],
    ['method' => 'POST', 'path' => 'instance/qr', 'body' => ['instanceName' => '%NAME%', 'image' => 'false', 'type' => 'base64']],
    ['method' => 'POST', 'path' => 'instance/qrcode/%NAME%', 'body' => ['image' => 'false', 'format' => 'base64']],
    ['method' => 'POST', 'path' => 'instance/qrcode', 'body' => ['instanceName' => '%NAME%', 'image' => 'false', 'format' => 'base64']],
  ];
  $last = null;
  foreach ($prefixes as $pf) {
    foreach ($routes as $route) {
      $method = $route['method'];
      $pathTpl = $route['path'];
      $path = str_replace('%NAME%', rawurlencode($name), $pathTpl);
      $path = rtrim($pf, '/') . '/' . ltrim($path, '/');
      $query = $route['query'] ?? [];
      $body = $route['body'] ?? null;
      // Replace name placeholders in query/body too
      $replaceName = function ($arr) use ($name) {
        $out = [];
        foreach ($arr as $k => $v) { $out[$k] = ($v === '%NAME%') ? $name : $v; }
        return $out;
      };
      if (!empty($query)) { $query = $replaceName($query); }
      if (is_array($body)) { $body = $replaceName($body); }
      $res = evoRequest($method, $path, $query, $body);
      if (!empty($res['ok'])) {
        $d = $res['data'] ?? [];
        // Common fields: base64, qrCode, image, qrcode
        $b64 = $d['base64'] ?? $d['qrCode'] ?? $d['image'] ?? $d['qrcode'] ?? $d['qr'] ?? $d['code'] ?? '';
        $pairingCode = $d['pairingCode'] ?? null;
        // If qrcode is an object/array, try nested fields
        if (is_array($d['qrcode'] ?? null)) {
          $b64 = $d['qrcode']['base64'] ?? $d['qrcode']['qrCode'] ?? $d['qrcode']['image'] ?? $d['qrcode']['base64Image'] ?? $b64;
          $pairingCode = $pairingCode ?? $d['qrcode']['pairingCode'] ?? null;
        }
        if (is_array($d['qr'] ?? null)) {
          $b64 = $d['qr']['base64'] ?? $d['qr']['image'] ?? $d['qr']['base64Image'] ?? $b64;
          $pairingCode = $pairingCode ?? $d['qr']['pairingCode'] ?? null;
        }
        // Some APIs wrap data under data.base64
        if (is_array($d['data'] ?? null)) {
          $b64 = $d['data']['base64'] ?? $d['data']['image'] ?? $d['data']['qrCode'] ?? $b64;
          $pairingCode = $pairingCode ?? $d['data']['pairingCode'] ?? null;
        }
        if (is_string($b64) && $b64 !== '') {
          // Some APIs return data URI, strip prefix if present
          if (strpos($b64, 'base64,') !== false) {
            $parts = explode('base64,', $b64, 2);
            $b64 = $parts[1] ?? $b64;
          }
          return ['ok' => true, 'base64' => $b64, 'data' => $d];
        }
        // If pairing code provided, return it explicitly
        if (!empty($pairingCode)) {
          return ['ok' => true, 'pairingCode' => $pairingCode, 'data' => $d];
        }
        // Fallback: PNG bytes as raw
        if (!empty($res['raw']) && substr($res['raw'], 0, 4) === "\x89PNG") {
          return ['ok' => true, 'base64' => base64_encode($res['raw']), 'data' => $d];
        }
      }
      $last = $res;
    }
  }
  return ['ok' => false, 'error' => 'get_qr_failed', 'status' => $last['status'] ?? 0, 'data' => $last['data'] ?? null, 'raw' => $last['raw'] ?? null];
}

// Start instance and poll for QR for a short period
function evoStartAndFetchQr(string $name, int $maxAttempts = 6, int $delayMs = 800): array {
  $started = evoStartInstance($name);
  $last = null; $stateRaw = '';
  for ($i = 0; $i < $maxAttempts; $i++) {
    $qr = evoGetQrCode($name);
    if (!empty($qr['ok']) && !empty($qr['base64'])) {
      return ['ok' => true, 'base64' => $qr['base64'], 'data' => $qr['data'] ?? []];
    }
    $st = evoGetStatus($name);
    $d = $st['data'] ?? [];
    $stateRaw = strtolower((string)($d['state'] ?? $d['connectionStatus'] ?? $d['status'] ?? ($d['instance']['state'] ?? '')));
    // If already connected, no QR will be produced
    if (in_array($stateRaw, ['connected','online','ready','open'])) {
      return ['ok' => false, 'error' => 'already_connected', 'state' => $stateRaw];
    }
    usleep($delayMs * 1000);
    $last = $qr;
  }
  return ['ok' => false, 'error' => 'qr_unavailable', 'state' => $stateRaw, 'last' => $last];
}

function evoGetStatus(string $name): array {
  foreach ([
    ['method' => 'GET', 'path' => 'instance/status/' . rawurlencode($name), 'query' => []],
    ['method' => 'GET', 'path' => 'instance/connectionState/' . rawurlencode($name), 'query' => []],
    ['method' => 'GET', 'path' => 'instance/status', 'query' => ['instanceName' => $name]],
  ] as $route) {
    $res = evoRequest($route['method'], $route['path'], $route['query']);
    if (!empty($res['ok'])) { return $res; }
  }
  return ['ok' => false, 'error' => 'get_status_failed'];
}

// Try to start/connect an instance to trigger QR pairing
function evoStartInstance(string $name): array {
  foreach ([
    ['method' => 'POST', 'path' => 'instance/start/' . rawurlencode($name), 'query' => []],
    ['method' => 'POST', 'path' => 'instance/connect/' . rawurlencode($name), 'query' => []],
    ['method' => 'POST', 'path' => 'instance/init/' . rawurlencode($name), 'query' => []],
    ['method' => 'POST', 'path' => 'instance/start', 'query' => ['instanceName' => $name]],
    // Alternative names
    ['method' => 'POST', 'path' => 'instances/start', 'query' => ['instanceName' => $name]],
    ['method' => 'POST', 'path' => 'instance/restart/' . rawurlencode($name), 'query' => []],
    ['method' => 'POST', 'path' => 'instance/reconnect/' . rawurlencode($name), 'query' => []],
    ['method' => 'POST', 'path' => 'instance/open/' . rawurlencode($name), 'query' => []],
    // Sometimes QR generation triggers pairing
    ['method' => 'POST', 'path' => 'instance/qrcode/' . rawurlencode($name), 'query' => []],
  ] as $route) {
    $res = evoRequest($route['method'], $route['path'], $route['query']);
    if (!empty($res['ok'])) { return $res; }
  }
  return ['ok' => false, 'error' => 'start_instance_failed'];
}

// Backward-compat wrappers for other scripts
function evoGetInstanceState(string $name): array {
  return evoGetStatus($name);
}
function evoGetQRCodeBase64(string $name): array {
  $qr = evoGetQrCode($name);
  if (!empty($qr['ok'])) {
    return ['ok' => true, 'base64' => $qr['base64'] ?? '', 'data' => $qr['data'] ?? []];
  }
  return ['ok' => false, 'error' => $qr['error'] ?? 'get_qr_failed'];
}

// Try deleting/removing an instance on Evolution API
function evoDeleteInstance(string $name): array {
  $routes = [
    // Common DELETE routes
    ['method' => 'DELETE', 'path' => 'instance/delete/' . rawurlencode($name), 'query' => []],
    ['method' => 'DELETE', 'path' => 'instances/delete/' . rawurlencode($name), 'query' => []],
    ['method' => 'DELETE', 'path' => 'instance/' . rawurlencode($name), 'query' => []],
    ['method' => 'DELETE', 'path' => 'instances/' . rawurlencode($name), 'query' => []],
    // Some servers use remove/close/logout
    ['method' => 'DELETE', 'path' => 'instance/remove/' . rawurlencode($name), 'query' => []],
    ['method' => 'DELETE', 'path' => 'instance/close/' . rawurlencode($name), 'query' => []],
    ['method' => 'DELETE', 'path' => 'instance/logout/' . rawurlencode($name), 'query' => []],
    // POST fallbacks with name in query/body
    ['method' => 'POST', 'path' => 'instance/delete', 'query' => ['instanceName' => $name]],
    ['method' => 'POST', 'path' => 'instances/delete', 'query' => ['instanceName' => $name]],
    ['method' => 'POST', 'path' => 'instance/remove', 'query' => ['instanceName' => $name]],
    ['method' => 'POST', 'path' => 'instance/close',  'query' => ['instanceName' => $name]],
    ['method' => 'POST', 'path' => 'instance/logout', 'query' => ['instanceName' => $name]],
  ];
  $last = null;
  foreach ($routes as $route) {
    $res = evoRequest($route['method'], $route['path'], $route['query']);
    $last = $res;
    if (!empty($res['ok'])) { return $res; }
    // Treat "not found" as success if instance is already absent
    $msg = strtolower(json_encode($res['data'] ?? ''));
    if (strpos($msg, 'not found') !== false || strpos($msg, 'no such') !== false) {
      return ['ok' => true, 'status' => $res['status'] ?? 200, 'data' => ['message' => 'instance_absent']];
    }
  }
  return ['ok' => false, 'error' => 'delete_instance_failed', 'status' => $last['status'] ?? 0, 'data' => $last['data'] ?? null];
}

function normalizePhone(string $phone): string {
  $p = trim($phone);
  // Keep only digits; handle international formats
  $digits = preg_replace('/\D+/', '', $p);
  // If written with '00' international prefix, strip it
  if (strpos($digits, '00') === 0) {
    $digits = substr($digits, 2);
  }
  // Country code trunk '0' removal for common prefixes
  $codes3 = ['971', '380'];
  $codes2 = ['90', '44', '49', '33', '34', '39', '31', '32', '41', '43', '45', '46', '47', '48', '61', '64'];
  $codes1 = ['1', '7'];
  $code = '';
  foreach ($codes3 as $cc) {
    if (strpos($digits, $cc) === 0) { $code = $cc; break; }
  }
  if ($code === '') {
    foreach ($codes2 as $cc) {
      if (strpos($digits, $cc) === 0) { $code = $cc; break; }
    }
  }
  if ($code === '') {
    foreach ($codes1 as $cc) {
      if (strpos($digits, $cc) === 0) { $code = $cc; break; }
    }
  }
  if ($code !== '' && strlen($digits) > strlen($code)) {
    $next = $digits[strlen($code)] ?? '';
    if ($next === '0') {
      // Remove trunk '0' after country code
      $digits = $code . substr($digits, strlen($code) + 1);
    }
  }
  // Fallback: if no country code detected and number looks like UK mobile (0?7XXXXXXXXX), assume +44
  if ($code === '' && preg_match('/^0?7\d{9}$/', $digits)) {
    // strip leading 0 if present, then prefix 44
    if ($digits[0] === '0') { $digits = substr($digits, 1); }
    $digits = '44' . $digits;
  }
  return $digits;
}

// Validate national significant number (NSN) length for common country codes.
// Returns ['ok'=>true] or ['ok'=>false,'error'=>'invalid_phone_length','expected'=>int,'national_length'=>int,'country'=>string]
function validatePhoneLength(string $digits): array {
  // Identify country code by prefix (3→2→1 digits)
  $prefixes = [
    // 3-digit codes we use
    '971' => 9,   // UAE: 9 NSN (mobile typically 50/55 + 7 digits)
    '380' => 9,   // Ukraine: 9
    // 2-digit
    '90'  => 10,  // Turkey
    '44'  => 10,  // UK
    // '49' varies; skip strict enforcement to avoid false negatives
    '33'  => 9,   // France
    '34'  => 9,   // Spain
    '39'  => 10,  // Italy
    '31'  => 9,   // Netherlands
    '32'  => 9,   // Belgium
    '41'  => 9,   // Switzerland (varies; set 9 common)
    '43'  => 10,  // Austria
    '45'  => 8,   // Denmark
    '46'  => 9,   // Sweden
    '47'  => 8,   // Norway
    '48'  => 9,   // Poland
    '61'  => 9,   // Australia (mobile 9)
    '64'  => 9,   // New Zealand
    // 1-digit
    '1'   => 10,  // US/Canada
    '7'   => 10,  // Russia/Kazakhstan
  ];
  $cc = '';
  foreach ([3,2,1] as $len) {
    $pref = substr($digits, 0, $len);
    if (isset($prefixes[$pref])) { $cc = $pref; break; }
  }
  if ($cc === '') {
    // Unknown or unsupported; do not block
    return ['ok' => true];
  }
  $national = substr($digits, strlen($cc));
  $expected = $prefixes[$cc];
  $nlen = strlen($national);
  if ($nlen === $expected) {
    return ['ok' => true];
  }
  return ['ok' => false, 'error' => 'invalid_phone_length', 'expected' => $expected, 'national_length' => $nlen, 'country' => $cc];
}

function sendWhatsappText(string $phone, string $text): array {
  global $config;
  $number = normalizePhone($phone);
  // Validate length before hitting API
  $val = validatePhoneLength($number);
  if (empty($val['ok'])) {
    return ['ok' => false, 'status' => 400, 'error' => 'invalid_phone_length', 'data' => ['number' => $number] + $val];
  }
  $payload = json_encode([
    'number' => $number,
    'text' => $text,
  ]);

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($config['evolution']['url'], '/') . '/message/sendText/' . rawurlencode($config['evolution']['instance']),
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'apikey: ' . $config['evolution']['apikey'],
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
  ]);
  $res = curl_exec($ch);
  $err = curl_error($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  unset($ch);

  if ($err) {
    return ['ok' => false, 'error' => $err, 'status' => $status];
  }
  $json = json_decode($res, true);
  return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'data' => $json];
}

function sendWhatsappTextWithRetry(string $phone, string $text, int $maxAttempts = 3, int $baseDelayMs = 500): array {
  $attempt = 0;
  $last = ['ok' => false, 'error' => 'init'];
  while ($attempt < $maxAttempts) {
    $attempt++;
    $res = sendWhatsappText($phone, $text);
    if (!empty($res['ok'])) {
      return $res;
    }
    // Do not retry if invalid length – needs data fix
    if (!empty($res['error']) && $res['error'] === 'invalid_phone_length') {
      return $res;
    }
    $delay = $baseDelayMs * (1 << ($attempt - 1));
    usleep($delay * 1000);
    $last = $res;
  }
  return $last;
}
