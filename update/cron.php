<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once __DIR__ . '/app/bootstrap.php';

$status = new \Magento\Update\Status();
if ($status->isUpdateInProgress()) {
    exit('Cron is already in progress...');
}

$backupDirectory = UPDATER_BP . '/var/backup';
if (!file_exists($backupDirectory) && !mkdir($backupDirectory)) {
    $status->add(sprintf('Backup directory "%s" cannot be created.', $backupDirectory));
    exit();
}

try {
    $status->setUpdateInProgress();
} catch (\RuntimeException $e) {
    $status->add($e->getMessage());
    exit();
}

$jobQueue = new \Magento\Update\Queue();
try {
    foreach ($jobQueue->popQueuedJobs() as $job) {
        $status->add(
            sprintf('Job "%s" has been started', $job)
        );
        try {
            $job->execute();
            $status->add(sprintf('Job "%s" has been successfully completed', $job));
        } catch (\Exception $e) {
            $status->add(sprintf('An error occurred while executing job "%s": %s', $job, $e->getMessage()));
        }
    }
} catch (\Exception $e) {
    $status->add($e->getMessage());
} finally {
    $status->setUpdateInProgress(false);
}
