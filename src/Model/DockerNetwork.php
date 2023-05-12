<?php

namespace App\Model;

class DockerNetwork {
    public function __construct(
        public string $networkId,
        public string $name,
        public string $driver,
        public string $scope
    ) {}
}
