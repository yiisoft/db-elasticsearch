<?php

declare(strict_types=1);

namespace Yiisoft\Elasticsearch\Profiler;

interface ProfilerAwareInterface
{
    /**
     * Sets the profiler instance.
     *
     * @param ProfilerInterface|null $profiler The profiler instance.
     */
    public function setProfiler(ProfilerInterface|null $profiler): void;
}
