<?php
$html = '<table><tr><td>A <b>B</b></td><td class="test">Hello <o:p>World</o:p></td></tr></table>';
preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $html, $matches);
print_r($matches[1]);
