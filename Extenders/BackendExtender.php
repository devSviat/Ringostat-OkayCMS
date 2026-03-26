<?php

namespace Okay\Modules\Sviat\Ringostat\Extenders;

use Okay\Core\Config;
use Okay\Core\EntityFactory;
use Okay\Core\ManagerMenu;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\Sviat\Ringostat\Entities\RingostatCallbackQueueEntity;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatHelper;

class BackendExtender implements ExtensionInterface
{
    /** @var RingostatHelper */
    private $ringostatHelper;

    /** @var Config */
    private $config;

    /** @var ManagerMenu */
    private $managerMenu;

    /** @var EntityFactory */
    private $entityFactory;

    public function __construct(
        RingostatHelper $ringostatHelper,
        Config $config,
        ManagerMenu $managerMenu,
        EntityFactory $entityFactory
    ) {
        $this->ringostatHelper = $ringostatHelper;
        $this->config = $config;
        $this->managerMenu = $managerMenu;
        $this->entityFactory = $entityFactory;
    }

    /** Бадж з кількістю необроблених у черзі передзвону. */
    public function setCallbackQueueCounter(): void
    {
        /** @var RingostatCallbackQueueEntity $queueEntity */
        $queueEntity = $this->entityFactory->get(RingostatCallbackQueueEntity::class);
        $this->managerMenu->addCounter('sviat__left_ringostat_callback_queue', $queueEntity->count(['processed' => 0]));
    }
}
