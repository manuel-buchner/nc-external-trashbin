<?php

declare(strict_types=1);

namespace OCA\External_Trashbin\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\Files\Events\BeforeFileSystemSetupEvent;
use OCA\External_Trashbin\Listener\EventListener;

class Application extends App implements IBootstrap {
    public function __construct() {
        parent::__construct('external_trashbin');
    }

    public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeFileSystemSetupEvent::class, EventListener::class);
    }

	public function boot(IBootContext $context): void {}
}
