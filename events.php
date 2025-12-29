i<?php
$dateFile = "events.txt";
$today = date('Y-m-d');
$start = date('Y-m-01');
$end = date('Y-m-t', strtotime('+2 months'));

$lines = file_exists($dateFile) ? array_filter(file($dateFile, FILE_IGNORE_NEW_LINES)) : [];
$lines = array_values(array_filter($lines, fn($z) => explode('|', $z)[2] >= $today));
file_put_contents($dateFile, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);

$events = [];
foreach ($lines as $z) {
    [$id, $title, $date, $location] = explode('|', $z, 4);
    if ($date < $start || $date > $end)
        continue;
    $key = md5($title . $location . date('Y-m', strtotime($date)));
    if (!isset($events[$key]))
        $events[$key] = ['title' => $title, 'location' => $location, 'start' => $date, 'end' => $date];
    else {
        $events[$key]['start'] = min($events[$key]['start'], $date);
        $events[$key]['end'] = max($events[$key]['end'], $date);
    }
}
usort($events, fn($a, $b) => strcmp($a['start'], $b['start']));
?>

<?php if (!$events): ?>
    <p>No upcoming events.</p>
<?php else: ?>
    <?php foreach ($events as $e): ?>
        <div>
            <strong><?= htmlspecialchars($e['title']) ?></strong>
            -
            <?=
                $e['start'] === $e['end'] ?
                date('d.m.Y', strtotime($e['start'])) :
                date('d.m.', strtotime($e['start'])) . " – " .
                date('d.m.Y', strtotime($e['end']))
                ?>
            | <?= htmlspecialchars($e['location']) ?>
        </div><br>
    <?php endforeach; ?>
<?php endif; ?>