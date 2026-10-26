<?php
/**
 * Slot Availability Feed
 * Returns how many appointments each time slot already holds for a date,
 * so the booking form can show what is left instead of guessing.
 */

require_once 'db.php';

header('Content-Type: application/json');

// The slots offered on the booking form
$slotTimes = [
    '09:00:00',
    '10:00:00',
    '11:00:00',
    '12:00:00',
    '13:00:00',
    '14:00:00',
    '15:00:00',
    '16:00:00',
    '17:00:00'
];

$date = trim($_GET['date'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['date' => $date, 'max' => MAX_APPOINTMENTS_PER_SLOT, 'slots' => []]);
    exit;
}

$bookedBySlot = array_fill_keys($slotTimes, 0);

$rows = getAllRows(
    "SELECT time, COUNT(*) as count
     FROM appointments
     WHERE date = ? AND status != 'rejected'
     GROUP BY time",
    [$date]
);

foreach ($rows as $row) {
    // Normalise "09:00", "9:00:00" and "09:00:00" to the same key
    $time = date('H:i:s', strtotime($row['time']));
    if (isset($bookedBySlot[$time])) {
        $bookedBySlot[$time] = (int)$row['count'];
    }
}

$slots = [];
foreach ($bookedBySlot as $time => $booked) {
    $remaining = max(0, MAX_APPOINTMENTS_PER_SLOT - $booked);
    $slots[] = [
        'time' => $time,
        'booked' => $booked,
        'remaining' => $remaining,
        'max' => MAX_APPOINTMENTS_PER_SLOT,
        'available' => $remaining > 0
    ];
}

echo json_encode([
    'date' => $date,
    'max' => MAX_APPOINTMENTS_PER_SLOT,
    'slots' => $slots
]);
