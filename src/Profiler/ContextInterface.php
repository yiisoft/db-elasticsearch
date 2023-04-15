<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch\Profiler;

/**
 * Profiling context.
 */
interface ContextInterface
{
    /**
     * @return string Type of the context.
     */
    public function getType(): string;

    public function asArray(): array;
}
