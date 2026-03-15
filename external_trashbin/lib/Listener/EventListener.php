<?php

declare(strict_types=1);

namespace OCA\External_Trashbin\Listener;

use OCP\EventDispatcher\IEventListener;
use OCP\EventDispatcher\Event;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
use OC\Files\Filesystem;
use OCP\Files\Storage\IStorage;
use OCP\Files\Mount\IMountPoint;
use OCA\External_Trashbin\Wrapper\ExternalRecycleWrapper;

class EventListener implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof BeforeFileSystemSetupEvent)) {
            return;
        }
        Filesystem::addStorageWrapper(
            'external_trashbin',
            function (string $mountPoint, IStorage $storage, IMountPoint $mount) {
                if ($mount instanceof \OCA\Files_External\Config\ExternalMountPoint) {
                    return new ExternalRecycleWrapper(['storage' => $storage, 'mountPoint' => $mountPoint]);
                }
                return $storage;
            },
        15);
	}
}
