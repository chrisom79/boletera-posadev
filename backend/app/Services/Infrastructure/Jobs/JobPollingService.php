<?php

namespace HiEvents\Services\Infrastructure\Jobs;

use HiEvents\Helper\Url;
use HiEvents\Services\Infrastructure\Jobs\DTO\JobPollingResultDTO;
use HiEvents\Services\Infrastructure\Jobs\Enum\JobStatusEnum;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL as LaravelUrl;

class JobPollingService
{
    public function startJob(string $jobName, array $jobs): JobPollingResultDTO
    {
        $batch = Bus::batch($jobs)
            ->name($jobName)
            ->dispatch();

        return new JobPollingResultDTO(
            status: JobStatusEnum::IN_PROGRESS,
            message: 'Job started successfully',
            jobUuid: $batch->id,
        );
    }

    /**
     * @param string|null $filePath Path (on config('filesystems.private')) to confirm exists once
     *                              the batch finishes.
     * @param string|null $downloadRoute Named route to sign and return as downloadUrl once finished,
     *                                   given $downloadRouteParams. Required if $filePath is given.
     */
    public function checkJobStatus(
        string  $jobUuid,
        ?string $filePath = null,
        ?string $downloadRoute = null,
        array   $downloadRouteParams = [],
    ): JobPollingResultDTO
    {
        $batch = Bus::findBatch($jobUuid);

        if (!$batch) {
            return new JobPollingResultDTO(
                status: JobStatusEnum::NOT_FOUND,
                message: __('Job not found'),
                jobUuid: $jobUuid,
            );
        }

        if ($batch->finished()) {
            if ($filePath && !Storage::disk(config('filesystems.private'))->exists($filePath)) {
                return new JobPollingResultDTO(
                    status: JobStatusEnum::NOT_FOUND,
                    message: __('Export file not found'),
                    jobUuid: $jobUuid,
                );
            }

            return new JobPollingResultDTO(
                status: JobStatusEnum::FINISHED,
                message: __('Job completed successfully'),
                jobUuid: $jobUuid,
                // Laravel's own route/domain resolution can't be trusted here: nginx
                // strips the "/api" prefix before routes are ever matched (so route
                // definitions have no "/api" in them), and APP_URL is unset in this
                // deployment. Sign a relative path, then rebuild the public URL via
                // Url::getApiUrl(), the same helper used for every other API link.
                downloadUrl: $filePath && $downloadRoute
                    ? Url::getApiUrl(LaravelUrl::temporarySignedRoute(
                        $downloadRoute,
                        now()->addMinutes(10),
                        $downloadRouteParams,
                        absolute: false,
                    ))
                    : null,
            );
        }

        return new JobPollingResultDTO(
            status: JobStatusEnum::IN_PROGRESS,
            message: __('Job is still in progress'),
            jobUuid: $jobUuid,
        );
    }
}
