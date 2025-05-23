<?php

namespace App\Tests\Service;

use App\Entity\Appointment;
use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Service\ParticipantService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParticipantTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private ParticipantRepository|MockObject $participantRepository;
    private ParticipantService $participantService;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->participantRepository = $this->createMock(ParticipantRepository::class);

        $this->participantService = new ParticipantService(
            $this->entityManager,
            $this->participantRepository
        );
    }

    public function testCreateParticipantSuccessfully(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ];

        $this->participantRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $payload['email']])
            ->willReturn(null);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->participantService->create($payload);

        $this->assertEquals(201, $result->status);
        $this->assertInstanceOf(Participant::class, $result->participant);

        $this->assertEquals($payload['name'], $result->participant->getName());
        $this->assertEquals($payload['email'], $result->participant->getEmail());
    }

    public function testCreateParticipantAlreadyExists(): void
    {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
        ];

        $this->participantRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $payload['email']])
            ->willReturn(new Participant());

        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->participantService->create($payload);

        $this->assertEquals(400, $result->status);
        $this->assertEquals('Participant already exists', $result->message);
        $this->assertObjectNotHasProperty('participant', $result);
    }

    public function testCreateParticipantThrowsException(): void
    {
        $payload = [
            'name' => 'Error User',
            'email' => 'error@example.com',
        ];

        $this->participantRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $payload['email']])
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->participantService->create($payload);

        $this->assertEquals(500, $result->status);
        $this->assertEquals('Database error', $result->message);
        $this->assertObjectNotHasProperty('participant', $result);
    }

    public function testAllParticipantsRetrievedSuccessfully(): void
    {
        $participants = [
            (new Participant())->setName('Alice')->setEmail('alice@example.com'),
            (new Participant())->setName('Bob')->setEmail('bob@example.com'),
        ];

        $this->participantRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($participants);

        $result = $this->participantService->all();

        $this->assertEquals(200, $result->status);
        $this->assertEquals('Participants retrieved successfully', $result->message);
        $this->assertIsArray($result->participants);
        $this->assertCount(2, $result->participants);
        $this->assertSame($participants, $result->participants);
    }

    public function testAllParticipantsThrowsException(): void
    {
        $this->participantRepository->expects($this->once())
            ->method('findAll')
            ->willThrowException(new \Exception('Failed to retrieve participants'));

        $result = $this->participantService->all();

        $this->assertEquals(500, $result->status);
        $this->assertEquals('Failed to retrieve participants', $result->message);
        $this->assertObjectNotHasProperty('participants', $result);
    }

    public function testGetParticipantFoundSuccessfully(): void
    {
        $participantId = '123';
        $participant = (new Participant())
            ->setName('Charlie')
            ->setEmail('charlie@example.com');

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn($participant);

        $result = $this->participantService->getParticipant($participantId);

        $this->assertEquals(200, $result->status);
        $this->assertInstanceOf(Participant::class, $result->participant);
        $this->assertSame($participant, $result->participant);
    }

    public function testGetParticipantNotFound(): void
    {
        $participantId = '500';

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn(null);

        $result = $this->participantService->getParticipant($participantId);

        $this->assertEquals(404, $result->status);
        $this->assertEquals('Participant not found', $result->message);
        $this->assertObjectNotHasProperty('participant', $result);
    }

    public function testGetParticipantThrowsException(): void
    {
        $participantId = '100';

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willThrowException(new \Exception('Error finding participant'));

        $result = $this->participantService->getParticipant($participantId);

        $this->assertEquals(500, $result->status);
        $this->assertEquals('Error finding participant', $result->message);
        $this->assertObjectNotHasProperty('participant', $result);
    }

    public function testDeleteParticipantSuccessfullyWithMultipleAppointments()
    {
        $participantId = 'test-participant-id';

        $participant = $this->createMock(Participant::class);
        $appointment1 = $this->createMock(Appointment::class);
        $appointment2 = $this->createMock(Appointment::class);

        $appointments = new ArrayCollection([$appointment1, $appointment2]);

        $participant->method('getAppointments')->willReturn($appointments);
        $participant->expects($this->never())
            ->method('removeAppointment');


        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn($participant);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($participant);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->participantService->deleteParticipant($participantId);

        $this->assertEquals('Participant deleted successfully', $result->message);
        $this->assertEquals(204, $result->status);
    }

    public function testDeleteParticipantSuccessfullyWithOneAppointment()
    {
        $participantId = 'test-participant-id-single-appointment';

        $participant = $this->createMock(Participant::class);
        $appointment1 = $this->createMock(Appointment::class);

        $appointments = new ArrayCollection([$appointment1]);

        $participant->method('getAppointments')->willReturn($appointments);

        $participant->expects($this->once())
            ->method('removeAppointment')
            ->with($appointment1);


        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn($participant);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($participant);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->participantService->deleteParticipant($participantId);

        $this->assertEquals('Participant deleted successfully', $result->message);
        $this->assertEquals(204, $result->status);
    }

    public function testDeleteParticipantSuccessfullyWithNoAppointments()
    {
        $participantId = 'test-participant-id-no-appointments';

        $participant = $this->createMock(Participant::class);
        $appointments = new ArrayCollection([]);

        $participant->method('getAppointments')->willReturn($appointments);
        $participant->expects($this->never())
            ->method('removeAppointment');

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn($participant);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($participant);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->participantService->deleteParticipant($participantId);

        $this->assertEquals('Participant deleted successfully', $result->message);
        $this->assertEquals(204, $result->status);
    }

    public function testDeleteParticipantNotFound()
    {
        $participantId = 'non-existent-participant-id';

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn(null);

        $this->entityManager->expects($this->never())
            ->method('remove');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->participantService->deleteParticipant($participantId);

        $this->assertEquals('Participant not found', $result->message);
        $this->assertEquals(404, $result->status);
    }

    public function testDeleteParticipantThrowsException()
    {
        $participantId = 'exception-causing-participant-id';
        $exceptionMessage = 'Database error occurred';

        $participant = $this->createMock(Participant::class);
        $participant->method('getAppointments')->willReturn(new ArrayCollection());

        $this->participantRepository->expects($this->once())
            ->method('find')
            ->with($participantId)
            ->willReturn($participant);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($participant)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->participantService->deleteParticipant($participantId);

        $this->assertEquals($exceptionMessage, $result->message);
        $this->assertEquals(500, $result->status);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->entityManager, $this->participantRepository, $this->participantService);
    }
}
