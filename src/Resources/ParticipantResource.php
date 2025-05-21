<?php

namespace App\Resources;

class ParticipantResource extends BaseResource
{
    protected function mapItem(mixed $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'email' => $item->getEmail(),
            'created_at' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
