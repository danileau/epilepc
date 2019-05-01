<?php

namespace App\Entity;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 */
class Event
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Encrypted
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp_when;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    private $modified_at;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\seizure", inversedBy="events")
     */
    private $seizure;

    public function __construct()
    {
        $this->seizure = new ArrayCollection();
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTimestampWhen(): ?\DateTimeInterface
    {
        return $this->timestamp_when;
    }

    public function setTimestampWhen(\DateTimeInterface $timestamp_when): self
    {
        $this->timestamp_when = $timestamp_when;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modified_at;
    }

    public function setModifiedAt(\DateTimeInterface $modified_at): self
    {
        $this->modified_at = $modified_at;

        return $this;
    }

    /**
     * @return Collection|seizure[]
     */
    public function getSeizure(): Collection
    {
        return $this->seizure;
    }

    public function addSeizure(seizure $seizure): self
    {
        if (!$this->seizure->contains($seizure)) {
            $this->seizure[] = $seizure;
        }

        return $this;
    }

    public function removeSeizure(seizure $seizure): self
    {
        if ($this->seizure->contains($seizure)) {
            $this->seizure->removeElement($seizure);
        }

        return $this;
    }
}
