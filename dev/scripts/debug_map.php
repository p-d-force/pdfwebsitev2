<?php
require 'C:/projects/pdf-website/dev/app/bootstrap.php';
use App\Core\Database;

// Test via web
$ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
$h = @file_get_contents('http://localhost:8081/data/map', false, $ctx);

if (!$h) {
    echo "NO RESPONSE from web server\n";
} elseif (strpos($h, 'Fatal') !== false) {
    preg_match('/<b>([^<]+)<\/b>/', $h, $m);
    echo "FATAL: " . ($m[1] ?? 'unknown') . "\n";
} elseif (strlen($h) < 500) {
    echo "SHORT: " . strlen($h) . " bytes\n" . $h . "\n";
} else {
    echo "OK " . strlen($h) . " bytes\n";
    echo "cty- in HTML: " . (strpos($h, 'cty-') !== false ? 'YES' : 'NO') . "\n";
    echo "#22c55e in HTML: " . (strpos($h, '#22c55e') !== false ? 'YES' : 'NO') . "\n";
    
    // Check SVG presence
    echo "SVG in HTML: " . (stripos($h, '<svg') !== false ? 'YES' : 'NO') . "\n";
    echo "county-shape: " . (strpos($h, 'county-shape') !== false ? 'YES' : 'NO') . "\n";
    
    // Check JS
    echo "county_colors JSON: " . (strpos($h, 'county_colors') !== false ? 'YES' : 'NO') . "\n";
    echo "getElementById: " . (strpos($h, 'getElementById') !== false ? 'YES' : 'NO') . "\n";
    
    // Check actual color keys
    preg_match('/var colors = ({[^;]+})/', $h, $m);
    if ($m) {
        $colors = json_decode($m[1], true);
        echo "Color keys: " . implode(', ', array_keys($colors)) . "\n";
    }
}
