<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AppointmentService
{
    private \stdClass $payload;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ParticipantRepository $participantRepository,
    ) {
        $this->payload = new \stdClass();
    }

    public function all(): \stdClass
    {
        try {
            $this->payload->appointments = $this->appointmentRepository->findAll();
            $this->payload->status = 200;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function create(array $payload): \stdClass
    {
        try {
            $participantEntities = [];

            foreach ($payload['participants'] as $participantId) {
                $participant = $this->participantRepository->find($participantId);
                if (!$participant) {
                    $this->payload->message = "Participant with ID {$participantId} not found.";
                    $this->payload->status = 404;

                    return $this->payload;
                }

                $overlappingAppointments = $this->appointmentRepository->findOverlappingAppointments(
                    $participant,
                    $payload['start_at'],
                    $payload['end_at']
                );

                if (!empty($overlappingAppointments)) {
                    $conflictDetails = [];
                    foreach ($overlappingAppointments as $oa) {
                        $conflictDetails[] = sprintf(
                            'Appointment "%s" (ID: %d) scheduled from %s to %s',
                            $oa->getTitle(),
                            $oa->getId(),
                            $oa->getStartAt()->format('Y-m-d H:i'),
                            $oa->getEndAt()->format('Y-m-d H:i')
                        );
                    }
                    $this->payload->message =
                        sprintf(
                            'Cannot schedule appointment: Participant "%s" (ID: %d) has a conflict with the following appointment(s): %s. Please choose a different time slot.',
                            $participant->getName(),
                            $participant->getId(),
                            implode('; ', $conflictDetails)
                        );
                    $this->payload->status = 409;

                    return $this->payload;
                }

                $participantEntities[] = $participant;
            }

            $appointment = new Appointment();

            $appointment->setTitle($payload['title'])
                ->setDescription($payload['description'])
                ->setSchedulerName($payload['scheduler_name'])
                ->setSchedulerEmail($payload['scheduler_email'])
                ->setStartAt(new \DateTimeImmutable($payload['start_at']))
                ->setEndAt(new \DateTimeImmutable($payload['end_at']))
                ->addParticipants($participantEntities);

            $this->entityManager->persist($appointment);
            $this->entityManager->flush();

            $this->payload->appointment = $appointment;
            $this->payload->status = 201;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function getAppointment(string $appointmentId): \stdClass
    {
        try {
            $appointment = $this->appointmentRepository->find($appointmentId);
            if (!$appointment) {
                $this->payload->message = 'Appointment not found';
                $this->payload->status = 404;

                return $this->payload;
            }

            $this->payload->appointment = $appointment;
            $this->payload->status = 200;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }
}
