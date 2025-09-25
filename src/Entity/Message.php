<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $contenu = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateEnvoi = null;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(name: 'expediteur_id', referencedColumnName: 'no_participant', nullable: false)]
    private ?Participant $expediteur = null;

    #[ORM\ManyToOne(targetEntity: GroupeMessage::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?GroupeMessage $groupe = null;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(name: 'destinataire_id', referencedColumnName: 'no_participant', nullable: true)]
    private ?Participant $destinataire = null;

    #[ORM\Column(type: 'boolean')]
    private bool $estSysteme = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $typeSysteme = null; // 'join', 'leave', 'sortie_cancelled', 'sortie_published', etc.

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: MessageStatus::class, cascade: ['persist', 'remove'])]
    private Collection $statuts;

    public function __construct()
    {
        $this->statuts = new ArrayCollection();
        $this->dateEnvoi = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateEnvoi(): ?\DateTimeInterface
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(\DateTimeInterface $dateEnvoi): static
    {
        $this->dateEnvoi = $dateEnvoi;
        return $this;
    }

    public function getExpediteur(): ?Participant
    {
        return $this->expediteur;
    }

    public function setExpediteur(?Participant $expediteur): static
    {
        $this->expediteur = $expediteur;
        return $this;
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

    public function getDestinataire(): ?Participant
    {
        return $this->destinataire;
    }

    public function setDestinataire(?Participant $destinataire): static
    {
        $this->destinataire = $destinataire;
        return $this;
    }

    public function isEstSysteme(): bool
    {
        return $this->estSysteme;
    }

    public function setEstSysteme(bool $estSysteme): static
    {
        $this->estSysteme = $estSysteme;
        return $this;
    }

    public function getTypeSysteme(): ?string
    {
        return $this->typeSysteme;
    }

    public function setTypeSysteme(?string $typeSysteme): static
    {
        $this->typeSysteme = $typeSysteme;
        return $this;
    }

    /**
     * @return Collection<int, MessageStatus>
     */
    public function getStatuts(): Collection
    {
        return $this->statuts;
    }

    public function addStatut(MessageStatus $statut): static
    {
        if (!$this->statuts->contains($statut)) {
            $this->statuts->add($statut);
            $statut->setMessage($this);
        }

        return $this;
    }

    public function removeStatut(MessageStatus $statut): static
    {
        if ($this->statuts->removeElement($statut)) {
            if ($statut->getMessage() === $this) {
                $statut->setMessage(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si le message a été lu par un participant donné
     */
    public function estLuPar(Participant $participant): bool
    {
        foreach ($this->statuts as $statut) {
            if ($statut->getParticipant() === $participant && $statut->isLu()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Marque le message comme lu par un participant
     */
    public function marquerLuPar(Participant $participant): void
    {
        // Chercher le statut existant
        foreach ($this->statuts as $statut) {
            if ($statut->getParticipant() === $participant) {
                $statut->setLu(true);
                $statut->setDateLecture(new \DateTime());
                return;
            }
        }

        // Créer un nouveau statut si inexistant
        $statut = new MessageStatus();
        $statut->setParticipant($participant);
        $statut->setMessage($this);
        $statut->setLu(true);
        $statut->setDateLecture(new \DateTime());
        $this->addStatut($statut);
    }
}