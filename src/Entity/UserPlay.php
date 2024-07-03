<?php

namespace App\Entity;

use App\Repository\UserPlayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPlayRepository::class)]
class UserPlay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $firstname;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $lastname;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $zip;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $city;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $address;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $addressComplement;

    #[ORM\OneToOne(targetEntity: Gift::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $gift;

    #[ORM\Column(type: 'text', nullable: true)]
    private $metaData;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $phone;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $optin1;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $optin2;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $entity_id = null;

    #[ORM\ManyToOne(inversedBy: 'userPlays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lottery $lottery = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getGift(): ?Gift
    {
        return $this->gift;
    }

    public function setGift(?Gift $gift): static
    {
        // unset the owning side of the relation if necessary
        if ($gift === null && $this->gift !== null) {
            $this->gift->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($gift !== null && $gift->getUser() !== $this) {
            $gift->setUser($this);
        }

        $this->gift = $gift;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getAddressComplement(): ?string
    {
        return $this->addressComplement;
    }

    public function setAddressComplement(?string $addressComplement): static
    {
        $this->addressComplement = $addressComplement;

        return $this;
    }

    public function getMetaMdata(): ?string
    {
        return $this->metaData;
    }

    public function setMetaData(?string $metaData): static
    {
        $this->metaData = $metaData;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getOptin1(): ?int
    {
        return $this->optin1;
    }

    public function setOptin1(?int $optin1): static
    {
        $this->optin1 = $optin1;

        return $this;
    }

    public function getOptin2(): ?int
    {
        return $this->optin2;
    }

    public function setOptin2(?int $optin2): static
    {
        $this->optin2 = $optin2;

        return $this;
    }

    public function getMetaData(): ?string
    {
        return $this->metaData;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entity_id;
    }

    public function setEntityId(?int $entity_id): static
    {
        $this->entity_id = $entity_id;

        return $this;
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
}
