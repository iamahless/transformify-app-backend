<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Participant;
use App\Repository\AppointmentRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AppointmentService
{
    private \stdClass $payload;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AppointmentRepository $appointmentRepository,
        private ParticipantRepository $participantRepository,
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
            $participants = [];

            $startAt = new \DateTimeImmutable($payload['start_at']);
            $endAt = new \DateTimeImmutable($payload['end_at']);

            if ($startAt >= $endAt) {
                throw new \InvalidArgumentException('The appointment start time must be before the end time.', 400);
            }

            foreach ($payload['participants'] as $participantId) {
                $participant = $this->participantRepository->find($participantId);
                if (!$participant) {
                    throw new \InvalidArgumentException("Participant with ID {$participantId} not found.", 404);
                }

                $overlappingAppointments = $this->appointmentRepository->findOverlappingAppointments(
                    $participant,
                    $startAt->format('Y-m-d H:i:s'),
                    $endAt->format('Y-m-d H:i:s')
                );

                if (!empty($overlappingAppointments)) {
                    $conflictDetails = $this->formatConflictDetails($overlappingAppointments);
                    throw new \InvalidArgumentException(sprintf('Participant "%s" (ID: %s) has a conflict with: %s. Please choose a different time slot.', $participant->getName(), $participant->getId(), implode('; ', $conflictDetails)), 409);
                }

                $participants[] = $participant;
            }

            $appointment = new Appointment();
            $appointment->setTitle($payload['title'])
                ->setDescription($payload['description'])
                ->setSchedulerName($payload['scheduler_name'])
                ->setSchedulerEmail($payload['scheduler_email'])
                ->setStartAt($startAt)
                ->setEndAt($endAt)
                ->addParticipants($participants);

            $this->entityManager->persist($appointment);
            $this->entityManager->flush();

            $this->payload->appointment = $appointment;
            $this->payload->status = 201;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 0 !== $exception->getCode() ? $exception->getCode() : 500;

            return $this->payload;
        }
    }

    private function formatConflictDetails(array $overlappingAppointments): array
    {
        $conflictDetails = [];
        foreach ($overlappingAppointments as $appointment) {
            $conflictDetails[] = sprintf(
                'Appointment "%s" (ID: %s) from %s to %s',
                $appointment->getTitle(),
                $appointment->getId(),
                $appointment->getStartAt()->format('Y-m-d H:i'),
                $appointment->getEndAt()->format('Y-m-d H:i')
            );
        }

        return $conflictDetails;
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

    public function deleteAppointment(string $appointmentId): \stdClass
    {
        try {
            $appointment = $this->appointmentRepository->find($appointmentId);
            if (!$appointment) {
                $this->payload->message = 'Appointment not found';
                $this->payload->status = 404;

                return $this->payload;
            }

            $this->entityManager->remove($appointment);
            $this->entityManager->flush();

            $this->payload->message = 'Appointment deleted successfully';
            $this->payload->status = 204;

            return $this->payload;

        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 500;

            return $this->payload;
        }
    }

    public function update(string $appointmentId, array $payload): \stdClass
    {
        try {
            $appointment = $this->appointmentRepository->find($appointmentId);
            if (!$appointment) {
                $this->payload->message = 'Appointment not found';
                $this->payload->status = 404;

                return $this->payload;
            }

            $appointment->setTitle($payload['title'] ?? $appointment->getTitle());
            $appointment->setDescription($payload['description'] ?? $appointment->getDescription());
            $appointment->setSchedulerName($payload['scheduler_name'] ?? $appointment->getSchedulerName());
            $appointment->setSchedulerEmail($payload['scheduler_email'] ?? $appointment->getSchedulerEmail());

            $startAt = $appointment->getStartAt();
            $endAt = $appointment->getEndAt();

            if (isset($payload['start_at'])) {
                $startAt = new \DateTimeImmutable($payload['start_at']);
                $appointment->setStartAt($startAt);
            }
            if (isset($payload['end_at'])) {
                $endAt = new \DateTimeImmutable($payload['end_at']);
                $appointment->setEndAt($endAt);
            }

            if ($startAt >= $endAt) {
                throw new \InvalidArgumentException('The appointment start time must be before the end time.', 400);
            }

            $currentParticipantIds = $appointment->getParticipants()->map(fn (Participant $p) => $p->getId())->toArray();

            $newParticipantIds = $payload['participants'] ?? [];

            $participantsToRemoveIds = array_diff($currentParticipantIds, $newParticipantIds);
            foreach ($participantsToRemoveIds as $participantId) {
                $participantToRemove = $this->participantRepository->find($participantId);
                if ($participantToRemove) {
                    $appointment->removeParticipant($participantToRemove);
                }
            }

            $participantsToAddIds = array_diff($newParticipantIds, $currentParticipantIds);
            foreach ($participantsToAddIds as $participantId) {
                $participant = $this->participantRepository->find($participantId);
                if (!$participant) {
                    throw new \InvalidArgumentException("Participant with ID {$participantId} not found.", 404);
                }

                $overlappingAppointments = $this->appointmentRepository->findOverlappingAppointments(
                    $participant,
                    $startAt->format('Y-m-d H:i:s'),
                    $endAt->format('Y-m-d H:i:s')
                );

                if (!empty($overlappingAppointments)) {
                    $conflictDetails = $this->formatConflictDetails($overlappingAppointments);
                    throw new \InvalidArgumentException(sprintf('Participant "%s" (ID: %s) has a conflict with: %s. Please choose a different time slot.', $participant->getName(), $participant->getId(), implode('; ', $conflictDetails)), 409);
                }

                $appointment->addParticipant($participant);
            }

            $this->entityManager->persist($appointment);
            $this->entityManager->flush();

            $this->payload->appointment = $appointment;
            $this->payload->status = 200;

            return $this->payload;
        } catch (\Exception $exception) {
            $this->payload->message = $exception->getMessage();
            $this->payload->status = 0 !== $exception->getCode() ? $exception->getCode() : 500;

            return $this->payload;
        }
    }
}
