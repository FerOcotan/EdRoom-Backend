<?php
// Parse .env for DB credentials (basic parser)
$env = file_get_contents(__DIR__ . '/../.env');
$matches = [];
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

$mysqli = new mysqli($host, $user, $pass, $db, (int)$port);
if ($mysqli->connect_error) {
    echo "CONNECT_ERROR: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

$res = $mysqli->query("SHOW COLUMNS FROM session_tokens");
if (!$res) {
    echo "QUERY_ERROR: " . $mysqli->error . PHP_EOL;
    exit(1);
}
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows, JSON_PRETTY_PRINT);
$mysqli->close();
