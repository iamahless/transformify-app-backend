<?php

namespace App\Tests\Service;

use App\Entity\Appointment;
use App\Entity\Participant;
use App\Repository\AppointmentRepository;
use App\Repository\ParticipantRepository;
use App\Service\AppointmentService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AppointmentTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private AppointmentRepository|MockObject $appointmentRepository;
    private ParticipantRepository|MockObject $participantRepository;
    private AppointmentService $appointmentService;

    private int $findOverlappingAppointmentsCallCount;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->appointmentRepository = $this->createMock(AppointmentRepository::class);
        $this->participantRepository = $this->createMock(ParticipantRepository::class);

        $this->appointmentService = new AppointmentService(
            $this->entityManager,
            $this->appointmentRepository,
            $this->participantRepository
        );
    }

    public function testAllAppointmentsRetrievedSuccessfully(): void
    {
        $appointment1 = (new Appointment())->setTitle('Meeting 1')
            ->setStartAt(new \DateTimeImmutable())
            ->setEndAt(new \DateTimeImmutable());
        $appointment2 = (new Appointment())->setTitle('Meeting 2')
            ->setStartAt(new \DateTimeImmutable())
            ->setEndAt(new \DateTimeImmutable());
        $appointmentsArray = [$appointment1, $appointment2];

        $this->appointmentRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($appointmentsArray);

        $result = $this->appointmentService->all();

        $this->assertEquals(200, $result->status);
        $this->assertIsArray($result->appointments);
        $this->assertCount(2, $result->appointments);
        $this->assertSame($appointmentsArray, $result->appointments);
    }

    public function testAllAppointmentsThrowsException(): void
    {
        $this->appointmentRepository->expects($this->once())
            ->method('findAll')
            ->willThrowException(new \Exception('Database connection error'));

        $result = $this->appointmentService->all();

        $this->assertEquals(500, $result->status);
        $this->assertEquals('Database connection error', $result->message);
        $this->assertObjectNotHasProperty('appointments', $result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testCreateAppointmentSuccessfully(): void
    {
        $participantId1 = 'p1';
        $participantId2 = 'p2';
        $payload = [
            'title' => 'Team Meeting',
            'description' => 'Weekly sync-up',
            'scheduler_name' => 'Admin',
            'scheduler_email' => 'admin@example.com',
            'start_at' => '2025-06-01 10:00:00',
            'end_at' => '2025-06-01 11:00:00',
            'participants' => [$participantId1, $participantId2],
        ];

        $participant1 = $this->createMock(Participant::class);
        $participant2 = $this->createMock(Participant::class);

        $this->participantRepository->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [$participantId1, $participant1],
                [$participantId2, $participant2],
            ]);

        $this->findOverlappingAppointmentsCallCount = 0;

        $this->appointmentRepository->expects($this->exactly(2))
            ->method('findOverlappingAppointments')
            ->willReturnCallback(function ($actualParticipant, $actualStartAt, $actualEndAt) use ($participant1, $participant2, $payload) {
                ++$this->findOverlappingAppointmentsCallCount;

                if (1 === $this->findOverlappingAppointmentsCallCount) {
                    $this->assertSame($participant1, $actualParticipant, 'Argument 1 for call 1 should be participant1');
                    $this->assertEquals($payload['start_at'], $actualStartAt, 'Argument 2 for call 1 should be start_at from payload');
                    $this->assertEquals($payload['end_at'], $actualEndAt, 'Argument 3 for call 1 should be end_at from payload');
                } elseif (2 === $this->findOverlappingAppointmentsCallCount) {
                    $this->assertSame($participant2, $actualParticipant, 'Argument 1 for call 2 should be participant2');
                    $this->assertEquals($payload['start_at'], $actualStartAt, 'Argument 2 for call 2 should be start_at from payload');
                    $this->assertEquals($payload['end_at'], $actualEndAt, 'Argument 3 for call 2 should be end_at from payload');
                } else {
                    $this->fail('findOverlappingAppointments was called more than expected.');
                }

                return [];
            });

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->appointmentService->create($payload);

        $this->assertEquals(201, $result->status);
        $this->assertInstanceOf(Appointment::class, $result->appointment);
        $this->assertEquals($payload['title'], $result->appointment->getTitle());
        $this->assertEquals(new \DateTimeImmutable($payload['start_at']), $result->appointment->getStartAt());
        $this->assertCount(2, $result->appointment->getParticipants());
    }

    /**
     * @throws Exception
     */
    public function testCreateAppointmentParticipantNotFound(): void
    {
        $participantId1 = 'valid-p1';
        $nonExistentParticipantId = 'invalid-p2';
        $payload = [
            'title' => 'Workshop',
            'description' => 'A workshop session',
            'scheduler_name' => 'Organizer',
            'scheduler_email' => 'organizer@example.com',
            'start_at' => '2025-07-01 14:00:00',
            'end_at' => '2025-07-01 16:00:00',
            'participants' => [$participantId1, $nonExistentParticipantId],
        ];

        $participant1 = $this->createMock(Participant::class);

        $this->participantRepository->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [$participantId1, $participant1],
                [$nonExistentParticipantId, null],
            ]);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->appointmentService->create($payload);

        $this->assertEquals(404, $result->status);
        $this->assertEquals("Participant with ID {$nonExistentParticipantId} not found.", $result->message);
        $this->assertObjectNotHasProperty('appointment', $result);
    }

    /**
     * @throws Exception
     */
    public function testCreateAppointmentWithOverlappingAppointment(): void
    {
        $participantId = 'p-conflict';
        $payload = [
            'title' => 'New Event',
            'description' => 'Trying to book a slot',
            'scheduler_name' => 'Booker',
            'scheduler_email' => 'booker@example.com',
            'start_at' => '2025-08-01 09:00:00',
            'end_at' => '2025-08-01 10:00:00',
            'participants' => [$participantId],
        ];

        $participant = $this->createMock(Participant::class);
        $participant->method('getName')->willReturn('Conflicting User');
        $participant->method('getId')->willReturn(123);

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn($participant);

        $overlappingAppointment = $this->createMock(Appointment::class);
        $overlappingAppointment->method('getTitle')->willReturn('Existing Meeting');
        $overlappingAppointment->method('getId')->willReturn(456);
        $overlappingAppointment->method('getStartAt')->willReturn(new \DateTimeImmutable('2025-08-01 08:30:00'));
        $overlappingAppointment->method('getEndAt')->willReturn(new \DateTimeImmutable('2025-08-01 09:30:00'));

        $this->appointmentRepository->expects($this->once())
            ->method('findOverlappingAppointments')
            ->with($this->identicalTo($participant), $payload['start_at'], $payload['end_at'])
            ->willReturn([$overlappingAppointment]);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->appointmentService->create($payload);

        $this->assertEquals(409, $result->status);
        $expectedMessage = sprintf(
            'Cannot schedule appointment: Participant "Conflicting User" (ID: 123) has a conflict with the following appointment(s): Appointment "Existing Meeting" (ID: 456) scheduled from %s to %s. Please choose a different time slot.',
            (new \DateTimeImmutable('2025-08-01 08:30:00'))->format('Y-m-d H:i'),
            (new \DateTimeImmutable('2025-08-01 09:30:00'))->format('Y-m-d H:i')
        );
        $this->assertEquals($expectedMessage, $result->message);
        $this->assertObjectNotHasProperty('appointment', $result);
    }

    /**
     * @throws Exception
     */
    public function testCreateAppointmentThrowsExceptionDuringPersist(): void
    {
        $participantId = 'p-persist-error';
        $payload = [
            'title' => 'Error Event',
            'description' => 'Event causing DB error',
            'scheduler_name' => 'Test User',
            'scheduler_email' => 'test@example.com',
            'start_at' => '2025-09-01 10:00:00',
            'end_at' => '2025-09-01 11:00:00',
            'participants' => [$participantId],
        ];

        $participant = $this->createMock(Participant::class);

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn($participant);

        $this->appointmentRepository->expects($this->once())
            ->method('findOverlappingAppointments')
            ->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Database persist error'));

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->appointmentService->create($payload);

        $this->assertEquals(500, $result->status);
        $this->assertEquals('Database persist error', $result->message);
        $this->assertObjectNotHasProperty('appointment', $result);
    }

    public function testGetAppointmentFoundSuccessfully(): void
    {
        $appointmentId = 'app-123';
        $appointment = (new Appointment())->setTitle('Specific Meeting')
            ->setStartAt(new \DateTimeImmutable())
            ->setEndAt(new \DateTimeImmutable());

        $this->appointmentRepository->expects($this->once())
            ->method('find')
            ->with($appointmentId)
            ->willReturn($appointment);

        $result = $this->appointmentService->getAppointment($appointmentId);

        $this->assertEquals(200, $result->status);
        $this->assertInstanceOf(Appointment::class, $result->appointment);
        $this->assertSame($appointment, $result->appointment);
    }

    public function testGetAppointmentNotFound(): void
    {
        $appointmentId = 'nonexistent-app-id';

        $this->appointmentRepository->expects($this->once())
            ->method('find')
            ->with($appointmentId)
            ->willReturn(null);

        $result = $this->appointmentService->getAppointment($appointmentId);

        $this->assertEquals(404, $result->status);
        $this->assertEquals('Appointment not found', $result->message);
        $this->assertObjectNotHasProperty('appointment', $result);
    }

    public function testGetAppointmentThrowsException(): void
    {
        $appointmentId = 'exception-app-id';

        $this->appointmentRepository->expects($this->once())
            ->method('find')
            ->with($appointmentId)
            ->willThrowException(new \Exception('Error finding appointment'));

        $result = $this->appointmentService->getAppointment($appointmentId);

        $this->assertEquals(500, $result->status);
        $this->assertEquals('Error finding appointment', $result->message);
        $this->assertObjectNotHasProperty('appointment', $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset(
            $this->entityManager,
            $this->appointmentRepository,
            $this->participantRepository,
            $this->appointmentService
        );
    }
}
