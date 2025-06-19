<?php

namespace App\Model;

final class BuildInformation
{
    private string $version, $build, $type;

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getBuild(): ?string
    {
        return $this->build;
    }

    public function setBuild(string $build): void
    {
        $this->build = $build;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}