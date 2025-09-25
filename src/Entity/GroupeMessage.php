<?php

namespace App\Entity;

use App\Repository\GroupeMessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: GroupeMessageRepository::class)]
class GroupeMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: 'boolean')]
    private bool $estActif = true;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $type = null; // 'sortie', 'prive', 'global'

    #[ORM\ManyToOne(targetEntity: Sortie::class)]
    #[ORM\JoinColumn(name: 'sortie_id', referencedColumnName: 'no_sortie', nullable: true, onDelete: 'SET NULL')]
    private ?Sortie $sortie = null;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(name: 'createur_id', referencedColumnName: 'no_participant', nullable: false)]
    private ?Participant $createur = null;

    #[ORM\OneToMany(mappedBy: 'groupe', targetEntity: GroupeMembre::class, cascade: ['persist', 'remove'])]
    private Collection $membres;

    #[ORM\OneToMany(mappedBy: 'groupe', targetEntity: Message::class, cascade: ['persist', 'remove'])]
    private Collection $messages;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): static
    {
        $this->estActif = $estActif;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function getCreateur(): ?Participant
    {
        return $this->createur;
    }

    public function setCreateur(?Participant $createur): static
    {
        $this->createur = $createur;
        return $this;
    }

    /**
     * @return Collection<int, GroupeMembre>
     */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function addMembre(GroupeMembre $membre): static
    {
        if (!$this->membres->contains($membre)) {
            $this->membres->add($membre);
            $membre->setGroupe($this);
        }

        return $this;
    }

    public function removeMembre(GroupeMembre $membre): static
    {
        if ($this->membres->removeElement($membre)) {
            if ($membre->getGroupe() === $this) {
                $membre->setGroupe(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setGroupe($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getGroupe() === $this) {
                $message->setGroupe(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si un participant est membre du groupe
     */
    public function aCommeMembreParticipant(Participant $participant): bool
    {
        foreach ($this->membres as $membre) {
            if ($membre->getParticipant() === $participant && $membre->isActif()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ajouter un participant au groupe
     */
    public function ajouterParticipant(Participant $participant, bool $estAdmin = false): GroupeMembre
    {
        // Vérifier s'il existe déjà
        foreach ($this->membres as $membre) {
            if ($membre->getParticipant() === $participant) {
                $membre->setActif(true);
                $membre->setEstAdmin($estAdmin);
                return $membre;
            }
        }

        // Créer nouveau membre
        $membre = new GroupeMembre();
        $membre->setParticipant($participant);
        $membre->setGroupe($this);
        $membre->setEstAdmin($estAdmin);
        $membre->setDateAjout(new \DateTime());
        $this->addMembre($membre);

        return $membre;
    }

    /**
     * Retirer un participant du groupe
     */
    public function retirerParticipant(Participant $participant): void
    {
        foreach ($this->membres as $membre) {
            if ($membre->getParticipant() === $participant) {
                $membre->setActif(false);
                $membre->setDateRetrait(new \DateTime());
                break;
            }
        }
    }

    /**
     * Obtenir le nombre de membres actifs
     */
    public function getNombreMembresActifs(): int
    {
        $count = 0;
        foreach ($this->membres as $membre) {
            if ($membre->isActif()) {
                $count++;
            }
        }
        return $count;
    }
}