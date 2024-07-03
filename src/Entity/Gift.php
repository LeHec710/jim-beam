<?php

namespace App\Entity;

use App\Repository\GiftRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GiftRepository::class)]
class Gift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Lottery::class, inversedBy: 'gifts')]
    private $lottery;

    #[ORM\Column(type: 'datetime')]
    private $instant_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $win_at;

    #[ORM\Column(type: 'boolean')]
    private $is_delivered;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $delivered_at;

    #[ORM\OneToOne(targetEntity: UserPlay::class, inversedBy: 'gift', cascade: ['persist', 'remove'])]
    private $user;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'gifts')]
    private $Product;

    #[ORM\Column(type: 'string', length: 255)]
    private $code;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLottery(): ?Lottery
    {
        return $this->lottery;
    }

    public function setLottery(?Lottery $lottery): static
    {
        $this->lottery = $lottery;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getInstantAt(): ?\DateTimeInterface
    {
        return $this->instant_at;
    }

    public function setInstantAt(\DateTimeInterface $instant_at): static
    {
        $this->instant_at = $instant_at;

        return $this;
    }

    public function getUser(): ?UserPlay
    {
        return $this->user;
    }

    public function setUser(?UserPlay $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getWinAt(): ?\DateTimeInterface
    {
        return $this->win_at;
    }

    public function setWinAt(?\DateTimeInterface $win_at): static
    {
        $this->win_at = $win_at;

        return $this;
    }

    public function getIsDelivered(): ?bool
    {
        return $this->is_delivered;
    }

    public function setIsDelivered(bool $is_delivered): static
    {
        $this->is_delivered = $is_delivered;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->delivered_at;
    }

    public function setDeliveredAt(?\DateTimeInterface $delivered_at): static
    {
        $this->delivered_at = $delivered_at;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->Product;
    }

    public function setProduct(?Product $Product): static
    {
        $this->Product = $Product;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function isIsDelivered(): ?bool
    {
        return $this->is_delivered;
    }
}
