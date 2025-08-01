<?php
// ================================================================
// SCRIPT DE VERIFICACIÓN - Agregar temporalmente a Render
// Crear archivo: public/verify.php
// ================================================================
header('Content-Type: application/json');

echo json_encode([
    'current_directory' => __DIR__,
    'snippets_dir' => [
        'path' => __DIR__ . '/snippets/',
        'exists' => is_dir(__DIR__ . '/snippets/'),
        'files' => is_dir(__DIR__ . '/snippets/') ? scandir(__DIR__ . '/snippets/') : 'directory not found'
    ],
    'code_snippets_dir' => [
        'path' => __DIR__ . '/code_snippets/',
        'exists' => is_dir(__DIR__ . '/code_snippets/'),
        'files' => is_dir(__DIR__ . '/code_snippets/') ? scandir(__DIR__ . '/code_snippets/') : 'directory not found'
    ],
    'all_files' => scandir(__DIR__),
    'php_files_in_root' => glob(__DIR__ . '/*.php'),
    'php_files_in_snippets' => is_dir(__DIR__ . '/snippets/') ? glob(__DIR__ . '/snippets/*.php') : [],
    'php_files_in_code_snippets' => is_dir(__DIR__ . '/code_snippets/') ? glob(__DIR__ . '/code_snippets/*.php') : []
], JSON_PRETTY_PRINT);
?>
