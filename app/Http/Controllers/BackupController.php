<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\HouseholdBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use JsonException;

class BackupController extends Controller
{
    public function __construct(
        private readonly HouseholdBackupService $backupService
    ) {
    }

    public function index(): View
    {
        return view('backup.index');
    }

    public function download(Request $request): Response
    {
        $backup = $this->backupService->export(
            $request->user()
        );

        try {
            $json = json_encode(
                $backup,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            abort(500, 'バックアップファイルの作成に失敗しました。');
        }

        $filename = sprintf(
            'household-backup-%s.json',
            now()->format('Ymd-His')
        );

        return response(
            $json,
            200,
            [
                'Content-Type'
                    => 'application/json; charset=UTF-8',
                'Content-Disposition'
                    => 'attachment; filename="'.$filename.'"',
                'Cache-Control'
                    => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    public function restore(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'backup_file' => [
                'required',
                'file',
                'max:10240',
            ],
            'confirm_restore' => [
                'accepted',
            ],
        ], [
            'backup_file.required'
                => 'バックアップファイルを選択してください。',
            'backup_file.file'
                => 'バックアップファイルを選択してください。',
            'backup_file.max'
                => 'バックアップファイルは10MB以下にしてください。',
            'confirm_restore.accepted'
                => '復元内容を確認してチェックを入れてください。',
        ]);

        $json = file_get_contents(
            $validated['backup_file']->getRealPath()
        );

        if ($json === false) {
            return back()
                ->withErrors([
                    'backup_file'
                        => 'バックアップファイルを読み込めませんでした。',
                ]);
        }

        $backup = $this->backupService->decode($json);

        $this->backupService->restore(
            $request->user(),
            $backup
        );

        return redirect()
            ->route('backup.index')
            ->with(
                'success',
                'バックアップからデータを復元しました。'
            );
    }
}