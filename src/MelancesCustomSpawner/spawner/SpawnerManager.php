<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\spawner;

use MelancesCustomSpawner\Main;
use MelancesCustomSpawner\persistence\SpawnerRepository;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

final class SpawnerManager implements Listener{

    private SpawnerRepository $repo;
    private ProductionEngine $engine;
    private HologramService $holograms;

    
    private array $spawners = [];

    
    private array $dirtyWorlds = [];

    private int $spawnerTypeId;
    private int $activityRadius;

    public function __construct(private Main $plugin){
        $this->repo = new SpawnerRepository($plugin->getDataFolder());
        $this->engine = new ProductionEngine($plugin->getConfig());
        $this->holograms = new HologramService($plugin);

        $this->spawnerTypeId = $this->resolveSpawnerTypeId();
        $this->activityRadius = (int)$plugin->getConfig()->getNested("activity.radiusBlocks", 48);

        foreach($plugin->getServer()->getWorldManager()->getWorlds() as $world){
            $this->loadWorld($world);
        }
    }

    private function resolveSpawnerTypeId() : int{
        try{
            return VanillaBlocks::MONSTER_SPAWNER()->getTypeId();
        }catch(\Throwable $e){
            $this->plugin->getLogger()->error("Spawner block registry bulunamadi (MONSTER_SPAWNER). Hata: " . $e->getMessage());
            return -1;
        }
    }

    public function onWorldLoad(WorldLoadEvent $e) : void{
        $this->loadWorld($e->getWorld());
    }

    public function onWorldUnload(WorldUnloadEvent $e) : void{
        $this->flushWorld($e->getWorld()->getFolderName());
    }

    private function loadWorld(World $world) : void{
        $name = $world->getFolderName();
        foreach($this->repo->loadWorld($name) as $k => $state){
            $this->spawners[$k] = $state;
            $this->holograms->ensure($state);
        }
    }

    public function flushAll() : void{
        foreach(array_keys($this->dirtyWorlds) as $world){
            $this->flushWorld($world);
        }
    }

    private function flushWorld(string $world) : void{
        if(!isset($this->dirtyWorlds[$world])) return;

        $subset = [];
        foreach($this->spawners as $state){
            if($state->world === $world){
                $subset[$state->getKey()] = $state;
            }
        }

        $this->repo->saveWorld($world, $subset);
        unset($this->dirtyWorlds[$world]);
    }

    private function markDirty(string $world) : void{
        $this->dirtyWorlds[$world] = true;
    }

    private function isSpawnerBlock(Block $block) : bool{
        return $this->spawnerTypeId !== -1 && $block->getTypeId() === $this->spawnerTypeId;
    }

    private function keyFromWorldPos(World $world, int $x, int $y, int $z) : string{
        return $world->getFolderName() . ":" . $x . ":" . $y . ":" . $z;
    }

    private function hasNearbyPlayer(World $world, Vector3 $pos) : bool{
        $r2 = $this->activityRadius * $this->activityRadius;
        foreach($world->getPlayers() as $p){
            if($p->getPosition()->distanceSquared($pos) <= $r2){
                return true;
            }
        }
        return false;
    }

    
    private function validateOrCleanup(SpawnerState $state, World $world) : bool{
        $block = $world->getBlockAt($state->x, $state->y, $state->z);
        if($this->isSpawnerBlock($block)){
            return true;
        }

        unset($this->spawners[$state->getKey()]);
        $this->markDirty($state->world);
        $this->holograms->despawn($state, $world);
        return false;
    }

    public function tick() : void{
        $periodTicks = (int)$this->plugin->getConfig()->getNested("tick.periodTicks", 10);
        $deltaSeconds = $periodTicks / 20.0;

        $this->holograms->tick();
        $shouldResendHolo = $this->holograms->shouldResendNow();

        $keys = array_keys($this->spawners);

        foreach($keys as $k){
            $state = $this->spawners[$k] ?? null;
            if($state === null) continue;

            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($state->world);
            if($world === null) continue;

            $chunkX = $state->x >> 4;
            $chunkZ = $state->z >> 4;
            if(!$world->isChunkLoaded($chunkX, $chunkZ)) continue;

            if(!$this->validateOrCleanup($state, $world)){
                continue;
            }

            if($shouldResendHolo){
                $this->holograms->updateAndSend($state, $world);
            }

            $center = new Vector3($state->x + 0.5, $state->y + 0.5, $state->z + 0.5);
            if(!$this->hasNearbyPlayer($world, $center)){
                continue;
            }

            $state->accumulatedSeconds += $deltaSeconds;

            $interval = $this->engine->getIntervalSeconds($state);
            $produced = false;

            while($state->accumulatedSeconds >= $interval){
                $state->accumulatedSeconds -= $interval;

                foreach($this->engine->createDrops($state) as $item){
                    $world->dropItem($this->computeDropPosition($world, $state), $item);
                }
                $produced = true;
            }

            if($produced){
                $this->markDirty($state->world);
            }
        }
    }

