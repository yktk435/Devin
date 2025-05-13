<?php

namespace App\Repositories;

use App\Interfaces\RedmineAPIClientInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RedmineAPIClientの実装
 * 
 * このクラスはRedmineAPIClientInterfaceを実装し、Redmine APIと対話します。
 * 設定は.envファイルから読み込まれます。
 */
class RedmineAPIClient implements RedmineAPIClientInterface
{
    protected $apiUrl;
    protected $apiKey;
    protected $isConfigured;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->apiUrl = env('REDMINE_API_URL');
        $this->apiKey = env('REDMINE_API_KEY');
        $this->isConfigured = !empty($this->apiUrl) && !empty($this->apiKey);
        
        if (!$this->isConfigured) {
            Log::warning('RedmineAPIClientが正しく設定されていません。.envファイルのREDMINE_API_URLとREDMINE_API_KEYを確認してください。');
        }
    }

    /**
     * Redmine APIにリクエストを送信
     *
     * @param string $endpoint
     * @param array $params
     * @return array|null
     */
    protected function makeApiRequest($endpoint, array $params = [])
    {
        if (!$this->isConfigured) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiUrl . $endpoint, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Redmine APIリクエストが失敗しました', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Redmine APIリクエスト中に例外が発生しました', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Redmine APIから日次統計を取得
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array|null
     */
    public function getDailyStats($startDate, $endDate, $projectId = null)
    {
        $params = [
            'status_id' => '*',
            'created_on' => urlencode('><') . $startDate . '|' . $endDate,
            'limit' => 100
        ];
        
        if ($projectId) {
            $params['project_id'] = $projectId;
        }
        
        $response = $this->makeApiRequest('/issues.json', $params);
        
        if (!$response || !isset($response['issues'])) {
            return null;
        }
        
        $dailyStats = [];
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        
        for ($date = $startCarbon; $date->lte($endCarbon); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $dailyStats[$dateStr] = [
                'date' => $dateStr,
                'completed' => 0,
                'incomplete' => 0
            ];
        }
        
        foreach ($response['issues'] as $issue) {
            $createdDate = substr($issue['created_on'], 0, 10);
            
            if (isset($dailyStats[$createdDate])) {
                if ($issue['status']['name'] === 'Closed' || $issue['status']['name'] === '完了') {
                    $dailyStats[$createdDate]['completed']++;
                } else {
                    $dailyStats[$createdDate]['incomplete']++;
                }
            }
        }
        
        return array_values($dailyStats);
    }

    /**
     * Redmine APIから月次統計を取得
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array|null
     */
    public function getMonthlyStats($startDate, $endDate, $projectId = null)
    {
        $params = [
            'status_id' => '*',
            'created_on' => urlencode('><') . $startDate . '|' . $endDate,
            'limit' => 100
        ];
        
        if ($projectId) {
            $params['project_id'] = $projectId;
        }
        
        $response = $this->makeApiRequest('/issues.json', $params);
        
        if (!$response || !isset($response['issues'])) {
            return null;
        }
        
        $monthlyStats = [];
        $startCarbon = Carbon::parse($startDate)->startOfMonth();
        $endCarbon = Carbon::parse($endDate)->endOfMonth();
        
        for ($date = $startCarbon; $date->lte($endCarbon); $date->addMonth()) {
            $monthStr = $date->format('Y-m');
            $monthlyStats[$monthStr] = [
                'month' => $monthStr,
                'completed' => 0,
                'incomplete' => 0
            ];
        }
        
        foreach ($response['issues'] as $issue) {
            $createdMonth = substr($issue['created_on'], 0, 7);
            
            if (isset($monthlyStats[$createdMonth])) {
                if ($issue['status']['name'] === 'Closed' || $issue['status']['name'] === '完了') {
                    $monthlyStats[$createdMonth]['completed']++;
                } else {
                    $monthlyStats[$createdMonth]['incomplete']++;
                }
            }
        }
        
        return array_values($monthlyStats);
    }

    /**
     * Redmine APIから進捗率統計を取得
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array|null
     */
    public function getProgressRateStats($startDate, $endDate, $projectId = null)
    {
        $params = [
            'status_id' => '*',
            'created_on' => urlencode('><') . $startDate . '|' . $endDate,
            'include' => 'journals',
            'limit' => 100
        ];
        
        if ($projectId) {
            $params['project_id'] = $projectId;
        }
        
        $response = $this->makeApiRequest('/issues.json', $params);
        
        if (!$response || !isset($response['issues'])) {
            return null;
        }
        
        
        return null;
    }
    
    /**
     * Redmine APIから個人消費率統計を取得
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array|null
     */
    public function getIndividualConsumptionStats($startDate, $endDate, $projectId = null)
    {
        // まずデータベースから時間エントリを取得
        $dbTimeEntries = $this->getTimeEntriesFromDatabase($startDate, $endDate, $projectId);
        
        // データベースにエントリがある場合はそれを使用
        if (!empty($dbTimeEntries)) {
            Log::info("データベースから{$startDate}から{$endDate}の期間の" . count($dbTimeEntries) . "件の時間エントリを取得しました");
            $allTimeEntries = $dbTimeEntries;
        } else {
            // データベースにエントリがない場合はAPIから取得
            Log::info("データベースに時間エントリが見つかりませんでした。APIから{$startDate}から{$endDate}の期間のデータを取得します");
            
            $timeEntriesParams = [
                'spent_on' => urlencode('><') . $startDate . '|' . $endDate,
                'limit' => 100,
                'offset' => 0
            ];
            
            if ($projectId) {
                $timeEntriesParams['project_id'] = $projectId;
            }
            
            $allTimeEntries = [];
            $totalCount = 0;
            $currentOffset = 0;
            
            do {
                $timeEntriesParams['offset'] = $currentOffset;
                
                $timeEntriesResponse = $this->makeApiRequest('/time_entries.json', $timeEntriesParams);
                
                if (!$timeEntriesResponse || !isset($timeEntriesResponse['time_entries'])) {
                    Log::warning('オフセット' . $currentOffset . 'での時間エントリの取得に失敗しました');
                    break;
                }
                
                $currentEntries = $timeEntriesResponse['time_entries'];
                $entriesCount = count($currentEntries);
                
                if ($entriesCount === 0) {
                    break;
                }
                
                $allTimeEntries = array_merge($allTimeEntries, $currentEntries);
                
                $totalCount += $entriesCount;
                $currentOffset += $timeEntriesParams['limit'];
                
                $totalAvailable = isset($timeEntriesResponse['total_count']) ? $timeEntriesResponse['total_count'] : 0;
                
                Log::info("{$entriesCount}件の時間エントリを取得しました（オフセット: {$timeEntriesParams['offset']}, 合計: {$totalCount}, 利用可能な合計: {$totalAvailable}）");
                
            } while ($entriesCount === $timeEntriesParams['limit']); // フルページを取得した場合は続行
            
            if (empty($allTimeEntries)) {
                Log::warning('指定された日付範囲の時間エントリが見つかりませんでした');
                return null;
            }
            
            Log::info("ページネーション後、合計" . count($allTimeEntries) . "件の時間エントリを取得しました");
        }
        
        // 時間エントリをデータベースに保存
        foreach ($allTimeEntries as $entry) {
            try {
                // エントリがすでにデータベースに存在するか確認
                $existingEntry = \App\Models\TimeEntry::where('redmine_id', $entry['id'])->first();
                
                if (!$existingEntry) {
                    // 存在しない場合は新しいエントリを作成
                    \App\Models\TimeEntry::create([
                        'redmine_id' => $entry['id'],
                        'user_id' => $entry['user']['id'],
                        'user_name' => $entry['user']['name'],
                        'issue_id' => $entry['issue']['id'],
                        'issue_subject' => $entry['issue']['subject'] ?? null,
                        'hours' => $entry['hours'],
                        'spent_on' => $entry['spent_on'],
                        'comments' => $entry['comments'] ?? null,
                    ]);
                }
                
                // ユーザー情報をデータベースに保存
                $this->saveUserToDatabase($entry['user']['id'], $entry['user']['name']);
                
            } catch (\Exception $e) {
                Log::error('時間エントリのデータベースへの保存に失敗しました', [
                    'entry_id' => $entry['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $userTimeEntries = [];
        $issueIds = [];
        
        foreach ($allTimeEntries as $entry) {
            $userId = $entry['user']['id'];
            $userName = $entry['user']['name'];
            $issueId = $entry['issue']['id'];
            $hours = $entry['hours'];
            
            if (!isset($userTimeEntries[$userId])) {
                $userTimeEntries[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'working_hours' => 0,
                    'issues' => []
                ];
            }
            
            $userTimeEntries[$userId]['working_hours'] += $hours;
            
            if (!isset($userTimeEntries[$userId]['issues'][$issueId])) {
                $userTimeEntries[$userId]['issues'][$issueId] = [
                    'spent_hours' => 0
                ];
            }
            
            $userTimeEntries[$userId]['issues'][$issueId]['spent_hours'] += $hours;
            $issueIds[] = $issueId;
        }
        
        $uniqueIssueIds = array_unique($issueIds);
        $issueDetails = [];
        
        $issueBatches = array_chunk($uniqueIssueIds, 20);
        
        foreach ($issueBatches as $batch) {
            $issuesParams = [
                'issue_id' => implode(',', $batch),
                'status_id' => '*',
                'include' => 'relations'
            ];
            
            $issuesResponse = $this->makeApiRequest('/issues.json', $issuesParams);
            
            if ($issuesResponse && isset($issuesResponse['issues'])) {
                foreach ($issuesResponse['issues'] as $issue) {
                    Log::info("チケット #{$issue['id']} のステータス: {$issue['status']['name']}");
                    
                    $completedStatuses = ['Closed', '完了', 'Resolved', '解決', 'Done', 'Fixed', '修正済み', 'Feedback', 'フィードバック'];
                    $isCompleted = in_array($issue['status']['name'], $completedStatuses);
                    
                    $issueDetails[$issue['id']] = [
                        'id' => $issue['id'],
                        'subject' => $issue['subject'],
                        'status' => $issue['status']['name'],
                        'is_completed' => $isCompleted,
                        'estimated_hours' => isset($issue['estimated_hours']) ? $issue['estimated_hours'] : 0
                    ];
                }
            }
        }
        
        $consumptionStats = [];
        
        foreach ($userTimeEntries as $userId => $userData) {
            $totalTickets = 0;
            $completedTickets = 0;
            $consumedTickets = 0;
            $consumedEstimatedHours = 0;
            
            foreach ($userData['issues'] as $issueId => $issueData) {
                if (isset($issueDetails[$issueId])) {
                    $totalTickets++;
                    $issue = $issueDetails[$issueId];
                    
                    if ($issue['is_completed']) {
                        $completedTickets++;
                        
                        if ($issue['estimated_hours'] > 0 && $issueData['spent_hours'] <= $issue['estimated_hours']) {
                            $consumedTickets++;
                            $consumedEstimatedHours += $issue['estimated_hours'];
                        }
                    }
                }
            }
            
            $workingHours = $userData['working_hours'];
            $achievementRate = ($workingHours > 0) ? round(($consumedEstimatedHours / $workingHours) * 100) : 0;
            $ticketConsumptionRate = ($totalTickets > 0) ? round(($consumedTickets / $totalTickets) * 100) : 0;
            
            $consumptionStats[] = [
                'user_id' => $userData['user_id'],
                'user_name' => $userData['user_name'],
                'consumed_estimated_hours' => $consumedEstimatedHours, // 消化時間（消化したチケットの予定工数）
                'working_hours' => $workingHours, // 稼働時間
                'achievement_rate' => $achievementRate, // 達成率
                'total_tickets' => $totalTickets, // 総チケット数
                'completed_tickets' => $completedTickets, // 完了チケット数
                'consumed_tickets' => $consumedTickets, // 消化チケット数（完了かつ予定工数以内）
                'ticket_consumption_rate' => $ticketConsumptionRate // チケット消化率
            ];
        }
        
        return $consumptionStats;
    }
    
    /**
     * データベースから時間エントリを取得
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    protected function getTimeEntriesFromDatabase($startDate, $endDate, $projectId = null)
    {
        try {
            $query = \App\Models\TimeEntry::whereBetween('spent_on', [$startDate, $endDate]);
            
            if ($projectId) {
                // 注意: これはデータベース内の時間エントリがプロジェクトにリンクされていることを前提としています
                // そうでない場合は、実際のデータモデルに基づいて調整する必要があります
                $query->where('issue_id', 'LIKE', $projectId . '-%');
            }
            
            $entries = $query->get();
            
            if ($entries->isEmpty()) {
                return [];
            }
            
            // データベースエントリをAPI形式に変換
            $formattedEntries = [];
            foreach ($entries as $entry) {
                $formattedEntries[] = [
                    'id' => $entry->redmine_id,
                    'user' => [
                        'id' => $entry->user_id,
                        'name' => $entry->user_name
                    ],
                    'issue' => [
                        'id' => $entry->issue_id,
                        'subject' => $entry->issue_subject
                    ],
                    'hours' => $entry->hours,
                    'spent_on' => $entry->spent_on->format('Y-m-d'),
                    'comments' => $entry->comments
                ];
            }
            
            return $formattedEntries;
        } catch (\Exception $e) {
            Log::error('データベースからの時間エントリの取得中にエラーが発生しました', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Redmine APIから利用可能なプロジェクトを取得
     * 
     * @return array|null
     */
    public function getProjects()
    {
        $params = [
            'limit' => 100
        ];
        
        $response = $this->makeApiRequest('/projects.json', $params);
        
        if (!$response || !isset($response['projects'])) {
            Log::warning('Redmine APIからプロジェクトの取得に失敗しました');
            return null;
        }
        
        $projects = [];
        
        foreach ($response['projects'] as $project) {
            $projects[] = [
                'id' => $project['id'],
                'name' => $project['name'],
                'identifier' => $project['identifier'],
                'description' => isset($project['description']) ? $project['description'] : ''
            ];
        }
        
        return $projects;
    }
    
    /**
     * ユーザー情報をデータベースに保存
     * 
     * @param int $redmineId
     * @param string $name
     * @return \App\Models\RedmineUser
     */
    protected function saveUserToDatabase($redmineId, $name)
    {
        try {
            Log::info("ユーザー情報をデータベースに保存しようとしています: {$name} (ID: {$redmineId})");
            
            // ユーザーがすでにデータベースに存在するか確認
            $user = \App\Models\RedmineUser::where('redmine_id', $redmineId)->first();
            
            if (!$user) {
                // 存在しない場合は新しいユーザーを作成
                Log::info("新しいユーザーを作成します: {$name} (ID: {$redmineId})");
                
                $user = new \App\Models\RedmineUser();
                $user->redmine_id = $redmineId;
                $user->name = $name;
                $user->save();
                
                Log::info("新しいユーザーをデータベースに保存しました: {$name} (ID: {$redmineId})");
            } else if ($user->name !== $name) {
                $user->name = $name;
                $user->save();
                Log::info("ユーザー情報を更新しました: {$name} (ID: {$redmineId})");
            } else {
                Log::info("ユーザーはすでに存在し、更新の必要はありません: {$name} (ID: {$redmineId})");
            }
            
            $checkUser = \App\Models\RedmineUser::where('redmine_id', $redmineId)->first();
            if ($checkUser) {
                Log::info("ユーザーが正常にデータベースに保存されました: {$checkUser->name} (ID: {$checkUser->redmine_id})");
            } else {
                Log::warning("ユーザーの保存を確認できませんでした: {$name} (ID: {$redmineId})");
            }
            
            return $user;
        } catch (\Exception $e) {
            Log::error('ユーザー情報のデータベースへの保存に失敗しました', [
                'redmine_id' => $redmineId,
                'name' => $name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
