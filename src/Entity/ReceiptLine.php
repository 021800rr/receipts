<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'receipt_line')]
#[ORM\Index(name: 'idx_line_product', columns: ['product_id'])]
#[ORM\Index(name: 'idx_line_receipt', columns: ['receipt_id'])]
#[ORM\HasLifecycleCallbacks]
class ReceiptLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Receipt::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Receipt $receipt = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Produkt jest wymagany')]
    private ?Product $product = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3, options: ['default' => 1])]
    #[Assert\NotBlank(message: 'Ilość jest wymagana')]
    #[Assert\GreaterThan(value: 0, message: 'Ilość musi być większa od zera')]
    private string $quantity = '1.000';

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(name: 'unit_price', type: 'decimal', precision: 12, scale: 2)]
    #[Assert\NotBlank(message: 'Cena jednostkowa jest wymagana')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Cena jednostkowa nie może być ujemna')]
    private string $unitPrice = '0.00';

    #[ORM\Column(name: 'line_total', type: 'decimal', precision: 12, scale: 2, options: ['default' => 0.00])]
    private string $lineTotal = '0.00';

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getReceipt(): ?Receipt
    {
        return $this->receipt;
    }

    public function setReceipt(?Receipt $receipt): self
    {
        $this->receipt = $receipt;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getUnitPrice(): float
    {
        return (float) $this->unitPrice;
    }

    public function setUnitPrice(float|string $value): self
    {
        if (\is_string($value)) {
            $value = str_replace([' ', "'", ','], ['', '', '.'], $value);
        }

        $v = (float) $value;
        $this->unitPrice = number_format($v, 2, '.', '');

        return $this;
    }

    public function getLineTotal(): float
    {
        return (float) $this->lineTotal;
    }

    public function setLineTotal(float|string $value): self
    {
        if (\is_string($value)) {
            $value = str_replace([' ', "'", ','], ['', '', '.'], $value);
        }

        $v = (float) $value;
        $this->lineTotal = number_format($v, 2, '.', '');

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeLineTotal(): void
    {
        // liczymy z quantity + unitPrice
        $qty = (float) str_replace(',', '.', (string) $this->quantity);
        $price = (float) $this->unitPrice;
        $total = $qty * $price;

        // zapis jako string pod decimal
        $this->lineTotal = number_format($total, 2, '.', '');
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
