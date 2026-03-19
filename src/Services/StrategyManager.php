<?php

namespace Willypelz\LogPlatform\Services;

use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;
use Willypelz\LogPlatform\Strategies\DailyNamingStrategy;
use Willypelz\LogPlatform\Strategies\WeeklyNamingStrategy;
use Willypelz\LogPlatform\Strategies\MonthlyNamingStrategy;
use Willypelz\LogPlatform\Strategies\CustomPatternNamingStrategy;
use InvalidArgumentException;

class StrategyManager
{
    protected array $strategies = [];
    protected array $instances = [];

    public function __construct()
    {
        $this->registerDefaultStrategies();
    }

    protected function registerDefaultStrategies(): void
    {
        $this->strategies['daily'] = DailyNamingStrategy::class;
        $this->strategies['weekly'] = WeeklyNamingStrategy::class;
        $this->strategies['monthly'] = MonthlyNamingStrategy::class;
    }

    /**
     * Register a custom strategy.
     */
    public function register(string $name, string $class): void
    {
        if (!is_subclass_of($class, NamingStrategyInterface::class)) {
            throw new InvalidArgumentException(
                "Strategy class must implement NamingStrategyInterface"
            );
        }

        $this->strategies[$name] = $class;
    }

    /**
     * Resolve a strategy instance.
     */
    public function resolve(string $strategy, array $options = []): NamingStrategyInterface
    {
        $cacheKey = $strategy . serialize($options);

        if (isset($this->instances[$cacheKey])) {
            return $this->instances[$cacheKey];
        }

        // Handle custom pattern
        if ($strategy === 'custom' && isset($options['pattern'])) {
            return $this->instances[$cacheKey] = new CustomPatternNamingStrategy($options['pattern']);
        }

        // Handle class-based strategy
        if (class_exists($strategy)) {
            return $this->instances[$cacheKey] = new $strategy(...array_values($options));
        }

        // Handle named strategy
        if (!isset($this->strategies[$strategy])) {
            throw new InvalidArgumentException("Unknown naming strategy: {$strategy}");
        }

        $class = $this->strategies[$strategy];
        return $this->instances[$cacheKey] = new $class(...array_values($options));
    }

    /**
     * Resolve filename for a given date and channel.
     */
    public function resolveFilename(
        string $strategy,
        \DateTimeInterface $date,
        string $channel,
        array $options = []
    ): string {
        return $this->resolve($strategy, $options)->resolveFilename($date, $channel);
    }
}
