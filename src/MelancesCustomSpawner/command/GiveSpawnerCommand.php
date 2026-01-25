<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\command;

use MelancesCustomSpawner\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class GiveSpawnerCommand extends Command{

    public function __construct(private Main $plugin){
        parent::__construct("spver", "Spawner verir.", "/spver kullanıcıadı sptürü adet");
        $this->setPermission("melancescustomspawner.spver");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        $msg = $this->plugin->messages();

        if($sender instanceof Player){
            if(!$sender->hasPermission("pocketmine.group.operator") || !$sender->hasPermission("melancescustomspawner.spver")){
                $sender->sendMessage($msg->error("no_permission.spver"));
                return true;
            }
        }else{
            if(!$sender->hasPermission("melancescustomspawner.spver")){
                $sender->sendMessage($msg->error("no_permission.spver"));
                return true;
            }
        }

        $argc = count($args);
        if($argc < 1){
            $sender->sendMessage($msg->error("spver.missing_nick"));
            foreach($msg->usageErrorLines() as $ln) $sender->sendMessage($ln);
            return true;
        }
        if($argc === 1){
            $sender->sendMessage($msg->error("spver.missing_type_and_amount"));
            foreach($msg->usageErrorLines() as $ln) $sender->sendMessage($ln);
            return true;
        }
        if($argc === 2){
            $sender->sendMessage($msg->error("spver.missing_amount"));
            foreach($msg->usageErrorLines() as $ln) $sender->sendMessage($ln);
            return true;
        }
        if($argc > 3){
            $sender->sendMessage($msg->error("spver.too_many_args"));
            foreach($msg->usageErrorLines() as $ln) $sender->sendMessage($ln);
            return true;
        }

        $playerName = (string)$args[0];
        $typeId = (string)$args[1];

        if(!preg_match('/^\d+$/', (string)$args[2])){
            $sender->sendMessage($msg->error("spver.amount_not_number", ["amount" => (string)$args[2]]));
            foreach($msg->usageErrorLines() as $ln) $sender->sendMessage($ln);
            return true;
        }

        $amount = (int)$args[2];
        if($amount < 1){
            $sender->sendMessage($msg->error("spver.amount_too_small"));
            foreach($msg->usageErrorLines() as $ln) $sender->sendMessage($ln);
            return true;
        }

        $types = (array)$this->plugin->getConfig()->get("types", []);
        if(!isset($types[$typeId])){
            $sender->sendMessage($msg->error("spver.unknown_type", ["types" => implode(", ", array_keys($types))]));
            return true;
        }

        $target = $this->plugin->getServer()->getPlayerExact($playerName);
        if(!$target instanceof Player){
            $sender->sendMessage($msg->error("spver.target_not_found", ["player" => $playerName]));
            return true;
        }

        try{
            $block = VanillaBlocks::MONSTER_SPAWNER();
        }catch(\Throwable $e){
            $sender->sendMessage($msg->error("spver.registry_missing"));
            return true;
        }

        $item = $block->asItem();
        $item->setCount($amount);

        $tag = $item->getNamedTag();
        $tag->setString("mcs_type", $typeId);
        $tag->setInt("mcs_level", 1);
        $item->setNamedTag($tag);

        $display = (string)($types[$typeId]["display"] ?? $typeId);
        $item->setCustomName($display . " | 1 adet");

        $target->getInventory()->addItem($item);

        $sender->sendMessage($msg->success("spver.success_sender", ["player" => $playerName, "amount" => $amount, "type" => $typeId]));
        $target->sendMessage($msg->success("spver.success_target", ["display" => $display, "amount" => $amount]));
        return true;
    }
}
