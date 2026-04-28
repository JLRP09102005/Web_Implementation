<?php
// NUCLEAR DEBUG - muestra TODO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

echo "<h1>🔥 NUCLEAR DEBUG</h1>";
echo "1. getcwd(): " . getcwd() . "<br>";
echo "2. __DIR__: " . __DIR__ . "<br>";
echo "3. Archivo actual: " . __FILE__ . "<br><br>";

// Test archivos
$paths = [
    'autoload.php' => 'autoload.php',
    'src/' => __DIR__ . '/../src/',
    'src/Core/' => __DIR__ . '/../src/Core/',
    'Test.php' => __DIR__ . '/../src/Core/Test.php'
];

foreach ($paths as $name => $path) {
    echo "$name: " . (is_file($path) || is_dir($path) ? '✅ ' . realpath($path) : '❌') . "<br>";
}

echo "<br>4. Contenido autoload.php:<br><pre>";
if (file_exists('autoload.php')) {
    echo htmlspecialchars(file_get_contents('autoload.php'));
}
echo "</pre>";

echo "<br>5. Contenido Test.php:<br><pre>";
$test_path = __DIR__ . '/../src/Core/Test.php';
if (file_exists($test_path)) {
    echo htmlspecialchars(file_get_contents($test_path));
} else {
    echo "❌ NO existe";
}
echo "</pre>";

// Intentar cargar
echo "<br>6. Intentando cargar:<br>";
try {
    require_once 'autoload.php';
    echo "Autoload cargado OK<br>";
    
    if (class_exists('App\\Core\\Test')) {
        echo "✅ Test existe<br>";
        echo App\\Core\\Test::hola();
    } else {
        echo "❌ Test NO existe<br>";
        var_dump(get_declared_classes());
    }
} catch (Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

$errors = ob_get_contents();
ob_end_clean();
echo nl2br($errors);
?>