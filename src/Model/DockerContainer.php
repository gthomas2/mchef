<?php

namespace App\Model;

class DockerContainer {
    public function __construct(
        public string $containerId,
        public string $image,
        public string $command,
        public string $created,
        public string $status,
        public string $ports,
        public string $names
    ) {}
}
