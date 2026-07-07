<?php
require_once 'config/database.php';
require_once 'lib/services_helpers.php';

echo "Testing services_count with status filter...\n";
try {
    $count = services_count(['status' => 'active']);
    echo "Count: $count\n";
} catch (Exception $e) {
    echo "Error in services_count: " . $e->getMessage() . "\n";
}

echo "\nTesting services_paginated with status filter...\n";
try {
    $services = services_paginated(1, 10, ['status' => 'active']);
    echo "Services count: " . count($services) . "\n";
} catch (Exception $e) {
    echo "Error in services_paginated: " . $e->getMessage() . "\n";
}
