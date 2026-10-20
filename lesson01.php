<?php

$equipment = [
    ["name" => "LED PAR", "stock" => 8, "required" => 10],
    ["name" => "Spotlight", "stock" => 5, "required" => 3],
    ["name" => "Moving Light", "stock" => 2, "required" => 6]
];

foreach ($equipment as $item) {
    if ($item["stock"] < $item["required"]) {
        $shortage = $item["required"] - $item["stock"];

        echo $item["name"] . ":" . $shortage . "台不足" . PHP_EOL;
    }
}