<?php
// services/show.php (snippet)
require_once __DIR__ . '/../lib/services_helpers.php';
$service = service_find($service_id);
include __DIR__ . '/../partials/service_rating_widget.php';
?>
