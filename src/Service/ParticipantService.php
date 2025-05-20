<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;

class ParticipantService
{
    private \stdClass $payload;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ParticipantRepository $participantRepository,
    ) {
        $this->payload = new \stdClass();
    }

    public function create(array $payload): \stdClass
    {
        try {
            if ($this->participantRepository->findOneBy(['email' => $payload['email']])) {
                $this->payload->message = 'Participant already exists';
                $this->payload->status = 400;

                return $this->payload;
            }

            $participant = new Participant();
            $participant->setName($payload['name'])
                ->setEmail($payload['email']);

            $this->entityManager->persist($participant);
            $this->entityManager->flush();

            $this->payload->participant = $participant;
            $this->payload->message = 'Participant created successfully';
            $this->payload->status = 201;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }
}
