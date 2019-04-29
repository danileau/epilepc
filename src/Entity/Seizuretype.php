<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeizuretypeRepository")
 */
class Seizuretype
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Seizure", mappedBy="seizuretype")
     */
    private $seizures;

    public function __construct()
    {
        $this->seizures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function __toString(){
        return $this->getName();
    }

    /**
     * @return Collection|Seizure[]
     */
    public function getSeizures(): Collection
    {
        return $this->seizures;
    }

    public function addSeizure(Seizure $seizure): self
    {
        if (!$this->seizures->contains($seizure)) {
            $this->seizures[] = $seizure;
            $seizure->setSeizuretype($this);
        }

        return $this;
    }

    public function removeSeizure(Seizure $seizure): self
    {
        if ($this->seizures->contains($seizure)) {
            $this->seizures->removeElement($seizure);
            // set the owning side to null (unless already changed)
            if ($seizure->getSeizuretype() === $this) {
                $seizure->setSeizuretype(null);
            }
        }

        return $this;
    }
}
