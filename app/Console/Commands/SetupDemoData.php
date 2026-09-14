<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DemoDataService;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class SetupDemoData extends Command
{
    protected $signature = 'demo:setup';

    protected $description =
        'デモユーザーとデモ用家計簿データを作成する';

    public function handle(
        DemoDataService $demoDataService
    ): int {
        $this->info(
            'デモデータを作成します。'
        );

        try {
            $user = $demoDataService->setup();
        } catch (RuntimeException $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);

            $this->error(
                'デモデータの作成に失敗しました。'
            );

            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            'デモデータを作成しました。'
        );

        $this->table(
            [
                '項目',
                '内容',
            ],
            [
                [
                    'ユーザーID',
                    (string) $user->id,
                ],
                [
                    '名前',
                    $user->name,
                ],
                [
                    'メールアドレス',
                    $user->email,
                ],
                [
                    'パスワード',
                    'password',
                ],
            ]
        );

        return self::SUCCESS;
    }
}