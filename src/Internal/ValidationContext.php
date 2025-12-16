<?php

declare(strict_types=1);

namespace JardisSupport\Validation\Internal;

/**
 * Tracks validation state during object graph traversal.
 * Prevents infinite recursion and duplicate validation.
 */
final class ValidationContext
{
    /**
     * @var array<int, bool> Tracks already validated objects by spl_object_id
     */
    private array $visitedObjects = [];

    private int $currentDepth = 0;

    public function __construct(
        private readonly int $maxDepth = 100
    ) {
    }

    /**
     * Checks if an object has already been validated.
     *
     * @param object $object
     * @return bool
     */
    public function hasVisited(object $object): bool
    {
        return isset($this->visitedObjects[spl_object_id($object)]);
    }

    /**
     * Marks an object as visited.
     *
     * @param object $object
     * @return void
     */
    public function markVisited(object $object): void
    {
        $this->visitedObjects[spl_object_id($object)] = true;
    }

    /**
     * Enters a new recursion level.
     *
     * @return void
     * @throws \RuntimeException if max depth is exceeded
     */
    public function enterLevel(): void
    {
        $this->currentDepth++;

        if ($this->currentDepth > $this->maxDepth) {
            throw new \RuntimeException("Maximum validation depth of {$this->maxDepth} exceeded");
        }
    }

    /**
     * Exits the current recursion level.
     *
     * @return void
     */
    public function exitLevel(): void
    {
        $this->currentDepth--;
    }

    /**
     * Returns the current recursion depth.
     *
     * @return int
     */
    public function getDepth(): int
    {
        return $this->currentDepth;
    }
}
