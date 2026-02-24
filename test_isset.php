<?php
$json = '{"group_id": 1, "is_expanded": false}';
$input = json_decode($json, true);

if (!isset($input['group_id']) || !isset($input['is_expanded'])) {
    echo "Missing parameters.\n";
} else {
    echo "Parameters are set.\n";
}
?>
