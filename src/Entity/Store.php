<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'store')]
#[ORM\UniqueConstraint(name: 'uniq_store_name', columns: ['name'])]
class Store
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private $id;
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $n): self
    {
        $this->name = $n;
        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
