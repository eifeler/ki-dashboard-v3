<?php
/**
 * KI-Dashboard API
 * Liefert JSON-Daten aus Markdown/Text-Dateien
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

define('DATA_DIR', __DIR__ . '/data/');
define('ADMIN_SESSION', 'ki_dashboard_admin');

session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard': echo json_encode(getDashboardData()); break;
    case 'tools':     echo json_encode(['tools' => getTools()]); break;
    case 'prompts':   echo json_encode(['prompts' => getPrompts()]); break;
    case 'courses':   echo json_encode(['courses' => getCourses(), 'glossary' => getGlossary()]); break;
    case 'news':      echo json_encode(['news' => getNews()]); break;
    case 'ticker':    echo json_encode(['items' => getTickerItems()]); break;
    case 'ai_query':  handleAiQuery(); break;

    // Admin actions (require auth)
    case 'admin_login':  handleAdminLogin(); break;
    case 'admin_logout': handleAdminLogout(); break;
    case 'admin_check':  echo json_encode(['loggedIn' => isAdminLoggedIn()]); break;
    case 'admin_save_tool':   requireAdmin(); saveTool(); break;
    case 'admin_delete_tool': requireAdmin(); deleteTool(); break;
    case 'admin_save_prompt':   requireAdmin(); savePrompt(); break;
    case 'admin_delete_prompt': requireAdmin(); deletePrompt(); break;
    case 'admin_save_news':   requireAdmin(); saveNews(); break;
    case 'admin_delete_news': requireAdmin(); deleteNews(); break;
    case 'admin_save_course':   requireAdmin(); saveCourse(); break;
    case 'admin_save_glossary': requireAdmin(); saveGlossary(); break;
    case 'admin_change_pw':     requireAdmin(); changePassword(); break;
    case 'admin_glossary_raw':  requireAdmin(); echo json_encode(['content' => file_exists(DATA_DIR.'glossary.md') ? file_get_contents(DATA_DIR.'glossary.md') : '']); break;

    default: echo json_encode(['error' => 'Unknown action']);
}

// ── AUTHENTICATION ────────────────────────────────────────────
function isAdminLoggedIn() {
    return isset($_SESSION[ADMIN_SESSION]) && $_SESSION[ADMIN_SESSION] === true;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
}

function handleAdminLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $config = getConfig();
    $hash = $config['admin_hash'] ?? '';

    // First-run: placeholder hash – accept "admin123" and replace hash
    $isPlaceholder = strpos($hash, 'placeholder') !== false;
    $pw = $data['password'] ?? '';

    $valid = false;
    if ($isPlaceholder && $pw === 'admin123') {
        // Auto-set proper hash on first login
        $config['admin_hash'] = password_hash('admin123', PASSWORD_DEFAULT);
        unset($config['note']);
        file_put_contents(DATA_DIR . 'config.json', json_encode($config, JSON_PRETTY_PRINT));
        $valid = true;
    } elseif (!$isPlaceholder) {
        $valid = password_verify($pw, $hash);
    }

    if ($valid) {
        $_SESSION[ADMIN_SESSION] = true;
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Falsches Passwort']);
    }
}

function handleAdminLogout() {
    unset($_SESSION[ADMIN_SESSION]);
    echo json_encode(['success' => true]);
}

function getConfig() {
    $file = DATA_DIR . 'config.json';
    if (file_exists($file)) return json_decode(file_get_contents($file), true);
    // Default config (password: "admin123" – ÄNDERN!)
    return [
        'admin_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'site_title' => 'KI-Dashboard',
        'api_proxy' => false,
    ];
}

// ── DATA READERS ──────────────────────────────────────────────
function getTools() {
    return readDataDir(DATA_DIR . 'tools/');
}

function getPrompts() {
    return readDataDir(DATA_DIR . 'prompts/');
}

function getNews() {
    $items = readDataDir(DATA_DIR . 'news/');
    usort($items, fn($a,$b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $items;
}

function getCourses() {
    return readDataDir(DATA_DIR . 'courses/');
}

function getGlossary() {
    $file = DATA_DIR . 'glossary.md';
    if (!file_exists($file)) return [];
    return parseGlossaryMd(file_get_contents($file));
}

function getTickerItems() {
    $news = getNews();
    $tools = getTools();
    $items = [];
    foreach (array_slice($news, 0, 5) as $n) $items[] = $n['title'] ?? '';
    foreach (array_slice($tools, 0, 5) as $t) $items[] = 'Neues Tool: ' . ($t['name'] ?? '');
    return array_filter($items);
}

function getDashboardData() {
    return [
        'stats' => [
            'tools'   => count(getTools()),
            'prompts' => count(getPrompts()),
            'courses' => count(getCourses()),
            'news'    => count(getNews()),
        ],
        'news' => array_slice(getNews(), 0, 5),
    ];
}

// ── GENERIC MD FILE READER ────────────────────────────────────
/**
 * Liest alle .md-Dateien aus einem Verzeichnis.
 * Format einer .md-Datei:
 * ---
 * id: tool-chatgpt
 * name: ChatGPT
 * key: value
 * ---
 * Freitext/Beschreibung folgt nach dem zweiten ---
 */
function readDataDir($dir) {
    if (!is_dir($dir)) return [];
    $items = [];
    foreach (glob($dir . '*.md') as $file) {
        $item = parseMdFile(file_get_contents($file));
        if ($item) {
            $item['_file'] = basename($file);
            $items[] = $item;
        }
    }
    return $items;
}