    public function onPlace(BlockPlaceEvent $e) : void{
        $item = $e->getItem();
        $tag = $item->getNamedTag();

        $typeId = $tag->getString("mcs_type", "");
        $level = max(1, $tag->getInt("mcs_level", 1));

        if($typeId === ""){
            return;
        }

        $types = (array)$this->plugin->getConfig()->get("types", []);
        if(!isset($types[$typeId])){
            return;
        }

        $tx = $e->getTransaction();
        foreach($tx->getBlocks() as [$x, $y, $z, $block]){
            if(!$block instanceof Block) continue;
            if(!$this->isSpawnerBlock($block)) continue;

            $world = $block->getPosition()->getWorld();
            $key = $this->keyFromWorldPos($world, (int)$x, (int)$y, (int)$z);

            if(isset($this->spawners[$key])){
                $e->cancel();
                $e->getPlayer()->sendMessage($this->plugin->messages()->info("runtime.place_on_existing"));
                return;
            }

            $state = new SpawnerState(
                $world->getFolderName(),
                (int)$x,
                (int)$y,
                (int)$z,
                $typeId,
                $level,
                $e->getPlayer()->getXuid()
            );

            $this->spawners[$key] = $state;
            $this->holograms->ensure($state);
            $this->markDirty($world->getFolderName());

            $this->holograms->updateAndSend($state, $world);
        }
    }

    public function onBreak(BlockBreakEvent $e) : void{
        $block = $e->getBlock();
        if(!$this->isSpawnerBlock($block)) return;

        $player = $e->getPlayer();
        $pos = $block->getPosition();
        $world = $pos->getWorld();
        $key = $this->keyFromWorldPos($world, $pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());

        $state = $this->spawners[$key] ?? null;
        if($state === null){
            return;
        }

        $isOwner = $state->ownerXuid !== "" && $player->getXuid() === $state->ownerXuid;

        $isOpLike = $player->hasPermission("pocketmine.group.operator") || $player->hasPermission("melancescustomspawner.breakany");

        if(!$isOwner && !$isOpLike){
            $player->sendMessage($this->plugin->messages()->error("runtime.break_not_owner"));
            $e->cancel();
            return;
        }

        if($player->getInventory()->firstEmpty() === -1){
            $player->sendMessage($this->plugin->messages()->error("runtime.break_inventory_full"));
            $e->cancel();
            return;
        }

        $e->setDrops([]);
        $e->setXpDropAmount(0);

        $types = (array)$this->plugin->getConfig()->get("types", []);
        $display = (string)($types[$state->typeId]["display"] ?? $state->typeId);

        $spawnerItem = VanillaBlocks::MONSTER_SPAWNER()->asItem();
        $spawnerItem->setCount(1);
        $tag = $spawnerItem->getNamedTag();
        $tag->setString("mcs_type", $state->typeId);
        $tag->setInt("mcs_level", 1);
        $spawnerItem->setNamedTag($tag);
        $spawnerItem->setCustomName($display . " | 1 adet");

        $player->getInventory()->addItem($spawnerItem);

        if($state->level > 1){
            $state->level -= 1;
            $this->markDirty($state->world);

            $e->cancel();

            $this->holograms->updateAndSend($state, $world);

            $player->sendMessage($this->plugin->messages()->success("runtime.break_given", ["level" => $state->level]));
            return;
        }

        unset($this->spawners[$key]);
        $this->markDirty($state->world);

        $this->holograms->despawn($state, $world);

        $player->sendMessage($this->plugin->messages()->success("runtime.break_given", ["level" => 0]));
    }

    public function onInteract(PlayerInteractEvent $e) : void{
        if($e->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;

        $block = $e->getBlock();
        if(!$this->isSpawnerBlock($block)) return;

        $player = $e->getPlayer();
        $pos = $block->getPosition();
        $world = $pos->getWorld();
        $key = $this->keyFromWorldPos($world, $pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ());

        $state = $this->spawners[$key] ?? null;
        if($state === null){
            $player->sendMessage($this->plugin->messages()->error("runtime.spawner_unknown"));
            $e->cancel();
            return;
        }

        $hand = $player->getInventory()->getItemInHand();
        $tag = $hand->getNamedTag();
        $handType = $tag->getString("mcs_type", "");
        $handLevel = max(1, $tag->getInt("mcs_level", 1));

        if($handType !== "" && $handType === $state->typeId && $hand->getCount() > 0){
            $state->level += $handLevel;
            $hand->setCount($hand->getCount() - 1);
            $player->getInventory()->setItemInHand($hand);

            $this->markDirty($state->world);

            $this->holograms->updateAndSend($state, $world);

            $player->sendMessage($this->plugin->messages()->success("runtime.spawner_stacked", ["level" => $state->level]));
            $e->cancel();
            return;
        }

        $e->cancel();
    }

    private function computeDropPosition(World $world, SpawnerState $state) : Vector3{
        $x = $state->x;
        $y = $state->y;
        $z = $state->z;

        $below = $world->getBlockAt($x, $y - 1, $z);
        if($below->isSolid()){
            $dirs = [
                [ 1,  0],
                [-1,  0],
                [ 0,  1],
                [ 0, -1],
                [ 1,  1],
                [ 1, -1],
                [-1,  1],
                [-1, -1],
            ];
            shuffle($dirs);

            foreach($dirs as [$dx, $dz]){
                $px = $x + 0.5 + ($dx * 0.75);
                $py = $y + 0.10; // zeminin üstündeki hava bloğu
                $pz = $z + 0.5 + ($dz * 0.75);

                $bx = (int)floor($px);
                $by = (int)floor($py);
                $bz = (int)floor($pz);

                if($world->getBlockAt($bx, $by, $bz)->isSolid()){
                    continue;
                }
                return new Vector3($px, $py, $pz);
            }

            return new Vector3($x + 0.5, $y + 0.10, $z + 0.5);
        }

        return new Vector3($x + 0.5, $y - 0.20, $z + 0.5);
    }

}
