<?php
echo "<h1>Diagnostics</h1>";
echo "<h2>Current Directory: " . __DIR__ . "</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    echo "<li>" . $file . "</li>";
}
echo "</ul>";

echo "<h2>API Directory: " . __DIR__ . "/api</h2>";
if (is_dir(__DIR__ . '/api')) {
    $apiFiles = scandir(__DIR__ . '/api');
    echo "<ul>";
    foreach ($apiFiles as $file) {
        echo "<li>" . $file . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>API directory not found.</p>";
}

echo "<h2>Health Directory: " . __DIR__ . "/health</h2>";
if (is_dir(__DIR__ . '/health')) {
    $healthFiles = scandir(__DIR__ . '/health');
    echo "<ul>";
    foreach ($healthFiles as $file) {
        echo "<li>" . $file . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Health directory not found.</p>";
}
?>
