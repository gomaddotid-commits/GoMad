<?php
echo "Step 1: PHP is running<br>";

// Test autoloader
echo "Step 2: Testing autoloader...<br>";
require_once __DIR__ . '/../vendor/autoload.php';
echo "Step 3: Autoloader loaded successfully<br>";

// Test app
echo "Step 4: Testing app bootstrap...<br>";
$app = require_once __DIR__ . '/../bootstrap/app.php';
echo "Step 5: App bootstrapped successfully<br>";

// Test kernel
echo "Step 6: Testing kernel...<br>";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
echo "Step 7: Kernel created successfully<br>";

echo "Step 8: All checks passed! 🎉";
