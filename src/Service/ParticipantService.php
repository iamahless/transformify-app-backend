<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ParticipantService
{
    private \stdClass $payload;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParticipantRepository $participantRepository,
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
            $this->payload->status = 201;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function all(): \stdClass
    {
        try {
            $this->payload->participants = $this->participantRepository->findAll();
            $this->payload->message = 'Participants retrieved successfully';
            $this->payload->status = 200;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function getParticipant(string $participantId): \stdClass
    {
        try {
            $participant = $this->participantRepository->find($participantId);
            if (!$participant) {
                throw new \InvalidArgumentException('Participant not found', 404);
            }

            $this->payload->participant = $participant;
            $this->payload->status = 200;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 0 !== $exception->getCode() ? $exception->getCode() : 500;

            return $this->payload;
        }
    }

    public function deleteParticipant(string $participantId): \stdClass
    {
        try {
            $participant = $this->participantRepository->find($participantId);
            if (!$participant) {
                $this->payload->message = 'Participant not found';
                $this->payload->status = 404;

                return $this->payload;
            }

            $appointments = $participant->getAppointments();
            if ($appointments->count() <= 1) {
                foreach ($appointments as $appointment) {
                    $participant->removeAppointment($appointment);
                }
            }

            $this->entityManager->remove($participant);
            $this->entityManager->flush();

            $this->payload->message = 'Participant deleted successfully';
            $this->payload->status = 204;

            return $this->payload;

        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }
}
