<?php

namespace App\Model;

class DockerData extends Recipe {
    public function __construct(
        public array $volumes,
        public ?string $dockerFile = null,

        ...$args,
    ) {
        parent::__construct(...self::cleanConstructArgs($args));
    }

    /**
     * @var int|null - proxy mode port for this instance
     */
    public ?int $proxyModePort = null;
}
