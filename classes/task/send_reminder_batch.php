<?php
namespace mod_videotrackerpremium\task;

use core\task\adhoc_task;
use mod_videotrackerpremium\local\service\reminder_service;

/**
 * Sends a bounded notification batch.
 */
class send_reminder_batch extends adhoc_task {
    public function execute(): void {
        $data = $this->get_custom_data();
        $ids = array_values(array_unique(array_map('intval', (array)$data->notificationids)));
        foreach (array_slice($ids, 0, 100) as $notificationid) {
            reminder_service::send($notificationid);
        }
    }
}
