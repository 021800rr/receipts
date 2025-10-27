<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'receipt_line')]
#[ORM\Index(name: 'idx_line_product', columns: ['product_id'])]
#[ORM\Index(name: 'idx_line_receipt', columns: ['receipt_id'])]
class ReceiptLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private $id;
    #[ORM\ManyToOne(targetEntity: Receipt::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private Receipt $receipt;
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    private string $quantity = '1.000';
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $unit = null;
    #[ORM\Column(type: 'bigint')]
    private int $unit_price_grosze;
    #[ORM\Column(type: 'bigint')]
    private int $line_total_grosze;

    public function getId()
    {
        return $this->id;
    }

    public function getReceipt(): Receipt
    {
        return $this->receipt;
    }

    public function setReceipt(Receipt $r): self
    {
        $this->receipt = $r;
        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $p): self
    {
        $this->product = $p;
        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $q): self
    {
        $this->quantity = $q;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $u): self
    {
        $this->unit = $u;
        return $this;
    }

    public function getUnitPriceGrosze(): int
    {
        return $this->unit_price_grosze;
    }

    public function setUnitPriceGrosze(int $v): self
    {
        $this->unit_price_grosze = $v;
        return $this;
    }

    public function getLineTotalGrosze(): int
    {
        return $this->line_total_grosze;
    }

    public function setLineTotalGrosze(int $v): self
    {
        $this->line_total_grosze = $v;
        return $this;
    }
}
