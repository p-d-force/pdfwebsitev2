<?php
$svg = file_get_contents('https://upload.wikimedia.org/wikipedia/commons/5/58/Massachusetts_county_map%2C_cb_500k.svg');
if (!$svg) { echo "DOWNLOAD FAILED\n"; exit(1); }

// Replace pastel fills with dark theme base
$svg = preg_replace('/ fill="[^"]*"/', ' fill="#1d1d1d"', $svg);

// Replace white stroke with dark border
$svg = preg_replace('/ stroke="white"/', ' stroke="#2a2a2a"', $svg);

// Remove Wikimedia transform
$svg = str_replace(' transform="translate(5,5)"', '', $svg);

// Add data attributes and class to county paths
$svg = preg_replace_callback(
    '/id="(\w+)"/',
    function($m) {
        return 'id="cty-' . strtolower($m[1]) . '" data-county="' . $m[1] . '" class="county-shape"';
    },
    $svg
);

// Inject dark-theme CSS
$style = "<style>\n.county-shape{cursor:pointer;transition:fill 0.3s;stroke:#2a2a2a;stroke-width:1}\n.county-shape:hover{stroke:#ff5a1f!important;stroke-width:2!important}\n</style>";
$svg = str_replace('stroke-linejoin="round">', 'stroke-linejoin="round">' . "\n" . $style, $svg);

file_put_contents('C:/projects/pdf-website/dev/public/assets/images/ma-counties.svg', $svg);

preg_match_all('/id="cty-(\w+)"/', $svg, $m);
echo 'Saved: ' . strlen($svg) . ' bytes, ' . count($m[1]) . ' counties: ' . implode(', ', $m[1]) . "\n";
