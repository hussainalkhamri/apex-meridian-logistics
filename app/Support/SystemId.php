<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SystemId
{
    public static function make(string $modelClass, string $prefix, ?int $year = null, int $digits = 5): string
    {
        $year ??= (int) now()->format('Y');
        $minimum = 10 ** ($digits - 1);
        $maximum = (10 ** $digits) - 1;

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $id = sprintf('%s-%d-%0' . $digits . 'd', $prefix, $year, random_int($minimum, $maximum));

            if (! $modelClass::whereKey($id)->exists()) {
                return $id;
            }
        }

        throw new RuntimeException("Unable to generate a unique {$prefix} identifier.");
    }

    public static function makeSequential(string $modelClass, string $prefix, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($modelClass, $prefix, $year): string {
                    $sequence = DB::table('system_sequences')
                        ->where('prefix', $prefix)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();

                    $nextNumber = $sequence
                        ? max(1, (int) $sequence->next_number)
                        : self::firstAvailableNumber($modelClass, $prefix, $year);

                    for ($numberAttempt = 0; $numberAttempt < 100000; $numberAttempt++) {
                        $id = sprintf('%s-%d-%d', $prefix, $year, $nextNumber);

                        if (! $modelClass::whereKey($id)->exists()) {
                            DB::table('system_sequences')->updateOrInsert(
                                [
                                    'prefix' => $prefix,
                                    'year' => $year,
                                ],
                                [
                                    'next_number' => $nextNumber + 1,
                                    'updated_at' => now(),
                                    'created_at' => $sequence?->created_at ?? now(),
                                ]
                            );

                            return $id;
                        }

                        $nextNumber++;
                    }

                    throw new RuntimeException("Unable to generate a sequential {$prefix} identifier.");
                });
            } catch (QueryException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException("Unable to generate a sequential {$prefix} identifier.");
    }

    private static function firstAvailableNumber(string $modelClass, string $prefix, int $year): int
    {
        $model = new $modelClass();
        $keyName = $model->getKeyName();
        $prefixPattern = $prefix . '-' . $year . '-%';
        $usedNumbers = [];

        $modelClass::query()
            ->where($keyName, 'like', $prefixPattern)
            ->pluck($keyName)
            ->each(function ($id) use (&$usedNumbers): void {
                $suffix = strrchr((string) $id, '-');
                $suffix = $suffix === false ? '' : ltrim($suffix, '-');

                if ($suffix !== '' && ctype_digit($suffix)) {
                    $usedNumbers[(int) $suffix] = true;
                }
            });

        $nextNumber = 1;
        while (isset($usedNumbers[$nextNumber])) {
            $nextNumber++;
        }

        return $nextNumber;
    }
}
