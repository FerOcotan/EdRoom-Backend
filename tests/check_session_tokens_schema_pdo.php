<?php
$env = file_get_contents(__DIR__ . '/../.env');
$creds = [];
foreach (explode("\n", $env) as $line) {
    if (preg_match('/^DB_([A-Z]+)=(.*)$/', $line, $m)) {
        $creds[$m[1]] = trim($m[2], " \t\n\r\0\x0B\"");
    }
}
$host = $creds['HOST'] ?? '127.0.0.1';
$port = $creds['PORT'] ?? '3306';
$db = $creds['DATABASE'] ?? '';
$user = $creds['USERNAME'] ?? 'root';
$pass = $creds['PASSWORD'] ?? '';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "CONNECT_ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM session_tokens");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "QUERY_ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
