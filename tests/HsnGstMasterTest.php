<?php

require_once __DIR__ . '/../database/hsn_gst_master.php';

$rows = hsnGstMasterRows();
$byCode = [];
$failures = [];

foreach ($rows as $row) {
    $key = $row['code_type'] . ':' . $row['hsn_code'];
    if (isset($byCode[$key])) {
        $failures[] = "Duplicate master code {$key}";
    }
    $byCode[$key] = $row;

    if (!in_array($row['code_type'], ['HSN', 'SAC'], true)) {
        $failures[] = "Invalid code type for {$key}";
    }
    if (!preg_match('/^\d{4}(?:\d{2}){0,2}$/', $row['hsn_code'])) {
        $failures[] = "Invalid HSN/SAC length for {$key}";
    }
}

foreach ([
    'HSN:5201',
    'HSN:5208',
    'HSN:6109',
    'HSN:6111',
    'HSN:6204',
    'HSN:6301',
    'HSN:6302',
    'HSN:9606',
    'HSN:9607',
    'HSN:5810',
    'HSN:8452',
    'SAC:9987',
    'SAC:998821',
] as $requiredCode) {
    if (!isset($byCode[$requiredCode])) {
        $failures[] = "Missing classification {$requiredCode}";
    }
}

if (!array_key_exists('gst_rate', $byCode['HSN:6109']) || $byCode['HSN:6109']['gst_rate'] !== null || empty($byCode['HSN:6109']['rate_note'])) {
    $failures[] = 'Garment GST rates must remain conditional and documented.';
}
if (($byCode['HSN:8452']['gst_rate'] ?? null) !== 18.00) {
    $failures[] = 'Sewing machinery standard rate is missing.';
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo count($rows) . " HSN/SAC master rows passed." . PHP_EOL;
