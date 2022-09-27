<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userId = null;

    #[ORM\OneToMany(mappedBy: 'orderId', targetEntity: ProductsOrder::class, orphanRemoval: true)]
    private Collection $productsOrders;

    public function __construct()
    {
        $this->productsOrders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getUserId(): ?User
    {
        return $this->userId;
    }

    public function setUserId(?User $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return Collection<int, ProductsOrder>
     */
    public function getProductsOrders(): Collection
    {
        return $this->productsOrders;
    }

    public function addProductsOrder(ProductsOrder $productsOrder): self
    {
        if (!$this->productsOrders->contains($productsOrder)) {
            $this->productsOrders->add($productsOrder);
            $productsOrder->setOrderId($this);
        }

        return $this;
    }

    public function removeProductsOrder(ProductsOrder $productsOrder): self
    {
        if ($this->productsOrders->removeElement($productsOrder)) {
            // set the owning side to null (unless already changed)
            if ($productsOrder->getOrderId() === $this) {
                $productsOrder->setOrderId(null);
            }
        }

        return $this;
    }
}
