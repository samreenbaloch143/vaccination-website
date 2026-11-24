<?php
require_once __DIR__ . '/../includes/dashboard_layout.php';
dashboard_require_role('admin');

$result = $conn->query(
    "SELECT r.id, r.date_administered, r.dose, r.notes,
            c.child_name, p.name AS parent_name,
            h.hospital_name, v.name AS vaccine_name
     FROM vaccination_records r
     INNER JOIN children c ON c.id = r.child_id
     INNER JOIN parents p ON p.id = c.parent_id
     INNER JOIN hospitals h ON h.id = r.hospital_id
     INNER JOIN vaccines v ON v.id = r.vaccine_id
     ORDER BY r.date_administered DESC, r.id DESC"
);
dashboard_start('Vaccination Records');
?>
<section class="panel table-wrap">
    <table>
        <thead><tr><th>Child / Parent</th><th>Vaccine</th><th>Dose</th><th>Date administered</th><th>Hospital</th><th>Notes</th></tr></thead>
        <tbody>
        <?php while ($record = $result->fetch_assoc()): ?>
            <tr>
                <td><?= dashboard_e($record['child_name']) ?><br><small><?= dashboard_e($record['parent_name']) ?></small></td>
                <td><?= dashboard_e($record['vaccine_name']) ?></td>
                <td><?= dashboard_e($record['dose']) ?></td>
                <td><?= dashboard_e($record['date_administered']) ?></td>
                <td><?= dashboard_e($record['hospital_name']) ?></td>
                <td><?= dashboard_e($record['notes']) ?></td>
            </tr>
        <?php endwhile; ?>
        <?php if ($result->num_rows === 0): ?><tr><td colspan="6">No vaccination records found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
<?php dashboard_end(); ?>
