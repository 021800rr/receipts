<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product')]
#[ORM\UniqueConstraint(name: 'uniq_product_name', columns: ['name'])]
#[ORM\Index(name: 'idx_product_category', columns: ['category_id'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private $id;
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $c): self
    {
        $this->category = $c;
        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
