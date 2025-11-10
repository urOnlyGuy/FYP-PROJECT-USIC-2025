<?php
// This file should be called by a cron job daily
// Example cron: 0 9 * * * /usr/bin/php /path/to/cron/check_reminders.php

require_once __DIR__ . '/../includes/firebase.php';

$result = check_and_send_reminders();

echo date('d-m-Y H:i:s') . " - Checked reminders. Sent " . $result['sent'] . " notification(s).\n";