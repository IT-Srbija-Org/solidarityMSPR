<?php

namespace App\Entity;

use App\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'tenant', targetEntity: UserDonor::class)]
    private Collection $userDonors;

    #[ORM\OneToMany(mappedBy: 'tenant', targetEntity: DamagedEducator::class)]
    private Collection $damagedEducators;

    public function __construct()
    {
        $this->userDonors = new ArrayCollection();
        $this->damagedEducators = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /**
     * @return Collection<int, UserDonor>
     */
    public function getUserDonors(): Collection
    {
        return $this->userDonors;
    }

    public function addUserDonor(UserDonor $userDonor): static
    {
        if (!$this->userDonors->contains($userDonor)) {
            $this->userDonors->add($userDonor);
            $userDonor->setTenant($this);
        }

        return $this;
    }

    public function removeUserDonor(UserDonor $userDonor): static
    {
        if ($this->userDonors->removeElement($userDonor)) {
            // set the owning side to null (unless already changed)
            if ($userDonor->getTenant() === $this) {
                $userDonor->setTenant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DamagedEducator>
     */
    public function getDamagedEducators(): Collection
    {
        return $this->damagedEducators;
    }

    public function addDamagedEducator(DamagedEducator $damagedEducator): static
    {
        if (!$this->damagedEducators->contains($damagedEducator)) {
            $this->damagedEducators->add($damagedEducator);
            $damagedEducator->setTenant($this);
        }

        return $this;
    }

    public function removeDamagedEducator(DamagedEducator $damagedEducator): static
    {
        if ($this->damagedEducators->removeElement($damagedEducator)) {
            // set the owning side to null (unless already changed)
            if ($damagedEducator->getTenant() === $this) {
                $damagedEducator->setTenant(null);
            }
        }

        return $this;
    }
}
