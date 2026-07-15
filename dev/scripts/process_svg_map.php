<?php
$svg = file_get_contents(__DIR__ . '/../public/assets/images/ma-counties-raw.svg');
$svg = preg_replace('/ fill="[^"]*"/', ' fill="#1d1d1d"', $svg);
$svg = preg_replace('/ stroke="white"/', ' stroke="#2a2a2a"', $svg);
$svg = str_replace(' transform="translate(5,5)"', '', $svg);
$svg = preg_replace_callback('/id="(\w+)"/', function($m) {
    return 'id="cty-'.strtolower($m[1]).'" data-county="'.$m[1].'" class="county-shape"';
}, $svg);
$style = "<style>.county-shape{cursor:pointer;transition:fill 0.3s;stroke:#2a2a2a;stroke-width:1}.county-shape:hover{stroke:#ff5a1f!important;stroke-width:2!important}</style>";
$svg = str_replace('stroke-linejoin="round">', 'stroke-linejoin="round">'."\n".$style, $svg);
file_put_contents(__DIR__ . '/../public/assets/images/ma-counties.svg', $svg);
echo 'Processed: '.strlen($svg).' bytes';
