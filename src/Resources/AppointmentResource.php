<?php

namespace App\Resources;

class AppointmentResource extends BaseResource
{
    protected function mapItem(mixed $item): array
    {
        return [
            'id' => $item->getId(),
            'title' => $item->getTitle(),
            'description' => $item->getDescription(),
            'scheduler_name' => $item->getSchedulerName(),
            'scheduler_email' => $item->getSchedulerEmail(),
            'start_at' => $item->getStartAt()->format('Y-m-d H:i:s'),
            'end_at' => $item->getEndAt()->format('Y-m-d H:i:s'),
            'participants' => array_map(function ($participant) {
                return [
                    'id' => $participant->getId(),
                    'name' => $participant->getName(),
                ];
            }, $item->getParticipants()->toArray()),
            'created_at' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
