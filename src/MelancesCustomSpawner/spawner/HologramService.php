<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\spawner;

use MelancesCustomSpawner\Main;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\World;

final class HologramService{

    
    private array $particles = [];

    private bool $enabled;
    private int $radius;
    private int $resendTicks;
    private string $titleFormat;

    private int $tickCounter = 0;

    public function __construct(private Main $plugin){
        $this->enabled = (bool)$plugin->getConfig()->getNested("hologram.enabled", true);
        $this->radius = (int)$plugin->getConfig()->getNested("hologram.radiusBlocks", 64);
        $this->resendTicks = (int)$plugin->getConfig()->getNested("hologram.resendPeriodTicks", 40);
        $this->titleFormat = (string)$plugin->getConfig()->getNested("hologram.titleFormat", "{display} | {level} adet");
        $this->resendTicks = max(10, $this->resendTicks);
    }

    public function tick() : void{
        if(!$this->enabled) return;
        $this->tickCounter++;
    }

    public function shouldResendNow() : bool{
        if(!$this->enabled) return false;
        return ($this->tickCounter % $this->resendTicks) === 0;
    }

    public function ensure(SpawnerState $state) : void{
        if(!$this->enabled) return;
        $key = $state->getKey();
        if(!isset($this->particles[$key])){
            $this->particles[$key] = new FloatingTextParticle("", "");
        }
        $this->applyText($state, $this->particles[$key]);
    }

    public function updateAndSend(SpawnerState $state, ?World $world) : void{
        if(!$this->enabled) return;
        if($world === null) return;

        $key = $state->getKey();
        $particle = $this->particles[$key] ?? new FloatingTextParticle("", "");
        $this->applyText($state, $particle);
        $this->particles[$key] = $particle;

        $this->sendToNearby($state, $world, $particle);
    }

    public function despawn(SpawnerState $state, ?World $world) : void{
        if(!$this->enabled) return;
        $key = $state->getKey();
        if(!isset($this->particles[$key])) return;

        $particle = $this->particles[$key];
        $particle->setInvisible(true);
        if($world !== null){
            $this->sendToNearby($state, $world, $particle);
        }
        unset($this->particles[$key]);
    }

    private function applyText(SpawnerState $state, FloatingTextParticle $particle) : void{
        $types = (array)$this->plugin->getConfig()->get("types", []);
        $display = (string)($types[$state->typeId]["display"] ?? $state->typeId);

        $title = str_replace(
            ["{display}", "{level}"],
            [$display, (string)$state->level],
            $this->titleFormat
        );

        $particle->setInvisible(false);
        $particle->setTitle($title);
        $particle->setText(""); // tek satir
    }

    private function sendToNearby(SpawnerState $state, World $world, FloatingTextParticle $particle) : void{
        $pos = new Vector3($state->x + 0.5, $state->y + 1.6, $state->z + 0.5);
        $r2 = $this->radius * $this->radius;

        $players = [];
        foreach($world->getPlayers() as $p){
            if(!$p instanceof Player) continue;
            if($p->getPosition()->distanceSquared($pos) <= $r2){
                $players[] = $p;
            }
        }
        if(count($players) === 0) return;

        $world->addParticle($pos, $particle, $players);
    }
}
