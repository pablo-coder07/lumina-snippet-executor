echo '<?php
header("Content-Type: application/json");
echo json_encode([
    "service" => "Lumina Snippet Executor",
    "status" => "online",
    "version" => "1.0.0",
    "endpoints" => [
        "health" => "/health.php",
        "save" => "/save-snippet.php", 
        "execute" => "/execute-snippet.php"
    ]
]);
?>' > public/index.php
