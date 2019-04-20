<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups("main")
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("main")
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("main")
     */
    private $lastname;

    /**
     * @ORM\Column(type="boolean")
     */
    private $deactivated;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Seizure", mappedBy="user")
     */
    private $seizures;


    public function __construct()
    {
        $this->created_at = new ArrayCollection();
        $this->seizures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" or "argon" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getDeactivated(): ?bool
    {
        return $this->deactivated;
    }

    public function setDeactivated(bool $deactivated): self
    {
        $this->deactivated = $deactivated;

        return $this;
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
            $seizure->setUser($this);
        }

        return $this;
    }

    public function removeSeizure(Seizure $seizure): self
    {
        if ($this->seizures->contains($seizure)) {
            $this->seizures->removeElement($seizure);
            // set the owning side to null (unless already changed)
            if ($seizure->getUser() === $this) {
                $seizure->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(){
        return $this->getFirstname();
    }

}
