<?php

namespace App\Controller;

use App\DTO\CreateParticipantRequestDto;
use App\Service\ParticipantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ParticipantController extends AbstractController
{
    public function __construct(
        private readonly ParticipantService $participantService,
    ) {
    }

    #[Route('/participants', name: 'get_all_participants', methods: ['GET'], format: 'json')]
    public function index(): JsonResponse
    {
        $payload = $this->participantService->all();

        if (200 === $payload->status) {
            return $this->json([
                'participants' => $payload->participants,
            ], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }

    #[Route('/participants', name: 'create_participant', methods: ['POST'], format: 'json')]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateParticipantRequestDto();
        $dto->name = $data['name'] ?? '';
        $dto->email = $data['email'] ?? '';

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath().': '.$error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], 400);
        }

        $payload = $this->participantService->create($data);

        if (201 === $payload->status) {
            return $this->json([
                'participant' => $payload->participant,
            ], $payload->status);
        }

        return $this->json([
            'message' => $payload->message,
        ], $payload->status);
    }
}
