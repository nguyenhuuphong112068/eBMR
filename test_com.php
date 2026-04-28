<?php
try {
    $word = new COM("word.application");
    echo "OK";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
