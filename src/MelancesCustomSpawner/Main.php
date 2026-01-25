<?php

declare(strict_types=1);

namespace MelancesCustomSpawner;

use MelancesCustomSpawner\command\GiveSpawnerCommand;
use MelancesCustomSpawner\command\SpawnerListCommand;
use MelancesCustomSpawner\spawner\SpawnerManager;
use MelancesCustomSpawner\spawner\SpawnerTicker;
use MelancesCustomSpawner\util\MessageProvider;
use pocketmine\plugin\PluginBase;

final class Main extends PluginBase{

    private SpawnerManager $spawnerManager;
    private MessageProvider $messages;

    public function messages() : MessageProvider{
        return $this->messages;
    }

    protected function onEnable() : void{
        $this->saveDefaultConfig();
        $this->saveResource("messages.yml", false);

        $this->messages = new MessageProvider($this);

        $this->spawnerManager = new SpawnerManager($this);
        $this->getServer()->getPluginManager()->registerEvents($this->spawnerManager, $this);

        $this->getServer()->getCommandMap()->register("spver", new GiveSpawnerCommand($this));
        $this->getServer()->getCommandMap()->register("splistesi", new SpawnerListCommand($this));

        $period = (int)$this->getConfig()->getNested("tick.periodTicks", 10);
        $this->getScheduler()->scheduleRepeatingTask(new SpawnerTicker($this->spawnerManager), $period);
    }

    protected function onDisable() : void{
        $this->spawnerManager->flushAll();
    }
}
