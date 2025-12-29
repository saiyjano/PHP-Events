    <?php
    /* ================= CONFIG ================= */
    $file = "events.txt";
    $path = "admin_event.php";
    /* ========================================== */

    /* ================= LOAD ================= */
    $data = [];
    if (file_exists($file)) {
        $data = array_filter(file($file, FILE_IGNORE_NEW_LINES));
    }

    /* ================= AUTO-DELETE EXPIRED EVENTS ================= */
    $today = date('Y-m-d');
    $new_data = [];

    foreach ($data as $line) {
        [$id, $title, $date, $location] = explode('|', $line, 4);

        if ($date >= $today) {
            $new_data[] = $line;
        }
    }

    if ($new_data !== $data) {
        file_put_contents($file, implode(PHP_EOL, $new_data) . PHP_EOL, LOCK_EX);
    }
    $data = $new_data;
    /* ================================================================== */

    /* ================= ADD NEW EVENT ================= */
    if (
        $_SERVER["REQUEST_METHOD"] === "POST"
        && !empty($_POST["title"])
        && !empty($_POST["date"])
        && !empty($_POST["location"])
    ) {
        $title = trim(str_replace("|", "", $_POST["title"]));
        $date = trim($_POST["date"]);
        $location = trim(str_replace("|", "", $_POST["location"]));

        $id = time();

        $data[] = implode("|", [$id, $title, $date, $location]);

        file_put_contents($file, implode(PHP_EOL, $data) . PHP_EOL, LOCK_EX);

        echo "<div class='ok'><big>&#10004;</big> Event has been added.</div>
          <meta http-equiv='refresh' content='1;url=admin_event.php'>";
        exit;
    }
    /* ================= GROUP EVENTS ================= */
    $groups = [];

    foreach ($data as $line) {
        [$id, $title, $date, $location] = explode('|', $line, 4);

        $month = date('Y-m', strtotime($date)); // e.g., 2025-02
        $key = md5($title . $location . $month);

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'title' => $title,
                'location' => $location,
                'month' => $month,
                'start' => $date,
                'end' => $date,
            ];
        } else {
            if ($date < $groups[$key]['start']) {
                $groups[$key]['start'] = $date;
            }
            if ($date > $groups[$key]['end']) {
                $groups[$key]['end'] = $date;
            }
        }
    }

    /* sort by start date */
    usort($groups, fn($a, $b) => strcmp($a['start'], $b['start']));
    /* ==================================================== */

    function h(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
    ?>
<h2>Add New Event</h2>
                <form method="post" style="text-align: center;">
                    <input name="title" type="text" placeholder="Event Title" required />

                    <input type="date" name="date" required />

                    <input name="location" type="text" placeholder="Location" required />
                    <input type="submit" value="Save" />
                    <button type="button" onclick="window.location.href='<?= $path ?>'">
                        Cancel
                    </button>
                </form>
<hr />
<h2>Upcoming Events</h2>
    <?php if (empty($groups)): ?>
        <p>No upcoming events at the moment.</p>
    <?php else: ?>
        <?php foreach ($groups as $e): ?>
            <div style="float: left; margin: 5px 10px; border:1px solid #ccc; padding:5px; width: 232px">
                <strong><?= h($e['title']) ?></strong>
                <ul>
                    <li><?php if ($e['start'] === $e['end']): ?>
                            <?= date('d.m.Y', strtotime($e['start'])) ?>
                        <?php else: ?>
                            <?= date('d.m.', strtotime($e['start'])) ?>
                            to
                            <?= date('d.m.Y', strtotime($e['end'])) ?>
                        <?php endif; ?>
                    </li>

                    <li><?= h($e['location']) ?></li>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<div style="clear: both;"></div>