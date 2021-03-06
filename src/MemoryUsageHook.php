<?php

declare(strict_types=1);

namespace MinistryOfJustice\PHPUnit;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;

class MemoryUsageHook implements BeforeTestHook, AfterTestHook, AfterLastTestHook
{
    private int $previousUsage = 0;
    private array $results = [];

    public function __construct(
        private int $memoryThresholdBytes = 10000000,
        private bool $displayRunningTotal = false
    ) {
    }

    public function executeBeforeTest(string $test): void
    {
        $this->previousUsage = memory_get_usage();
    }

    public function executeAfterTest(string $test, float $time): void
    {
        $currentUsage = memory_get_usage();
        $difference = $currentUsage - $this->previousUsage;

        if ($difference > $this->memoryThresholdBytes) {
            $this->results[] = ['name' => $test, 'usage' => $difference];
        }

        if ($this->displayRunningTotal) {
            $currentString = MemoryStringFormatter::roundToStandardUnits($currentUsage);
            $differenceString = MemoryStringFormatter::roundToStandardUnits($difference);
            print("Test '$test' ended. Current: $currentString, Difference: $differenceString\n");
        }
    }

    public function executeAfterLastTest(): void
    {
        if (empty($this->results)) {
            print("\nAll tests were under the memory usage threshold. Congratulations!\n");
            return;
        }

        print("\nHigh memory usage tests\n");

        $usageColumn = array_column($this->results, 'usage');
        array_multisort($usageColumn, SORT_DESC, $this->results);

        foreach ($this->results as $result) {
            $differenceString = MemoryStringFormatter::roundToStandardUnits($result['usage']);
            print("Test '${result['name']}' used: $differenceString\n");
        }
    }
}
