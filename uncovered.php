<?php
$x = simplexml_load_file("coverage.xml");
foreach ($x->xpath("//file") as $f) {
    foreach ($f->line as $l) {
        if ((int) $l["count"] === 0) {
            echo $l["type"] . " " . $f["name"] . ":" . $l["num"] . " " . ($l["name"] ?? "") . PHP_EOL;
        }
    }
}