<?php
$svg = file_get_contents(__DIR__ . '/../public/assets/images/ma-towns-raw.svg');

// Strip original fills
$svg = preg_replace('/ fill="[^"]*"/', ' fill="#1d1d1d"', $svg);
$svg = preg_replace('/ stroke="white"/', ' stroke="#2a2a2a"', $svg);
$svg = str_replace(' transform="translate(5,5)"', '', $svg);

// Convert town IDs: "Paxton town" → "town-paxton" with data-town="Paxton"
$svg = preg_replace_callback(
    '/id="([^"]+) town"/',
    function($m) {
        $slug = strtolower(str_replace([' ', "'", '.'], '-', $m[1]));
        return 'id="town-' . $slug . '" data-town="' . $m[1] . '" class="town-shape"';
    },
    $svg
);

// Also handle the container group
$svg = preg_replace('/id="Massachusetts Towns"/', 'id="ma-towns-container"', $svg);

// Add CSS
$style = "<style>\n.town-shape{cursor:pointer;transition:fill 0.3s;stroke:#1a1a1a;stroke-width:0.5}\n.town-shape:hover{stroke:#ff5a1f!important;stroke-width:1!important}\n</style>";
$svg = str_replace('stroke-linejoin="round">', 'stroke-linejoin="round">' . "\n" . $style, $svg);

file_put_contents(__DIR__ . '/../public/assets/images/ma-towns.svg', $svg);

preg_match_all('/id="town-([^"]+)"/', $svg, $m);
echo 'Processed: ' . strlen($svg) . ' bytes, ' . count($m[1]) . ' towns: ' . implode(', ', array_slice($m[1], 0, 10)) . '...' . "\n";
