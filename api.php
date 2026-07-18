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
    case 'rss_feeds': echo json_encode(['feeds' => getRssFeeds()]); break;
    case 'rss_news':  echo json_encode(['items' => getRssNews()]); break;

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
    case 'admin_save_rss_feeds': requireAdmin(); saveRssFeeds(); break;
    case 'admin_refresh_rss':  requireAdmin(); refreshRssCache(); break;

    default: echo json_encode(['error' => 'Unknown action']);
}

// f4cd AUTHENTICATION f4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cd
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

    // First-run: placeholder hash  accept "admin123" and replace hash
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
    // Default config (password: "admin123"  NDERN!)
    return [
        'admin_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'site_title' => 'KI-Dashboard',
        'api_proxy' => false,
    ];
}

// f4cd DATA READERS f4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cd
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
    $rssItems = getRssNews();
    $tools = getTools();
    $items = [];
    foreach (array_slice($rssItems, 0, 3) as $n) $items[] = $n['title'] ?? '';
    foreach (array_slice($news, 0, 3) as $n) $items[] = $n['title'] ?? '';
    foreach (array_slice($tools, 0, 2) as $t) $items[] = 'Neues Tool: ' . ($t['name'] ?? '');
    return array_filter($items);
}

function getDashboardData() {
    $rssItems = getRssNews();
    return [
        'stats' => [
            'tools'   => count(getTools()),
            'prompts' => count(getPrompts()),
            'courses' => count(getCourses()),
            'news'    => count($rssItems),
        ],
        'news' => array_slice($rssItems, 0, 5),
    ];
}

// f4f0 RSS FEED FUNCTIONS f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0f4f0
function getRssFeeds() {
    $config = getConfig();
    return $config['rss_feeds'] ?? [
        ['url' => 'https://www.drweb.de/technologie-innovation/ki/feed/', 'title' => 'DrWeb KI', 'enabled' => true],
        ['url' => 'https://www.heise.de/thema/Kuenstliche-Intelligenz.xml', 'title' => 'Heise KI', 'enabled' => true],
        ['url' => 'https://the-decoder.de/feed/', 'title' => 'The Decoder', 'enabled' => true],
    ];
}

function getRssNews() {
    $feeds = getRssFeeds();
    $allItems = [];
    $cacheFile = DATA_DIR . 'rss_cache.json';
    $cacheTime = 3600; // 1 hour cache
    
    // Try to load from cache first
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && isset($cached['items'])) {
            return $cached['items'];
        }
    }
    
    // Fetch fresh data
    foreach ($feeds as $feed) {
        if (empty($feed['enabled'])) continue;
        $items = fetchRssFeed($feed['url'], $feed['title']);
        $allItems = array_merge($allItems, $items);
    }
    
    // Sort by date descending
    usort($allItems, function($a, $b) {
        return strcmp($b['pubDate'] ?? '', $a['pubDate'] ?? '');
    });
    
    // Limit to 50 items
    $allItems = array_slice($allItems, 0, 50);
    
    // Save to cache
    file_put_contents($cacheFile, json_encode(['items' => $allItems, 'cached_at' => time()], JSON_PRETTY_PRINT));
    
    return $allItems;
}

function fetchRssFeed($url, $feedTitle) {
    $items = [];
    
    // Use cURL to fetch the feed
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; KI-Dashboard RSS Reader)');
    
    $xml = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$xml) {
        return [];
    }
    
    // Parse XML
    try {
        $rss = new SimpleXMLElement($xml);
        
        // Handle different RSS namespaces
        $ns = $rss->getNamespaces(true);
        
        // Check if it's Atom feed
        if (isset($ns['http://www.w3.org/2005/Atom'])) {
            $items = parseAtomFeed($rss, $feedTitle);
        } else {
            $items = parseRssFeed($rss, $feedTitle);
        }
    } catch (Exception $e) {
        // Try to parse as HTML or other format
        return [];
    }
    
    return $items;
}

function parseRssFeed($rss, $feedTitle) {
    $items = [];
    
    foreach ($rss->channel->item as $item) {
        $title = (string)($item->title ?? '');
        $link = (string)($item->link ?? '');
        $description = (string)($item->description ?? '');
        $pubDate = (string)($item->pubDate ?? '');
        
        // Clean up description - remove HTML tags
        $description = strip_tags($description);
        
        // If no date, use current time
        if (empty($pubDate)) {
            $pubDate = date('r');
        }
        
        // Format date to Y-m-d
        try {
            $dateObj = new DateTime($pubDate);
            $formattedDate = $dateObj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $formattedDate = date('Y-m-d H:i:s');
        }
        
        $items[] = [
            'id' => 'rss-' . md5($link . $title),
            'title' => $title,
            'content' => $description,
            'url' => $link,
            'date' => $formattedDate,
            'pubDate' => $pubDate,
            'source' => $feedTitle,
            'category' => 'RSS',
            'color' => '',
        ];
    }
    
    return $items;
}

function parseAtomFeed($feed, $feedTitle) {
    $items = [];
    
    foreach ($feed->entry as $entry) {
        $title = (string)($entry->title ?? '');
        $link = (string)($entry->link['href'] ?? '');
        $description = (string)($entry->summary ?? $entry->content ?? '');
        $pubDate = (string)($entry->published ?? $entry->updated ?? '');
        
        // Clean up description
        $description = strip_tags($description);
        
        if (empty($pubDate)) {
            $pubDate = date('r');
        }
        
        try {
            $dateObj = new DateTime($pubDate);
            $formattedDate = $dateObj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $formattedDate = date('Y-m-d H:i:s');
        }
        
        $items[] = [
            'id' => 'rss-' . md5($link . $title),
            'title' => $title,
            'content' => $description,
            'url' => $link,
            'date' => $formattedDate,
            'pubDate' => $pubDate,
            'source' => $feedTitle,
            'category' => 'RSS',
            'color' => '',
        ];
    }
    
    return $items;
}

// f4cd GENERIC MD FILE READER f4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cd
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

// f4cd FILE WRITERS f4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cd
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

function saveRssFeeds() {
    $data = json_decode(file_get_contents('php://input'), true);
    $feeds = $data['feeds'] ?? [];
    
    $config = getConfig();
    $config['rss_feeds'] = $feeds;
    
    file_put_contents(DATA_DIR . 'config.json', json_encode($config, JSON_PRETTY_PRINT));
    
    // Refresh cache after saving feeds
    refreshRssCache();
    
    echo json_encode(['success' => true]);
}

function refreshRssCache() {
    // Force refresh by deleting cache
    $cacheFile = DATA_DIR . 'rss_cache.json';
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
    
    // Fetch fresh data
    $items = getRssNews();
    
    // Save to cache
    file_put_contents($cacheFile, json_encode(['items' => $items, 'cached_at' => time()], JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true, 'items' => count($items)]);
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

// f4cd AI PROXY f4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cdf4cd
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