function parseMdFile($content) {
    $content = trim($content);
    if (!str_starts_with($content, '---')) return null;
    $parts = explode('---', $content, 3);
    if (count($parts) < 2) return null;
    $meta = [];
    foreach (explode("\n", trim($parts[1])) as $line) {
        if (strpos($line, ':') !== false) {
            [$key, $val] = explode(':', $line, 2);
            $key = trim($key);
            $val = trim($val);
            // Arrays: comma-separated in brackets [a, b, c]
            if (preg_match('/^\[(.+)\]$/', $val, $m)) {
                $val = array_map('trim', explode(',', $m[1]));
            }
            $meta[$key] = $val;
        }
    }
    if (isset($parts[2])) $meta['content'] = trim($parts[2]);
    return $meta;
}

function parseGlossaryMd($content) {
    $items = [];
    $lines = explode("\n", $content);
    $cur = null;
    foreach ($lines as $line) {
        if (preg_match('/^##\s+(.+)/', $line, $m)) {
            if ($cur) $items[] = $cur;
            $cur = ['term' => trim($m[1]), 'def' => ''];
        } elseif ($cur && trim($line)) {
            $cur['def'] .= ($cur['def'] ? ' ' : '') . trim($line);
        }
    }
    if ($cur) $items[] = $cur;
    return $items;
}

// ── FILE WRITERS ──────────────────────────────────────────────
function generateMd($data, $contentKey = null) {
    $out = "---\n";
    foreach ($data as $k => $v) {
        if ($k === $contentKey || $k === '_file') continue;
        if (is_array($v)) $v = '[' . implode(', ', $v) . ']';
        $out .= "$k: $v\n";
    }
    $out .= "---\n";
    if ($contentKey && isset($data[$contentKey])) {
        $out .= "\n" . $data[$contentKey];
    }
    return $out;
}

function saveTool() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = preg_replace('/[^a-z0-9\-]/', '', strtolower($data['id'] ?? uniqid('tool-')));
    if (!$id) $id = 'tool-' . time();
    $file = DATA_DIR . 'tools/' . $id . '.md';
    file_put_contents($file, generateMd($data, 'content'));
    echo json_encode(['success' => true, 'id' => $id]);
}

function deleteTool() {
    $data = json_decode(file_get_contents('php://input'), true);
    $file = DATA_DIR . 'tools/' . ($data['file'] ?? '');
    if (file_exists($file)) { unlink($file); echo json_encode(['success' => true]); }
    else echo json_encode(['success' => false, 'error' => 'File not found']);
}

function savePrompt() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = preg_replace('/[^a-z0-9\-]/', '', strtolower($data['id'] ?? uniqid('prompt-')));
    if (!$id) $id = 'prompt-' . time();
    $file = DATA_DIR . 'prompts/' . $id . '.md';
    file_put_contents($file, generateMd($data, 'text'));
    echo json_encode(['success' => true]);
}

function deletePrompt() {
    $data = json_decode(file_get_contents('php://input'), true);
    $file = DATA_DIR . 'prompts/' . ($data['file'] ?? '');
    if (file_exists($file)) { unlink($file); echo json_encode(['success' => true]); }
    else echo json_encode(['success' => false]);
}

function saveNews() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['date'])) $data['date'] = date('Y-m-d');
    $id = preg_replace('/[^a-z0-9\-]/', '', strtolower($data['id'] ?? ''));
    if (!$id) $id = 'news-' . date('Ymd-His');
    $file = DATA_DIR . 'news/' . $id . '.md';
    file_put_contents($file, generateMd($data, 'content'));
    echo json_encode(['success' => true]);
}

function deleteNews() {
    $data = json_decode(file_get_contents('php://input'), true);
    $file = DATA_DIR . 'news/' . ($data['file'] ?? '');
    if (file_exists($file)) { unlink($file); echo json_encode(['success' => true]); }
    else echo json_encode(['success' => false]);
}

function saveCourse() {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = preg_replace('/[^a-z0-9\-]/', '', strtolower($data['id'] ?? uniqid('course-')));
    if (!$id) $id = 'course-' . time();
    $file = DATA_DIR . 'courses/' . $id . '.md';
    file_put_contents($file, generateMd($data, 'content'));
    echo json_encode(['success' => true]);
}

function saveGlossary() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['content'])) { echo json_encode(['success' => false]); return; }
    file_put_contents(DATA_DIR . 'glossary.md', $data['content']);
    echo json_encode(['success' => true]);
}

function changePassword() {
    $data = json_decode(file_get_contents('php://input'), true);
    $pw = $data['password'] ?? '';
    if (strlen($pw) < 6) { echo json_encode(['success' => false, 'error' => 'Passwort zu kurz (min. 6 Zeichen)']); return; }
    $config = getConfig();
    $config['admin_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    unset($config['note']); // remove placeholder note
    file_put_contents(DATA_DIR . 'config.json', json_encode($config, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
}

// ── AI PROXY ──────────────────────────────────────────────────
function handleAiQuery() {
    $data = json_decode(file_get_contents('php://input'), true);
    $apiKey = $data['apiKey'] ?? '';
    $message = $data['message'] ?? '';
    $model = $data['model'] ?? 'gpt-3.5-turbo';

    if (!$apiKey || !$message) {
        echo json_encode(['error' => 'API-Key oder Nachricht fehlt']);
        return;
    }

    // Sanitize
    $message = substr(strip_tags($message), 0, 2000);

    $payload = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $message]],
        'max_tokens' => 600,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$res) {
        echo json_encode(['error' => 'cURL-Fehler']);
        return;
    }

    $json = json_decode($res, true);
    if ($code !== 200) {
        echo json_encode(['error' => $json['error']['message'] ?? 'API-Fehler ' . $code]);
        return;
    }

    $reply = $json['choices'][0]['message']['content'] ?? 'Keine Antwort';
    echo json_encode(['reply' => $reply]);
}
