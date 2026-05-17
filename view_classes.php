<?php
declare(strict_types=1);

require_once 'includes/helpers.php';
require_login();
require_once 'config/db.php';

$teacherId = current_teacher_id();

$stmt = $conn->prepare(
    'SELECT c.id,
            c.class_name,
            c.semester,
            c.total_weeks,
            c.created_at,
            COUNT(DISTINCT s.id) AS student_count,
            COUNT(a.id) AS attendance_count
     FROM classes c
     LEFT JOIN students s ON s.class_id = c.id
     LEFT JOIN attendance a ON a.class_id = c.id
     WHERE c.teacher_id = ?
     GROUP BY c.id, c.class_name, c.semester, c.total_weeks, c.created_at
     ORDER BY c.created_at DESC'
);
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

page_header('Your Classes', 'classes');
?>

<section class="page-title">
    <div>
        <h1>Your Classes</h1>
        <p>Open a class to add students, mark attendance, or inspect attendance reports.</p>
    </div>
    <a class="button" href="create_class.php">Create Class</a>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Class List</h2>
            <p class="panel-subtitle"><?php echo count($classes); ?> class records available.</p>
        </div>
    </div>

    <?php if ($classes) { ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Semester</th>
                        <th>Weeks</th>
                        <th>Students</th>
                        <th>Records</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class) { ?>
                        <tr>
                            <td><strong><?php echo e($class['class_name']); ?></strong></td>
                            <td><?php echo e($class['semester']); ?></td>
                            <td><?php echo (int) $class['total_weeks']; ?></td>
                            <td><span class="badge"><?php echo (int) $class['student_count']; ?> students</span></td>
                            <td><?php echo (int) $class['attendance_count']; ?></td>
                            <td>
                                <div class="actions">
                                    <a class="link-button" href="mark_attendance.php?class_id=<?php echo (int) $class['id']; ?>">Mark</a>
                                    <a class="link-button" href="add_students.php?class_id=<?php echo (int) $class['id']; ?>">Students</a>
                                    <a class="link-button" href="attendance_report.php?class_id=<?php echo (int) $class['id']; ?>">Report</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="empty-state">
            You have not created any classes yet.
            <div class="empty-action">
                <a class="button" href="create_class.php">Create Your First Class</a>
            </div>
        </div>
    <?php } ?>
</section>

<?php page_footer(); ?>
