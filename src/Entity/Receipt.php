<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: 'receipt')]
#[ORM\Index(name: 'idx_receipt_date', columns: ['purchase_date'])]
#[ORM\Index(name: 'idx_receipt_household', columns: ['household_id'])]
#[ORM\Index(name: 'idx_receipt_store', columns: ['store_id'])]
class Receipt
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: 'uuid', unique: true)]
    private $id;
    #[ORM\ManyToOne(targetEntity: Household::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Household $household;
    #[ORM\ManyToOne(targetEntity: Store::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Store $store;
    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $purchase_date;
    #[ORM\Column(type: 'bigint')]
    private int $total_amount_grosze = 0;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;
    #[ORM\OneToMany(mappedBy: 'receipt', targetEntity: ReceiptLine::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getHousehold(): Household
    {
        return $this->household;
    }

    public function setHousehold(Household $h): self
    {
        $this->household = $h;
        return $this;
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function setStore(Store $s): self
    {
        $this->store = $s;
        return $this;
    }

    public function getPurchaseDate(): \DateTimeInterface
    {
        return $this->purchase_date;
    }

    public function setPurchaseDate(\DateTimeInterface $d): self
    {
        $this->purchase_date = $d;
        return $this;
    }

    public function getTotalAmountGrosze(): int
    {
        return $this->total_amount_grosze;
    }

    public function setTotalAmountGrosze(int $v): self
    {
        $this->total_amount_grosze = $v;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $n): self
    {
        $this->notes = $n;
        return $this;
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function addLine(ReceiptLine $l): self
    {
        if (!$this->lines->contains($l)) {
            $this->lines->add($l);
            $l->setReceipt($this);
            $this->recalc();
        }
        return $this;
    }

    public function removeLine(ReceiptLine $l): self
    {
        if ($this->lines->removeElement($l)) {
            $this->recalc();
        }
        return $this;
    }

    public function recalc(): void
    {
        $s = 0;
        foreach ($this->lines as $l) {
            $s += $l->getLineTotalGrosze();
        }
        $this->total_amount_grosze = $s;
    }

    public function __toString(): string
    {
        return ($this->purchase_date?->format('Y-m-d') ?? '') . ' - ' . (string)($this->store ?? '');
    }
}
