<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\persistence;

use MelancesCustomSpawner\spawner\SpawnerState;
use pocketmine\utils\Config;

final class SpawnerRepository{

    public function __construct(private string $dataFolder){
        @mkdir($this->dataFolder . "worlds/", 0777, true);
    }

    private function getWorldFile(string $world) : string{
        return $this->dataFolder . "worlds/" . strtolower($world) . ".json";
    }

    
    public function loadWorld(string $world) : array{
        $cfg = new Config($this->getWorldFile($world), Config::JSON, ["spawners" => []]);
        $out = [];
        foreach($cfg->get("spawners", []) as $row){
            $state = SpawnerState::fromArray((array)$row);
            $out[$state->getKey()] = $state;
        }
        return $out;
    }

    
    public function saveWorld(string $world, array $spawners) : void{
        $cfg = new Config($this->getWorldFile($world), Config::JSON, ["spawners" => []]);
        $rows = [];
        foreach($spawners as $state){
            $rows[] = $state->toArray();
        }
        $cfg->set("spawners", $rows);
        $cfg->save();
    }
}
