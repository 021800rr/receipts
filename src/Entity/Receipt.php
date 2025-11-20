<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'receipt')]
#[ORM\Index(name: 'idx_receipt_date', columns: ['purchase_date'])]
#[ORM\Index(name: 'idx_receipt_household', columns: ['household_id'])]
#[ORM\Index(name: 'idx_receipt_store', columns: ['store_id'])]
#[ORM\HasLifecycleCallbacks]
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
    #[Assert\NotNull(message: 'Data zakupu jest wymagana')]
    private \DateTimeInterface $purchase_date;

    #[ORM\Column(name: 'total_amount', type: 'decimal', precision: 14, scale: 2, options: ['default' => 0.00])]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(targetEntity: ReceiptLine::class, mappedBy: 'receipt', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid]
    private Collection $lines;

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

    public function setHousehold(Household $household): self
    {
        $this->household = $household;

        return $this;
    }

    public function getStore(): Store
    {
        return $this->store;
    }

    public function setStore(Store $store): self
    {
        $this->store = $store;

        return $this;
    }

    public function getPurchaseDate(): \DateTimeInterface
    {
        return $this->purchase_date;
    }

    public function setPurchaseDate(\DateTimeInterface $purchaseDate): self
    {
        $this->purchase_date = $purchaseDate;

        return $this;
    }

    public function getTotalAmount(): float
    {
        return (float) $this->totalAmount;
    }

    public function setTotalAmount(float|string $value): self
    {
        if (\is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        $v = (float) $value;
        $this->totalAmount = number_format($v, 2, '.', '');

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return Collection<int, ReceiptLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(ReceiptLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $line->setReceipt($this);
            // kolejna pozycja na końcu
            $line->setPosition($this->lines->count());
            $this->lines->add($line);
            $this->recalc();
        }

        return $this;
    }

    public function removeLine(ReceiptLine $line): self
    {
        if ($this->lines->removeElement($line)) {
            $this->recalc();
        }

        return $this;
    }

    public function recalc(): void
    {
        $sum = 0.0;

        foreach ($this->lines as $line) {
            $sum += $line->getLineTotal();
        }

        $this->totalAmount = number_format($sum, 2, '.', '');
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeTotalAmount(): void
    {
        $this->recalc();
    }

    public function __toString(): string
    {
        return ($this->purchase_date?->format('Y-m-d') ?? '') . ' - ' . (string) ($this->store ?? '');
    }
}
