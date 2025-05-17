<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "reservation")]
class Reservation
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\ReferenceOne(targetDocument: Chambre::class)]
    private ?Chambre $chambre = null;

    #[MongoDB\ReferenceOne(targetDocument: Client::class, inversedBy: 'reservations', nullable: true)]
    private ?Client $client = null;

    #[MongoDB\Field(type: "date")]
    private ?\DateTime $dateDebut = null;

    #[MongoDB\Field(type: "date")]
    private ?\DateTime $dateFin = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getChambre(): ?Chambre
    {
        return $this->chambre;
    }

    public function setChambre(?Chambre $chambre): self
    {
        $this->chambre = $chambre;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }
}