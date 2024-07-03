<?php

namespace App\Entity;

use App\Repository\LotteryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LotteryRepository::class)]
class Lottery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $token;

    #[ORM\OneToMany(targetEntity: Gift::class, mappedBy: 'lottery')]
    private $gifts;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $Icon;

    #[ORM\Column(type: 'datetime')]
    private $start_at;

    #[ORM\Column(type: 'datetime')]
    private $end_at;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'lottery')]
    private $products;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $theme;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $meta_data = null;

    #[ORM\OneToMany(mappedBy: 'lottery', targetEntity: User::class)]
    private Collection $users;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rules = null;

    #[ORM\OneToMany(mappedBy: 'lottery', targetEntity: UserPlay::class)]
    private Collection $userPlays;

    public function __construct()
    {
        $this->gifts = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->userPlays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Collection<int, Gift>
     */
    public function getGifts(): Collection
    {
        return $this->gifts;
    }

    public function addGift(Gift $gift): static
    {
        if (!$this->gifts->contains($gift)) {
            $this->gifts->add($gift);
            $gift->setLottery($this);
        }

        return $this;
    }

    public function removeGift(Gift $gift): static
    {
        if ($this->gifts->removeElement($gift)) {
            // set the owning side to null (unless already changed)
            if ($gift->getLottery() === $this) {
                $gift->setLottery(null);
            }
        }

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->Icon;
    }

    public function setIcon(?string $Icon): static
    {
        $this->Icon = $Icon;

        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->start_at;
    }

    public function setStartAt(\DateTimeInterface $start_at): static
    {
        $this->start_at = $start_at;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->end_at;
    }

    public function setEndAt(\DateTimeInterface $end_at): static
    {
        $this->end_at = $end_at;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setLottery($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getLottery() === $this) {
                $product->setLottery(null);
            }
        }

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getMetaData(): ?string
    {
        return $this->meta_data;
    }

    public function setMetaData(?string $meta_data): static
    {
        $this->meta_data = $meta_data;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setLottery($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getLottery() === $this) {
                $user->setLottery(null);
            }
        }

        return $this;
    }

    public function getRules(): ?string
    {
        return $this->rules;
    }

    public function setRules(?string $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return Collection<int, UserPlay>
     */
    public function getUserPlays(): Collection
    {
        return $this->userPlays;
    }

    public function addUserPlay(UserPlay $userPlay): static
    {
        if (!$this->userPlays->contains($userPlay)) {
            $this->userPlays->add($userPlay);
            $userPlay->setLottery($this);
        }

        return $this;
    }

    public function removeUserPlay(UserPlay $userPlay): static
    {
        if ($this->userPlays->removeElement($userPlay)) {
            // set the owning side to null (unless already changed)
            if ($userPlay->getLottery() === $this) {
                $userPlay->setLottery(null);
            }
        }

        return $this;
    }
}
