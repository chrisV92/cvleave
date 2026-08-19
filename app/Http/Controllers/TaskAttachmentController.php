<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves task attachments.
 *
 * Files live on the private disk and are handed out only through here. Putting
 * them on the public disk with a storage link would make every attachment
 * readable by anyone holding the URL — and URLs get forwarded. That is the
 * same hole that had to be closed in the leave report routes.
 */
class TaskAttachmentController extends Controller
{
    public function show(Request $request, TaskAttachment $attachment): Response
    {
        $viewer = $request->user();
        $task = $attachment->task;

        abort_unless($viewer && $task, 404);

        // Permissions are stored per company, so the check has to be made in
        // the company that owns the file — not whichever one the session
        // happens to have been left in.
        app(PermissionRegistrar::class)->setPermissionsTeamId($viewer->tenant_id);

        abort_unless(
            $viewer->tenant_id === $task->tenant_id && $viewer->can(Permissions::TASKS_VIEW),
            403,
        );

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        // Images open in the browser; everything else downloads. Both go
        // through this same check either way.
        return $attachment->isImage()
            ? $disk->response($attachment->path, $attachment->original_name)
            : $disk->download($attachment->path, $attachment->original_name);
    }
}
