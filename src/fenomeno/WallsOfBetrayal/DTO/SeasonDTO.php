<?php

namespace fenomeno\WallsOfBetrayal\DTO;

class SeasonDTO
{
    /**
     * @param int $id Identifiant unique de la saison
     * @param int $seasonNumber Numéro de la saison (1, 2, 3, etc.)
     * @param string $name Nom de la saison
     * @param string $theme Thème de la saison
     * @param string $description Description de la saison
     * @param int $startTime Timestamp de début de la saison
     * @param int $plannedEndTime Timestamp prévu pour la fin de la saison
     * @param int|null $actualEndTime Timestamp réel de fin de la saison (null si en cours)
     * @param bool $isActive Indique si la saison est actuellement active
     * @param string $properties Propriétés JSON supplémentaires de la saison
     */
    public function __construct(
        public int $id,
        public int $seasonNumber,
        public string $name,
        public string $theme,
        public string $description,
        public int $startTime,
        public int $plannedEndTime,
        public ?int $actualEndTime,
        public bool $isActive,
        public string $properties
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            seasonNumber: (int) $data['season_number'],
            name: $data['name'],
            theme: $data['theme'],
            description: $data['description'],
            startTime: (int) $data['start_time'],
            plannedEndTime: (int) $data['planned_end_time'],
            actualEndTime: $data['actual_end_time'] !== null ? (int) $data['actual_end_time'] : null,
            isActive: (bool) $data['is_active'],
            properties: $data['properties']
        );
    }

    public function isPaused(): bool
    {
        $props = json_decode($this->properties, true);
        return isset($props['paused']) && $props['paused'] === true;
    }

    public function getDurationDays(): int
    {
        return (int) (($this->plannedEndTime - $this->startTime) / (24 * 60 * 60));
    }

    public function getRemainingDays(): int
    {
        if (!$this->isActive) {
            return 0;
        }

        $endTime = $this->actualEndTime ?? $this->plannedEndTime;
        $remainingSeconds = $endTime - time();

        if ($remainingSeconds <= 0) {
            return 0;
        }

        return (int) ceil($remainingSeconds / (24 * 60 * 60));
    }

    public function getElapsedDays(): int
    {
        $endTime = $this->isActive ? time() : ($this->actualEndTime ?? $this->plannedEndTime);
        $elapsedSeconds = $endTime - $this->startTime;

        if ($elapsedSeconds <= 0) {
            return 0;
        }

        // Tenir compte des périodes de pause
        $props = json_decode($this->properties, true);
        $pauseDuration = $props['totalPauseDuration'] ?? 0;
        $elapsedSeconds -= $pauseDuration;

        return (int) floor($elapsedSeconds / (24 * 60 * 60));
    }

    public function getProgressPercentage(): float
    {
        $totalDuration = $this->getDurationDays();
        if ($totalDuration <= 0) {
            return 100.0;
        }

        $elapsed = $this->getElapsedDays();
        return min(100.0, ($elapsed / $totalDuration) * 100);
    }

    public function getPropertiesArray(): array
    {
        return json_decode($this->properties, true) ?? [];
    }

    public function updateProperty(string $key, $value): void
    {
        $props = $this->getPropertiesArray();
        $props[$key] = $value;
        $this->properties = json_encode($props);
    }

    public function toArray(bool $cast = false): array
    {
        return [
            'id'               => $this->id,
            'season_number'    => $this->seasonNumber,
            'name'             => $this->name,
            'theme'            => $this->theme,
            'description'      => $this->description,
            'start_time'       => $this->startTime,
            'planned_end_time' => $this->plannedEndTime,
            'actual_end_time'  => $this->actualEndTime,
            'is_active'        => $cast ? (int) $this->isActive : $this->isActive,
            'properties'       => $this->properties,
        ];
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
