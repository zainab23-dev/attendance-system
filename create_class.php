<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
require_login();
require_once 'config/db.php';

$teacherId = current_teacher_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = trim((string) ($_POST['class_name'] ?? ''));
    $semester = trim((string) ($_POST['semester'] ?? ''));
    $totalWeeks = (int) ($_POST['total_weeks'] ?? 16);

    if ($className === '' || $semester === '') {
        flash('error', 'Class name and semester are required.');
        redirect('create_class.php');
    }

    if ($totalWeeks < 1 || $totalWeeks > 30) {
        flash('error', 'Total weeks must be between 1 and 30.');
        redirect('create_class.php');
    }

    $stmt = $conn->prepare('INSERT INTO classes (teacher_id, class_name, semester, total_weeks) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('issi', $teacherId, $className, $semester, $totalWeeks);
    $stmt->execute();
    $stmt->close();

    flash('success', 'Class created successfully.');
    redirect('view_classes.php');
}

page_header('Create Class', 'classes');
?>

<section class="page-title">
    <div>
        <h1>Create Class</h1>
        <p>Add a class section with semester details and the number of weeks you want to track.</p>
    </div>
    <a class="button button-secondary" href="view_classes.php">Back to Classes</a>
</section>

<section class="panel">
    <form method="POST" action="create_class.php" class="form-grid">
        <div class="field">
            <label for="class_name">Class Name</label>
            <input type="text" id="class_name" name="class_name" placeholder="Data Structures" required>
        </div>
        <div class="field">
            <label for="semester">Semester</label>
            <input type="text" id="semester" name="semester" placeholder="Fall 2026" required>
        </div>
        <div class="field">
            <label for="total_weeks">Total Weeks</label>
            <input type="number" id="total_weeks" name="total_weeks" min="1" max="30" value="16" required>
        </div>
        <div class="form-actions field-full">
            <button type="submit">Create Class</button>
        </div>
    </form>
</section>

<?php page_footer(); ?>
