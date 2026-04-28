<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>🔍 DEBUG EXACTO</h3>";

echo "Paso 1: PHP OK ✅<br>";
require_once 'autoload.php';
echo "Paso 2: Autoload cargado ✅<br>";

echo "Paso 3: Clase existe? ";
if (class_exists('App\\Core\\Test')) {
    echo "✅ SÍ<br>";
    echo "Test::hola(): " . App\\Core\\Test::hola() . "<br>";
} else {
    echo "❌ NO<br>";
    echo "Path esperado: " . __DIR__ . '/../src/Core/Test.php' . "<br>";
    echo "Existe: " . (file_exists(__DIR__ . '/../src/Core/Test.php') ? '✅' : '❌') . "<br>";
    
    // Contenido Test.php
    $test_path = __DIR__ . '/../src/Core/Test.php';
    if (file_exists($test_path)) {
        echo "<strong>Contenido Test.php:</strong><br><pre>";
        echo htmlspecialchars(file_get_contents($test_path));
        echo "</pre>";
    }
}
?>