<?php

declare(strict_types=1);

namespace MelancesCustomSpawner\spawner;

final class SpawnerState{

    public function __construct(
        public string $world,
        public int $x,
        public int $y,
        public int $z,
        public string $typeId,
        public int $level = 1,
        public string $ownerXuid = "",
        public float $accumulatedSeconds = 0.0
    ){}

    public function getKey() : string{
        return $this->world . ":" . $this->x . ":" . $this->y . ":" . $this->z;
    }

    public function toArray() : array{
        return [
            "world" => $this->world,
            "x" => $this->x,
            "y" => $this->y,
            "z" => $this->z,
            "typeId" => $this->typeId,
            "level" => $this->level,
            "ownerXuid" => $this->ownerXuid,
            "accumulatedSeconds" => $this->accumulatedSeconds
        ];
    }

    public static function fromArray(array $data) : self{
        return new self(
            (string)$data["world"],
            (int)$data["x"],
            (int)$data["y"],
            (int)$data["z"],
            (string)$data["typeId"],
            (int)($data["level"] ?? 1),
            (string)($data["ownerXuid"] ?? ""),
            (float)($data["accumulatedSeconds"] ?? 0.0)
        );
    }
}
