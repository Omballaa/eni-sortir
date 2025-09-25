<?php

namespace App\Entity;

use App\Repository\GroupeMembreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupeMembreRepository::class)]
class GroupeMembre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GroupeMessage::class, inversedBy: 'membres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GroupeMessage $groupe = null;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(name: 'participant_id', referencedColumnName: 'no_participant', nullable: false)]
    private ?Participant $participant = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateAjout = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateRetrait = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    #[ORM\Column(type: 'boolean')]
    private bool $estAdmin = false;

    #[ORM\Column(type: 'boolean')]
    private bool $notifications = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $derniereVisite = null;

    public function __construct()
    {
        $this->dateAjout = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupe(): ?GroupeMessage
    {
        return $this->groupe;
    }

    public function setGroupe(?GroupeMessage $groupe): static
    {
        $this->groupe = $groupe;
        return $this;
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

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeInterface $dateAjout): static
    {
        $this->dateAjout = $dateAjout;
        return $this;
    }

    public function getDateRetrait(): ?\DateTimeInterface
    {
        return $this->dateRetrait;
    }

    public function setDateRetrait(?\DateTimeInterface $dateRetrait): static
    {
        $this->dateRetrait = $dateRetrait;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function isEstAdmin(): bool
    {
        return $this->estAdmin;
    }

    public function setEstAdmin(bool $estAdmin): static
    {
        $this->estAdmin = $estAdmin;
        return $this;
    }

    public function isNotifications(): bool
    {
        return $this->notifications;
    }

    public function setNotifications(bool $notifications): static
    {
        $this->notifications = $notifications;
        return $this;
    }

    public function getDerniereVisite(): ?\DateTimeInterface
    {
        return $this->derniereVisite;
    }

    public function setDerniereVisite(?\DateTimeInterface $derniereVisite): static
    {
        $this->derniereVisite = $derniereVisite;
        return $this;
    }

    /**
     * Met à jour la dernière visite à maintenant
     */
    public function mettreAJourDerniereVisite(): static
    {
        $this->derniereVisite = new \DateTime();
        return $this;
    }

    /**
     * Vérifie si le membre a des messages non lus depuis sa dernière visite
     */
    public function aDesMessagesNonLus(): bool
    {
        if (!$this->derniereVisite || !$this->groupe) {
            return false;
        }

        foreach ($this->groupe->getMessages() as $message) {
            if ($message->getDateEnvoi() > $this->derniereVisite && 
                $message->getExpediteur() !== $this->participant) {
                return true;
            }
        }

        return false;
    }
}