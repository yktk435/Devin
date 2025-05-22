<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedmineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SetupRedmineData extends Command
{
    /**
     * コマンドの名前と説明
     */
    protected $signature = 'redmine:setup {--months=3} {--start_date=} {--end_date=} {--project=}';
    protected $description = 'Redmineから過去数ヶ月分のデータを取得してデータベースに初期設定します';

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
        $this->info('Redmineからの初期データ取得を開始します...');
        
        if ($this->option('start_date') && $this->option('end_date')) {
            try {
                $startDate = $this->option('start_date');
                $endDate = $this->option('end_date');
                
                Carbon::createFromFormat('Y-m-d', $startDate);
                Carbon::createFromFormat('Y-m-d', $endDate);
                
                $this->info("期間: {$startDate} から {$endDate} のデータを取得します");
                
                try {
                    $this->redmineService->getIndividualProgressStats($startDate, $endDate, $this->option('project'), true);
                    $this->info("期間: {$startDate} から {$endDate} のデータを取得しました");
                    return 0;
                } catch (\Exception $e) {
                    $this->error("期間: {$startDate} から {$endDate} のデータ取得中にエラーが発生しました: " . $e->getMessage());
                    Log::error('Redmine初期データ取得中のエラー', [
                        'period' => "{$startDate} to {$endDate}",
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error('日付の形式が不正です。YYYY-MM-DD形式で指定してください。');
                return 1;
            }
        }
        
        $months = (int)$this->option('months');
        if ($months <= 0) {
            $months = 3;
        }
        
        $projectId = $this->option('project');
        
        $this->info("過去{$months}ヶ月分のデータを取得します");
        
        $now = Carbon::now();
        $successCount = 0;
        $errorCount = 0;
        
        $this->output->progressStart($months);
        
        for ($i = 0; $i < $months; $i++) {
            $targetDate = $now->copy()->subMonths($i);
            $startDate = $targetDate->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $targetDate->copy()->endOfMonth()->format('Y-m-d');
            
            $this->output->progressAdvance();
            
            try {
                $this->info("期間: {$startDate} から {$endDate} のデータを取得中...");
                $this->redmineService->getIndividualProgressStats($startDate, $endDate, $projectId, true);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("期間: {$startDate} から {$endDate} のデータ取得中にエラーが発生しました: " . $e->getMessage());
                Log::error('Redmine初期データ取得中のエラー', [
                    'period' => "{$startDate} to {$endDate}",
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
            
            sleep(2);
        }
        
        $this->output->progressFinish();
        
        $this->info("データ取得完了: 成功 {$successCount}件、エラー {$errorCount}件");
        
        return $errorCount > 0 ? 1 : 0;
    }
}
