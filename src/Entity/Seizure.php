<?php

namespace App\Entity;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeizureRepository")
 * Definiert die Tabelle Seizure (Anfälle) und sämtliche Funktionen
 * um diese zu pflegen
 */
class Seizure
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Encrypted
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     */
    private $timestamp_when;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $modified_at;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="seizures")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Titel darf nicht leer sein")
     * @Encrypted
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Seizuretype", inversedBy="seizures")
     */
    private $seizuretype;



    public function __construct()
    {

    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function __toString(){
        return $this->getTitle();
    }


    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modified_at;
    }

    public function setModifiedAt(?\DateTimeInterface $modified_at): self
    {
        $this->modified_at = $modified_at;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSeizuretype(): ?Seizuretype
    {
        return $this->seizuretype;
    }

    public function setSeizuretype(?Seizuretype $seizuretype): self
    {
        $this->seizuretype = $seizuretype;

        return $this;
    }



}
