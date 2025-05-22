<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedmineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FetchRedmineData extends Command
{
    /**
     * コマンドの名前と説明
     */
    protected $signature = 'redmine:fetch-data {--month=current} {--start_date=} {--end_date=} {--project=} {--user_id=} {--delay=2}';
    protected $description = 'Redmineからデータを取得してデータベースに保存します。ユーザーIDを指定すると特定ユーザーのデータのみを取得します。';

    private $redmineService;

    /**
     * コンストラクタ
     */
    public function __construct(RedmineService $redmineService)
    {
        parent::__construct();
        $this->redmineService = $redmineService;
    }

    /**
     * コマンドの実行
     */
    public function handle()
    {
        $this->info('Redmineからデータの取得を開始します...');
        
        $startDate = null;
        $endDate = null;
        
        if ($this->option('start_date') && $this->option('end_date')) {
            try {
                $startDate = $this->option('start_date');
                $endDate = $this->option('end_date');
                
                Carbon::createFromFormat('Y-m-d', $startDate);
                Carbon::createFromFormat('Y-m-d', $endDate);
            } catch (\Exception $e) {
                $this->error('日付の形式が不正です。YYYY-MM-DD形式で指定してください。');
                return 1;
            }
        } else {
            $monthOption = $this->option('month');
            
            if ($monthOption === 'current') {
                $date = Carbon::now();
            } elseif ($monthOption === 'previous') {
                $date = Carbon::now()->subMonth();
            } else {
                try {
                    $date = Carbon::createFromFormat('Y-m', $monthOption);
                } catch (\Exception $e) {
                    $this->error('月の形式が不正です。YYYY-MM形式、current、またはpreviousを指定してください。');
                    return 1;
                }
            }
            
            $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
        }
        
        $projectId = $this->option('project');
        $userId = $this->option('user_id');
        $delay = (int)$this->option('delay');
        
        if ($userId) {
            $this->info("ユーザーID: {$userId} の期間: {$startDate} から {$endDate} のデータを取得します");
            
            try {
                $this->info("APIリクエスト間の遅延: {$delay}秒");
                
                $stats = $this->redmineService->getUserTicketDetails($userId, $startDate, $endDate, $projectId, true);
                
                sleep($delay); // APIリクエスト間の遅延
                
                $this->info("ユーザーID: {$userId} のデータの取得と保存が完了しました");
                return 0;
            } catch (\Exception $e) {
                $this->error('データの取得中にエラーが発生しました: ' . $e->getMessage());
                Log::error('Redmineデータ取得中のエラー', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return 1;
            }
        } else {
            $this->info("期間: {$startDate} から {$endDate} の全ユーザーデータを取得します");
            
            try {
                $this->info("APIリクエスト間の遅延: {$delay}秒");
                
                $stats = $this->redmineService->getIndividualProgressStats($startDate, $endDate, $projectId, true);
                
                $this->info('データの取得と保存が完了しました');
                return 0;
            } catch (\Exception $e) {
                $this->error('データの取得中にエラーが発生しました: ' . $e->getMessage());
                Log::error('Redmineデータ取得中のエラー', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return 1;
            }
        }
    }
}
