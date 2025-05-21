<?php

namespace App\Controller;

use App\DTO\CreateAppointmentRequestDto;
use App\Resources\AppointmentResource;
use App\Service\AppointmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {
    }

    #[Route('/appointments', name: 'get_appointments', methods: ['GET'], format: 'json')]
    public function index(): JsonResponse
    {
        $payload = $this->appointmentService->all();

        if (200 === $payload->status) {
            return $this->json([
                'appointments' => (new AppointmentResource($payload->appointments))->toResponse(),
                // 'appointments' => AppointmentResource::collection($payload->appointments),
            ], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    #[Route('/appointments', name: 'create_appointment', methods: ['POST'], format: 'json')]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateAppointmentRequestDto();
        $dto->title = $data['title'] ?? '';
        $dto->description = $data['description'] ?? '';
        $dto->schedulerName = $data['scheduler_name'] ?? '';
        $dto->schedulerEmail = $data['scheduler_email'] ?? '';
        $dto->startAt = $data['start_at'] ?? '';
        $dto->endAt = $data['end_at'] ?? '';
        $dto->participants = $data['participants'] ?? [];

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath().': '.$error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }

        $payload = $this->appointmentService->create($data);

        if (201 === $payload->status) {
            return $this->json([
                'appointment' => (new AppointmentResource($payload->appointment))->toResponse(),
            ], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    #[Route('/appointments/{id}', name: 'get_appointment', methods: ['GET'], format: 'json')]
    public function show(Request $request): JsonResponse
    {
        $appointmentId = $request->attributes->get('id');

        $payload = $this->appointmentService->getAppointment($appointmentId);

        if (200 === $payload->status) {
            return $this->json([
                'appointment' => (new AppointmentResource($payload->appointment))->toResponse(),
            ], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }
}
