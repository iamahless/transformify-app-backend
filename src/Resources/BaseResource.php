<?php

namespace App\Resources;

abstract class BaseResource
{
    protected mixed $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function toArray(): array
    {
        if (is_array($this->resource)) {
            return array_map(function ($item) {
                return $this->mapItem($item);
            }, $this->resource);
        }

        return $this->mapItem($this->resource);
    }

    /**
     * Override this in the child class to define mapping.
     */
    abstract protected function mapItem(mixed $item): array;

    /**
     * Return a JSON response.
     */
    public function toResponse(): array
    {
        return $this->toArray();
    }
}
