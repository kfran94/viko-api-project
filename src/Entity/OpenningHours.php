<?php

namespace App\Entity;

use App\Repository\OpenningHoursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpenningHoursRepository::class)]
class OpenningHours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $day = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $opening_hours = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $closing_hours = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $break = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?string
    {
        return $this->day;
    }

    public function setDay(string $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getOpeningHours(): ?\DateTimeInterface
    {
        return $this->opening_hours;
    }

    public function setOpeningHours(\DateTimeInterface $opening_hours): self
    {
        $this->opening_hours = $opening_hours;

        return $this;
    }

    public function getClosingHours(): ?\DateTimeInterface
    {
        return $this->closing_hours;
    }

    public function setClosingHours(\DateTimeInterface $closing_hours): self
    {
        $this->closing_hours = $closing_hours;

        return $this;
    }

    public function getBreak(): ?\DateTimeInterface
    {
        return $this->break;
    }

    public function setBreak(\DateTimeInterface $break): self
    {
        $this->break = $break;

        return $this;
    }
}
