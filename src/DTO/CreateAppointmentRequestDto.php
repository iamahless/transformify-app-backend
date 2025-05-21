<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAppointmentRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $title;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $description;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $schedulerName;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $schedulerEmail;

    #[Assert\NotBlank]
    #[Assert\DateTime]
    public string $startAt;

    #[Assert\NotBlank]
    #[Assert\DateTime]
    public string $endAt;

    #[Assert\NotBlank]
    #[Assert\Type('Array')]
    public array $participants;
}
