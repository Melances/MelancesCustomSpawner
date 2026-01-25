<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\spawner;

use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;

final class ProductionEngine{

    public function __construct(private Config $config){}

    public function getIntervalSeconds(SpawnerState $state) : float{
        $base = (float)$this->config->getNested("types.{$state->typeId}.baseIntervalSeconds", 5.0);
        $mult = (float)$this->config->getNested("types.{$state->typeId}.intervalMultiplierPerLevel", 1.0);
        return max(0.1, $base * pow($mult, max(0, $state->level - 1)));
    }

    public function getAmountPerCycle(SpawnerState $state) : int{
        $base = (int)$this->config->getNested("types.{$state->typeId}.baseAmount", 1);
        $perLevel = (int)$this->config->getNested("types.{$state->typeId}.amountPerLevel", 0);
        return max(1, $base + ($perLevel * max(0, $state->level - 1)));
    }

    
    public function createDrops(SpawnerState $state) : array{
        $drops = $this->config->getNested("types.{$state->typeId}.drops");
        if(!is_array($drops) || count($drops) === 0){
            $itemId = (string)$this->config->getNested("types.{$state->typeId}.drop.id", "minecraft:iron_ingot");
            $countEach = (int)$this->config->getNested("types.{$state->typeId}.drop.count", 1);
            return $this->makeSingle($itemId, $countEach * $this->getAmountPerCycle($state));
        }

        $chosen = $this->pickWeighted($drops);
        if($chosen === null){
            return [];
        }

        $id = (string)($chosen["id"] ?? "");
        $count = (int)($chosen["count"] ?? 1);
        return $this->makeSingle($id, $count * $this->getAmountPerCycle($state));
    }

    
    private function makeSingle(string $itemId, int $count) : array{
        $parser = StringToItemParser::getInstance();
        $item = $parser->parse($itemId);
        if($item === null){
            return [];
        }
        $item->setCount(max(1, $count));
        return [$item];
    }

    
    private function pickWeighted(array $rows) : ?array{
        $total = 0;
        $clean = [];
        foreach($rows as $r){
            if(!is_array($r)) continue;
            $w = (int)($r["weight"] ?? 0);
            if($w <= 0) continue;
            $total += $w;
            $clean[] = $r;
        }
        if($total <= 0 || count($clean) === 0) return null;

        $roll = random_int(1, $total);
        $acc = 0;
        foreach($clean as $r){
            $acc += (int)$r["weight"];
            if($roll <= $acc){
                return $r;
            }
        }
        return $clean[array_key_last($clean)];
    }
}
