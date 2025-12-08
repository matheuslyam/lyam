<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($id && $status) {
        // Update funnel_stage instead of status_kanban
        $stmt = $pdo->prepare("UPDATE leads SET funnel_stage = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}
?>