<?php
// index.php CORREGIDO
require_once __DIR__ . '/startup.php';

header("Content-Type: application/json");
echo json_encode([
    "service" => "Lumina Snippet Executor",
    "status" => "online", 
    "version" => "1.0.0",
    "endpoints" => [
        "health" => "/health.php",
        "save" => "/save-snippet.php", 
        "execute" => "/execute-snippet.php",
        "backup" => "/backup-restore.php",
        "file-viewer" => "/file-viewer.php"
    ]
]);
?>
