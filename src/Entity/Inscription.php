<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ORM\Table(name: 'inscriptions')]
class Inscription
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Participant::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(name: 'no_participant', referencedColumnName: 'no_participant', nullable: false)]
    private ?Participant $participant = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Sortie::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(name: 'no_sortie', referencedColumnName: 'no_sortie', nullable: false)]
    private ?Sortie $sortie = null;

    #[ORM\Column(name: 'date_inscription', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    public function setParticipant(?Participant $participant): static
    {
        $this->participant = $participant;

        return $this;
    }

    public function getSortie(): ?Sortie
    {
        return $this->sortie;
    }

    public function setSortie(?Sortie $sortie): static
    {
        $this->sortie = $sortie;

        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function __toString(): string
    {
        return $this->participant . ' -> ' . $this->sortie . ' (' . $this->dateInscription->format('d/m/Y H:i') . ')';
    }
}