<?php

}
    }
        return $this->resolve($strategy, $options)->resolveFilename($date, $channel);
    ): string {
        array $options = []
        string $channel,
        \DateTimeInterface $date,
        string $strategy,
    public function resolveFilename(
     */
     * Resolve filename for a given date and channel.
    /**

    }
        return $this->instances[$cacheKey] = new $class(...array_values($options));
        $class = $this->strategies[$strategy];

        }
            throw new InvalidArgumentException("Unknown naming strategy: {$strategy}");
        if (!isset($this->strategies[$strategy])) {
        // Handle named strategy

        }
            return $this->instances[$cacheKey] = new $strategy(...array_values($options));
        if (class_exists($strategy)) {
        // Handle class-based strategy

        }
            return $this->instances[$cacheKey] = new CustomPatternNamingStrategy($options['pattern']);
        if ($strategy === 'custom' && isset($options['pattern'])) {
        // Handle custom pattern

        }
            return $this->instances[$cacheKey];
        if (isset($this->instances[$cacheKey])) {

        $cacheKey = $strategy . serialize($options);
    {
    public function resolve(string $strategy, array $options = []): NamingStrategyInterface
     */
     * Resolve a strategy instance.
    /**

    }
        $this->strategies[$name] = $class;

        }
            );
                "Strategy class must implement NamingStrategyInterface"
            throw new InvalidArgumentException(
        if (!is_subclass_of($class, NamingStrategyInterface::class)) {
    {
    public function register(string $name, string $class): void
     */
     * Register a custom strategy.
    /**

    }
        $this->strategies['monthly'] = MonthlyNamingStrategy::class;
        $this->strategies['weekly'] = WeeklyNamingStrategy::class;
        $this->strategies['daily'] = DailyNamingStrategy::class;
    {
    protected function registerDefaultStrategies(): void

    }
        $this->registerDefaultStrategies();
    {
    public function __construct()

    protected array $instances = [];
    protected array $strategies = [];
{
class StrategyManager

use InvalidArgumentException;
use Willypelz\LogPlatform\Strategies\CustomPatternNamingStrategy;
use Willypelz\LogPlatform\Strategies\MonthlyNamingStrategy;
use Willypelz\LogPlatform\Strategies\WeeklyNamingStrategy;
use Willypelz\LogPlatform\Strategies\DailyNamingStrategy;
use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;

namespace Willypelz\LogPlatform\Services;

