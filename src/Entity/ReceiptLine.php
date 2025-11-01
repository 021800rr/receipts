<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    private Receipt $receipt;
    
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    private string $quantity = '1.000';
    
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $unit = null;
    
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => 0.00], name: 'unit_price')]
    private string $unitPrice = '0.00';
    
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => 0.00], name: 'line_total')]
    private string $lineTotal = '0.00';
    
    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'now()'])]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getUnitPrice(): float
    {
        return (float)$this->unitPrice;
    }

    public function setUnitPrice($value): self
    {
        if (is_string($value)) {
            $value = str_replace([' ', "'", ','], ['', '', '.'], $value);
        }
        $v = (float)$value;
        $this->unitPrice = number_format($v, 2, '.', '');
        return $this;
    }

    public function getLineTotal(): float
    {
        return (float)$this->lineTotal;
    }

    public function setLineTotal($value): self
    {
        if (is_string($value)) {
            $value = str_replace([' ', "'", ','], ['', '', '.'], $value);
        }
        $v = (float)$value;
        $this->lineTotal = number_format($v, 2, '.', '');
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeLineTotal(): void
    {
        // compute using quantity and unitPrice; quantity stored as string decimal
        $qty = (float)str_replace(',', '.', (string)$this->quantity);
        $price = (float)$this->unitPrice;
        $total = $qty * $price;
        // store as formatted string to match Doctrine decimal mapping
        $this->lineTotal = number_format($total, 2, '.', '');

        // If linked to a receipt, update its total as well so the header is consistent
        if (isset($this->receipt) && $this->receipt !== null) {
            $this->receipt->recalc();
        }
    }
    
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
