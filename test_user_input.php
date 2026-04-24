<?php
$html = '<p class="MsoNormal" style="text-align: justify; line-height: 115%; font-size: 14pt; font-family: Inter, Roboto, sans-serif;"><span style="color: black; font-size: 14pt; font-family: Inter, Roboto, sans-serif;">Nhân viên vận hành máy hàn/gút và cắt đoạn
túi chứa viên bị loại ra bởi máy dò kim loại vào cuối mỗi ca/ lô, đếm số lượng
viên ghi nhận trong bảng sau.<o:p style="font-size: 14pt; font-family: Inter, Roboto, sans-serif;"></o:p></span></p>';
$placeholder = "[[CONTENT_123]]";

$first = true;
$structure = preg_replace_callback('/>([^<]+)</', function($matches) use (&$first, $placeholder) {
    if (trim($matches[1]) !== '') {
        if ($first) {
            $first = false;
            return '>' . $placeholder . '<';
        } else {
            return '><'; // Clear subsequent text nodes
        }
    }
    return $matches[0];
}, '>' . $html . '<'); // wrap in >< to catch text at the very beginning/end

$structure = substr($structure, 1, -1);
echo $structure;
