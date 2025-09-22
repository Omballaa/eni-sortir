<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
#[ORM\Table(name: 'sorties')]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'no_sortie')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 30)]
    private ?string $nom = null;

    #[ORM\Column(name: 'date_heure_debut', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateHeureDebut = null;

    #[ORM\Column(name: 'duree', type: 'integer', nullable: true)]
    private ?int $duree = null;

    #[ORM\Column(name: 'date_limite_inscription', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateLimiteInscription = null;

    #[ORM\Column(name: 'nb_inscriptions_max', type: 'integer')]
    private ?int $nbInscriptionsMax = null;

    #[ORM\Column(name: 'infos_sortie', type: Types::TEXT, nullable: true)]
    private ?string $infosSortie = null;

    #[ORM\ManyToOne(targetEntity: Etat::class, inversedBy: 'sorties')]
    #[ORM\JoinColumn(name: 'no_etat', referencedColumnName: 'no_etat', nullable: false)]
    private ?Etat $etat = null;

    #[ORM\ManyToOne(targetEntity: Lieu::class, inversedBy: 'sorties')]
    #[ORM\JoinColumn(name: 'no_lieu', referencedColumnName: 'no_lieu', nullable: false)]
    private ?Lieu $lieu = null;

    #[ORM\ManyToOne(targetEntity: Participant::class, inversedBy: 'sortiesOrganisees')]
    #[ORM\JoinColumn(name: 'no_organisateur', referencedColumnName: 'no_participant', nullable: false)]
    private ?Participant $organisateur = null;

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'sortie')]
    private Collection $inscriptions;

    public function __construct()
    {
        $this->inscriptions = new ArrayCollection();
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

    public function getDateHeureDebut(): ?\DateTimeInterface
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(\DateTimeInterface $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTimeInterface
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(\DateTimeInterface $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    public function getNbInscriptionsMax(): ?int
    {
        return $this->nbInscriptionsMax;
    }

    public function setNbInscriptionsMax(int $nbInscriptionsMax): static
    {
        $this->nbInscriptionsMax = $nbInscriptionsMax;

        return $this;
    }

    public function getInfosSortie(): ?string
    {
        return $this->infosSortie;
    }

    public function setInfosSortie(?string $infosSortie): static
    {
        $this->infosSortie = $infosSortie;

        return $this;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(?Etat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;

        return $this;
    }

    /**
     * @return Collection<int, Inscription>
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setSortie($this);
        }

        return $this;
    }

    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            // set the owning side to null (unless already changed)
            if ($inscription->getSortie() === $this) {
                $inscription->setSortie(null);
            }
        }

        return $this;
    }

    /**
     * Retourne le nombre d'inscriptions actuelles
     */
    public function getNbInscriptions(): int
    {
        return $this->inscriptions->count();
    }

    /**
     * Vérifie si la sortie est complète
     */
    public function isComplete(): bool
    {
        return $this->getNbInscriptions() >= $this->nbInscriptionsMax;
    }

    /**
     * Vérifie si les inscriptions sont encore ouvertes
     */
    public function isInscriptionOuverte(): bool
    {
        $now = new \DateTime();
        return $this->dateLimiteInscription >= $now && !$this->isComplete();
    }

    public function __toString(): string
    {
        return $this->nom . ' - ' . $this->dateHeureDebut->format('d/m/Y H:i');
    }
}