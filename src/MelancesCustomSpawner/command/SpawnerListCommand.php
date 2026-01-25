<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\command;

use MelancesCustomSpawner\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class SpawnerListCommand extends Command{

    public function __construct(private Main $plugin){
        parent::__construct("splistesi", "Spawner türlerini listeler.", "/splistesi");
        $this->setPermission("melancescustomspawner.splistesi");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        $msg = $this->plugin->messages();

        if($sender instanceof Player){
            if(!$sender->hasPermission("pocketmine.group.operator") || !$sender->hasPermission("melancescustomspawner.splistesi")){
                $sender->sendMessage($msg->error("no_permission.splistesi"));
                return true;
            }
        }else{
            if(!$sender->hasPermission("melancescustomspawner.splistesi")){
                $sender->sendMessage($msg->error("no_permission.splistesi"));
                return true;
            }
        }

        $types = (array)$this->plugin->getConfig()->get("types", []);
        if(count($types) === 0){
            $sender->sendMessage($msg->info("splistesi.empty"));
            return true;
        }

        $sender->sendMessage($msg->info("splistesi.header"));

        $lineFormat = $msg->raw("splistesi.line_format");

        foreach($types as $id => $row){
            $display = (string)($row["display"] ?? $id);
            $line = str_replace(["{type}", "{display}"], [(string)$id, $display], $lineFormat);
            $sender->sendMessage($msg->info("splistesi.line_wrap", ["line" => $line]));
        }

        $sender->sendMessage($msg->info("splistesi.footer"));
        return true;
    }
}
