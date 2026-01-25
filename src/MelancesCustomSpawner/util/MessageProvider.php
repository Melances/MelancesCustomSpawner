<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\util;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

final class MessageProvider{

    private Config $cfg;

    private string $prefixSuccess;
    private string $prefixError;
    private string $prefixInfo;

    private string $usageLine;
    private string $exampleLine;

    public function __construct(private PluginBase $plugin){
        $path = $plugin->getDataFolder() . "messages.yml";
        $this->cfg = new Config($path, Config::YAML);

        $this->prefixSuccess = (string)$this->cfg->getNested("meta.prefix.success", "&7[&a+&7] &6MCS &7> &f");
        $this->prefixError   = (string)$this->cfg->getNested("meta.prefix.error",   "&7[&c!&7] &6MCS &7> &f");
        $this->prefixInfo    = (string)$this->cfg->getNested("meta.prefix.info",    "&7[&e i &7] &6MCS &7> &f");

        $this->usageLine   = (string)$this->cfg->getNested("meta.usageLine", "Kullanım: /spver kullanıcıadı sptürü adet");
        $this->exampleLine = (string)$this->cfg->getNested("meta.exampleLine", "Örneğin: /spver MelancesINC orumceksp 1");
    }

    
    private function pick(string $path, array $vars = []) : string{
        $raw = $this->cfg->getNested($path);

        if(is_array($raw) && count($raw) > 0){
            $pick = $raw[array_rand($raw)];
            $text = is_string($pick) ? $pick : (string)$pick;
        }elseif(is_string($raw)){
            $text = $raw;
        }else{
            $text = "Mesaj bulunamadı: " . $path;
        }

        $vars["usage"] = $this->usageLine;
        $vars["example"] = $this->exampleLine;

        foreach($vars as $k => $v){
            $text = str_replace("{" . $k . "}", (string)$v, $text);
        }
        return $text;
    }

    private function colorize(string $text) : string{
        return str_replace("&", "§", $text);
    }

    
    public function raw(string $path, array $vars = []) : string{
        return $this->colorize($this->pick($path, $vars));
    }

    
    private function format(string $type, string $path, array $vars = []) : string{
        $prefix = match($type){
            "success" => $this->prefixSuccess,
            "error" => $this->prefixError,
            default => $this->prefixInfo
        };

        $body = $this->pick($path, $vars);

        return $this->colorize($prefix) . $this->colorize($body);
    }

    
    public function success(string $path, array $vars = []) : string{
        return $this->format("success", $path, $vars);
    }

    
    public function error(string $path, array $vars = []) : string{
        return $this->format("error", $path, $vars);
    }

    
    public function info(string $path, array $vars = []) : string{
        return $this->format("info", $path, $vars);
    }

    
    public function usageErrorLines() : array{
        return [
            $this->colorize($this->prefixError) . $this->colorize($this->usageLine),
            $this->colorize($this->prefixError) . $this->colorize($this->exampleLine),
        ];
    }
}
