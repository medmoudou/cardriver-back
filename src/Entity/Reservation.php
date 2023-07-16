<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\Controller\ReservationController;
use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[
    ApiResource(
        operations: [
            new Post(
                controller: ReservationController::class,
                deserialize: false,
                security: "is_granted('ROLE_USER')",
            ),
            new GetCollection(
                normalizationContext: ['groups' => ['reservation:read']],
            ),
            new Get(
                normalizationContext: ['groups' => ['reservation:read']],
            ),
            new Patch(
                controller: ReservationController::class,
                deserialize: false,
                // security: "is_granted('ROLE_USER')",
            ),
        ],
    ),
    ApiFilter(
        SearchFilter::class,
        properties: [
            'status' => 'exact',
            'car.immatriculation' => 'partial'
        ]
    ),
    ApiFilter(
        OrderFilter::class,
        properties: ['id'],
    )


]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('reservation:read')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('reservation:read')]
    private ?User $owner = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('reservation:read')]
    private ?Address $addressFrom = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('reservation:read')]
    private ?Address $addressTo = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('reservation:read')]
    private ?Car $car = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups('reservation:read')]
    private ?\DateTimeInterface $availableFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups('reservation:read')]
    private ?\DateTimeInterface $availableTo = null;

    #[ORM\Column]
    #[Groups('reservation:read')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    #[Groups('reservation:read')]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 255)]
    #[Groups('reservation:read')]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    #[Groups('reservation:read')]
    private ?string $price = null;

    #[ORM\Column]
    #[Groups('reservation:read')]
    private ?float $priceHT = null;

    #[ORM\Column]
    #[Groups('reservation:read')]
    private ?float $priceTTC = null;

    #[Groups('reservation:read')]
    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: Track::class)]
    private Collection $tracks;



    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->tracks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getAddressFrom(): ?Address
    {
        return $this->addressFrom;
    }

    public function setAddressFrom(Address $addressFrom): static
    {
        $this->addressFrom = $addressFrom;

        return $this;
    }

    public function getAddressTo(): ?Address
    {
        return $this->addressTo;
    }

    public function setAddressTo(Address $addressTo): static
    {
        $this->addressTo = $addressTo;

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

        return $this;
    }

    public function getAvailableFrom(): ?\DateTimeInterface
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(\DateTimeInterface $availableFrom): static
    {
        $this->availableFrom = $availableFrom;

        return $this;
    }

    public function getAvailableTo(): ?\DateTimeInterface
    {
        return $this->availableTo;
    }

    public function setAvailableTo(\DateTimeInterface $availableTo): static
    {
        $this->availableTo = $availableTo;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Track>
     */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function addTrack(Track $track): static
    {
        if (!$this->tracks->contains($track)) {
            $this->tracks->add($track);
            $track->setReservation($this);
        }

        return $this;
    }

    public function removeTrack(Track $track): static
    {
        if ($this->tracks->removeElement($track)) {
            // set the owning side to null (unless already changed)
            if ($track->getReservation() === $this) {
                $track->setReservation(null);
            }
        }

        return $this;
    }

    public function getPriceHT(): ?float
    {
        return $this->priceHT;
    }

    public function setPriceHT(float $priceHT): static
    {
        $this->priceHT = $priceHT;

        return $this;
    }

    public function getPriceTTC(): ?float
    {
        return $this->priceTTC;
    }

    public function setPriceTTC(float $priceTTC): static
    {
        $this->priceTTC = $priceTTC;

        return $this;
    }
}
