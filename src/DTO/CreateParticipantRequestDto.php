<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateParticipantRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
}
