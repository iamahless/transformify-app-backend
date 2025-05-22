<?php

namespace App\Controller;

use App\DTO\CreateAppointmentRequestDto;
use App\Resources\AppointmentResource;
use App\Service\AppointmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        if (Response::HTTP_OK === $payload->status) {
            return $this->json([
                'appointments' => (new AppointmentResource($payload->appointments))->toResponse(),
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

        if (Response::HTTP_CREATED === $payload->status) {
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

        if (Response::HTTP_OK === $payload->status) {
            return $this->json([
                'appointment' => (new AppointmentResource($payload->appointment))->toResponse(),
            ], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    #[Route('/appointments/{id}', name: 'update_appointment', methods: ['PATCH', 'PUT'], format: 'json')]
    public function update(Request $request): JsonResponse
    {
        $appointmentId = $request->attributes->get('id');
        $data = json_decode($request->getContent(), true);

        $payload = $this->appointmentService->update($appointmentId, $data);

        if (Response::HTTP_OK === $payload->status) {
            return $this->json([
                'appointment' => (new AppointmentResource($payload->appointment))->toResponse(),
            ], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    #[Route('/appointments/{id}', name: 'delete_appointment', methods: ['DELETE'], format: 'json')]
    public function delete(Request $request): JsonResponse
    {
        $appointmentId = $request->attributes->get('id');

        $payload = $this->appointmentService->deleteAppointment($appointmentId);

        if (Response::HTTP_NO_CONTENT === $payload->status) {
            return $this->json([], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }
}
