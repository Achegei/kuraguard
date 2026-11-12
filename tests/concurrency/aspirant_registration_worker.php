<?php

declare(strict_types=1);

use App\Enums\ElectoralOffice;
use App\Services\AspirantOnboardingService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$userId = (int) ($argv[1] ?? 0);
$datasetId = (int) ($argv[2] ?? 0);
$startAt = (float) ($argv[3] ?? 0);

if ($userId <= 0 || $datasetId <= 0 || $startAt <= 0) {
    fwrite(STDERR, "Invalid worker arguments.\n");
    exit(2);
}

while (microtime(true) < $startAt) {
    usleep(1000);
}

try {
    $contestId = app(AspirantOnboardingService::class)
        ->createContest(
            $userId,
            'Concurrent Registration Test',
            2027,
            ElectoralOffice::PRESIDENT,
            datasetId: $datasetId
        );

    echo json_encode([
        'status' => 'success',
        'contest_id' => $contestId,
    ], JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $exception) {
    echo json_encode([
        'status' => 'exception',
        'type' => get_class($exception),
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR) . PHP_EOL;

    exit(1);
}
