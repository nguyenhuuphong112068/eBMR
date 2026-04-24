<?php
$htmls = [
    '<p class="MsoNormal" style="..."><span style="..."><o:p> Sản phẩm </o:p></span></p>',
    '<p>A <b>B</b> C</p>',
    'Just text',
    '<div>Hello <br> World</div>',
    'all the rejected tablet/ <i>capsules</i>'
];

foreach ($htmls as $html) {
    $text = trim(strip_tags($html));
    
    $first = true;
    $structure = preg_replace_callback('/>([^<]+)</', function($matches) use (&$first) {
        if (trim($matches[1]) !== '') {
            if ($first) {
                $first = false;
                return '>[CONTENT_ID]<';
            } else {
                return '><';
            }
        }
        return $matches[0];
    }, '>' . $html . '<');
    
    $structure = substr($structure, 1, -1);
    
    echo "Original: " . $html . "\n";
    echo "Text:     " . $text . "\n";
    echo "Struct:   " . $structure . "\n\n";
}
