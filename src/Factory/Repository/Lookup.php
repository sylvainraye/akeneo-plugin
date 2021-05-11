<?php declare(strict_types=1);

namespace Kiboko\Plugin\Akeneo\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Plugin\Akeneo;

final class Lookup implements Configurator\StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private Akeneo\Builder\Lookup|Akeneo\Builder\ConditionalLookup $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Akeneo\Builder\Lookup|Akeneo\Builder\ConditionalLookup
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): self
    {
        array_push($this->files, ...$friend->getFiles());
        array_push($this->packages, ...$friend->getPackages());

        return $this;
    }
}
