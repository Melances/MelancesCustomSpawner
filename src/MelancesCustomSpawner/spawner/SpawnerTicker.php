<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\spawner;

use pocketmine\scheduler\Task;

final class SpawnerTicker extends Task{
    public function __construct(private SpawnerManager $manager){}
    public function onRun() : void{
        $this->manager->tick();
    }
}
