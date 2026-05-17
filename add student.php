<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
require_login();

$classId = (int) ($_GET['class_id'] ?? 0);

if ($classId > 0) {
    redirect('add_students.php?class_id=' . $classId);
}

redirect('dashboard.php');
