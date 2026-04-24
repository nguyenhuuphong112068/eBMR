<?php
$htmls = [
    '<p class="MsoNormal" style="..."><span style="..."><o:p> Sản phẩm </o:p></span></p>',
    '<p>A <b>B</b> C</p>',
    'Just text',
    '<div>Hello <br> World</div>'
];

foreach ($htmls as $html) {
    $dom = new \DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    $text = '';
    $xpath = new \DOMXPath($dom);
    $textNodes = $xpath->query('//text()');
    
    foreach ($textNodes as $node) {
        $text .= $node->nodeValue;
    }
    $text = trim($text);
    
    $first = true;
    foreach ($textNodes as $node) {
        if (trim($node->nodeValue) !== '') {
            if ($first) {
                $node->nodeValue = "[[CONTENT_ID]]";
                $first = false;
            } else {
                $node->nodeValue = '';
            }
        }
    }
    
    $structure = '';
    foreach ($dom->childNodes as $child) {
        $structure .= $dom->saveHTML($child);
    }
    
    echo "Original: " . $html . "\n";
    echo "Text:     " . $text . "\n";
    echo "Struct:   " . $structure . "\n\n";
}
