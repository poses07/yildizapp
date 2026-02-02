<?php
$cfg = [
  'mode' => 'central', // 'central' veya 'agent'
  'db' => [
    'host' => 'localhost',
    'name' => 'orionyeni',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],
  'evolution' => [
    'url' => 'http://37.148.210.190:8080',
    'apikey' => 'EE922FB0F286-49D1-AFBA-F03055AC8AA9',
    // Manager-level token (used for privileged operations like instance creation)
    'manager_token' => '443679',


    // Optional API base prefix, e.g., 'api/' or 'v1/'. Leave empty to use root.
    'prefix' => '',
    // Single-route instance creation configuration (to skip multi-probe)
    // Set method/path and where to send the name
    // Examples:
    //  - POST /instances/create with JSON body {"instanceName":"NAME"}
    //  - POST /instance/create/{NAME}
    //  - GET /instances/create?instanceName=NAME
    'create' => [
      'method' => 'POST',          // 'POST' or 'GET'
      'path' => 'instance/create', // endpoint path without prefix
      'name_field' => 'instanceName', // key for name in body/query
      'send_in' => 'body',         // 'body' or 'query'
      'name_in_path' => false,     // true to append /{NAME} to path
      // Auth mode for create: 'bearer' (uses manager_token) or 'apikey'
      'auth' => 'bearer',
      // Additional payload fields merged into body/query according to send_in
      // Keep minimal; you can extend as needed
      'payload' => [
        'qrcode' => true,
        // 'token' => 'YOUR_TOKEN',
        'integration' => 'WHATSAPP-BAILEYS',
        // 'webhook' => 'https://your-webhook',
        // 'webhook_by_events' => true,
        // 'events' => ['APPLICATION_STARTUP'],
      ],
    ],
    'instance' => 'Milat SOFT',


  ],
  'public' => [
    'run_token' => 'change-me',
    'enabled' => true,
  ],
  'log' => [
    'stdout' => true,
    // File logging controls
    'enable_file' => true,         // Disable to stop writing logs.txt
    'max_size_mb' => 5,            // Rotate when logs.txt exceeds this size
    'rotate_keep' => 10,           // Keep up to N rotated log files
    'rotate_daily' => true,        // Rotate at day boundary
    'retention_days' => 14,        // Delete rotated logs older than N days
    // Noise filters (reduce log volume)
    'exclude_preview' => true,     // Skip lines starting with "preview "
    'exclude_skip' => true,        // Skip lines starting with "skip "
    'exclude_success' => false,    // Skip lines indicating successful sends
  ],
  'send' => [
    // Delay sending after creation/approval (minutes)
    'delay_minutes_new' => 0,
    'delay_minutes_approved' => 0,
    'delay_minutes_canceled' => 0,
    // Gap between consecutive messages (seconds)
    'gap_seconds_min' => 30,
    'gap_seconds_max' => 60,
  ],
  'messages' => [
    'new' => 'Merhaba {{full_name}},\n\nBen Murat, Orion Travel\'den.\nAz önce sitemizden yaptığınız rezervasyon için yazıyorum.\n\nRezervasyon PNR: {{code}}\nAraç: {{vehicle_title}}\nGüzergah: {{pickup}} → {{dropoff}}\nTarih/Saat: {{transfer_date}} {{transfer_time}}\nKişi: Yetişkin {{adults}}, Çocuk {{children}}\nToplam: {{grand_total}} {{selected_currency}}\n\nNot: {{special_note}}\n\nHerhangi bir sorunuz olursa bu mesajdan yanıtlayabilirsiniz.',
    'approved' => 'Merhaba {{full_name}},\n\nBen Murat, Orion Travel\'den.\nRezervasyonunuz onaylandı.\n\nRezervasyon PNR: {{code}}\nAraç: {{vehicle_title}}\nGüzergah: {{pickup}} → {{dropoff}}\nTarih/Saat: {{transfer_date}} {{transfer_time}}\nKişi: Yetişkin {{adults}}, Çocuk {{children}}\nToplam: {{grand_total}} {{selected_currency}}\n\nNot: {{special_note}}\n\nHerhangi bir sorunuz olursa bu mesajdan yanıtlayabilirsiniz.',
  ],
];

// Harici mesaj şablonları: wpvoucher/messages.json mevcutsa yükle
$tplFile = __DIR__ . '/messages.json';
if (file_exists($tplFile)) {
  $tplJson = json_decode(@file_get_contents($tplFile), true);
  if (is_array($tplJson)) {
    $cfg['messages'] = array_merge($cfg['messages'] ?? [], $tplJson);
  }
}

return $cfg;
