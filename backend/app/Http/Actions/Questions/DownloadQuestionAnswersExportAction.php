<?php

namespace HiEvents\Http\Actions\Questions;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\ResponseCodes;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reached via a short-lived Laravel signed URL (see JobPollingService), not
 * auth:api — the signature itself, scoped to this exact event/job and expiring
 * in minutes, is the authorization. This lets the browser download the file
 * with a plain link click, which can't carry the app's normal JWT auth header,
 * while working the same way regardless of which filesystem disk is
 * configured (local for a self-hosted deployment, or s3 if ever configured).
 */
class DownloadQuestionAnswersExportAction extends BaseAction
{
    public function __invoke(int $eventId, string $jobUuid): Response
    {
        $disk = Storage::disk(config('filesystems.private'));
        $filePath = "event_$eventId/answers-$jobUuid.xlsx";

        if (!$disk->exists($filePath)) {
            return $this->errorResponse(
                message: __('Export file not found'),
                statusCode: ResponseCodes::HTTP_NOT_FOUND,
            );
        }

        return $disk->download($filePath, "event_{$eventId}_question_answers.xlsx");
    }
}
