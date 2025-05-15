<?php

namespace App\Repositories;

use App\Interfaces\RedmineAPIClientInterface;
use App\Models\RedmineStatus;
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

        $this->initializeStatuses();

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
     * Redmine APIから個人進捗率統計を取得
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array|null
     */
    public function getIndividualProgressStats($startDate, $endDate, $projectId = null)
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

        $dateObj = Carbon::parse($startDate);
        $monthStart = $dateObj->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $dateObj->copy()->endOfMonth()->format('Y-m-d');
        
        Log::info("期限が{$monthStart}から{$monthEnd}の期間のチケットを取得します");
        
        $dueTicketsParams = [
            'due_date' => urlencode('><') . $monthStart . '|' . $monthEnd,
            'assigned_to_id' => '*', // 担当者が設定されているチケットのみ
            'status_id' => '*',
            'limit' => 100,
            'offset' => 0
        ];
        
        if ($projectId) {
            $dueTicketsParams['project_id'] = $projectId;
        }
        
        $dueTickets = [];
        $totalDueCount = 0;
        $currentDueOffset = 0;
        
        do {
            $dueTicketsParams['offset'] = $currentDueOffset;
            
            $dueTicketsResponse = $this->makeApiRequest('/issues.json', $dueTicketsParams);
            
            if (!$dueTicketsResponse || !isset($dueTicketsResponse['issues'])) {
                Log::warning('オフセット' . $currentDueOffset . 'での期限付きチケットの取得に失敗しました');
                break;
            }
            
            $currentDueTickets = $dueTicketsResponse['issues'];
            $dueTicketsCount = count($currentDueTickets);
            
            if ($dueTicketsCount === 0) {
                break;
            }
            
            $dueTickets = array_merge($dueTickets, $currentDueTickets);
            
            $totalDueCount += $dueTicketsCount;
            $currentDueOffset += $dueTicketsParams['limit'];
            
            $totalDueAvailable = isset($dueTicketsResponse['total_count']) ? $dueTicketsResponse['total_count'] : 0;
            
            Log::info("{$dueTicketsCount}件の期限付きチケットを取得しました（オフセット: {$dueTicketsParams['offset']}, 合計: {$totalDueCount}, 利用可能な合計: {$totalDueAvailable}）");
            
        } while ($dueTicketsCount === $dueTicketsParams['limit']); // フルページを取得した場合は続行
        
        Log::info("ページネーション後、合計" . count($dueTickets) . "件の期限付きチケットを取得しました");

        $userTimeEntries = [];
        $issueIds = [];
        $userDueTickets = []; // ユーザーごとの期限付きチケット

        $userSettings = [];
        $userSettingsFromDB = \App\Models\UserSetting::all();
        foreach ($userSettingsFromDB as $setting) {
            $userSettings[$setting->user_id] = [
                'monthly_working_hours' => $setting->monthly_working_hours,
                'exclude_keywords' => $setting->exclude_keywords ? explode(',', $setting->exclude_keywords) : []
            ];
        }
        
        $defaultExcludeKeywords = ['コアデイ', '朝会', '有給'];
        
        foreach ($allTimeEntries as $entry) {
            $userId = $entry['user']['id'];
            $userName = $entry['user']['name'];
            $issueId = $entry['issue']['id'];
            $hours = $entry['hours'];
            $comments = isset($entry['comments']) ? $entry['comments'] : '';
            $issueSubject = isset($entry['issue']['subject']) ? $entry['issue']['subject'] : '';
            
            $shouldExclude = false;
            $excludeReason = '';
            
            $userExcludeKeywords = $defaultExcludeKeywords;
            if (isset($userSettings[$userId]) && !empty($userSettings[$userId]['exclude_keywords'])) {
                $userExcludeKeywords = $userSettings[$userId]['exclude_keywords'];
            }
            
            foreach ($userExcludeKeywords as $keyword) {
                if (mb_stripos($comments, $keyword) !== false || mb_stripos($issueSubject, $keyword) !== false) {
                    $shouldExclude = true;
                    $excludeReason = $keyword;
                    Log::info("除外キーワード '{$keyword}' が含まれているため、チケット #{$issueId} ({$issueSubject}) の時間エントリを除外します。コメント: {$comments}");
                    break;
                }
            }
            
            if (!isset($userTimeEntries[$userId])) {
                $userTimeEntries[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'working_hours' => 0,
                    'excluded_hours' => 0,
                    'excluded_tickets' => [],
                    'issues' => []
                ];
                
                $userDueTickets[$userId] = [];
            }
            
            if ($shouldExclude) {
                if (!isset($userTimeEntries[$userId]['excluded_hours'])) {
                    $userTimeEntries[$userId]['excluded_hours'] = 0;
                }
                $userTimeEntries[$userId]['excluded_hours'] += $hours;
                
                if (!isset($userTimeEntries[$userId]['excluded_tickets'][$issueId])) {
                    $userTimeEntries[$userId]['excluded_tickets'][$issueId] = [
                        'id' => $issueId,
                        'subject' => $issueSubject,
                        'hours' => 0,
                        'reason' => $excludeReason
                    ];
                }
                $userTimeEntries[$userId]['excluded_tickets'][$issueId]['hours'] += $hours;
                continue;
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
        
        foreach ($dueTickets as $ticket) {
            if (isset($ticket['assigned_to']) && isset($ticket['assigned_to']['id'])) {
                $userId = $ticket['assigned_to']['id'];
                $issueId = $ticket['id'];
                
                if (!isset($userTimeEntries[$userId])) {
                    $userTimeEntries[$userId] = [
                        'user_id' => $userId,
                        'user_name' => $ticket['assigned_to']['name'],
                        'working_hours' => 0,
                        'issues' => []
                    ];
                    
                    $userDueTickets[$userId] = [];
                }
                
                $userDueTickets[$userId][$issueId] = true;
                
                if (!in_array($issueId, $issueIds)) {
                    $issueIds[] = $issueId;
                }
            }
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

                    $statusName = $issue['status']['name'];
                    $statusId = $issue['status']['id'] ?? null;
                    $status = $this->upsertStatus($statusId, $statusName);

                    $isCompletedStatus = $status ? $status->is_completed : false;

                    // データベースに登録されていない場合はデフォルトのリストを使用
                    if (!$status) {
                        $completedStatuses = ['Closed', '完了', '終了', 'Resolved', '解決', 'Done', 'Fixed', '修正済み', 'Feedback', 'フィードバック'];
                        $isCompletedStatus = in_array($statusName, $completedStatuses);
                    }

                    $issueDetails[$issue['id']] = [
                        'id' => $issue['id'],
                        'subject' => $issue['subject'],
                        'status' => $issue['status']['name'],
                        'is_completed_status' => $isCompletedStatus, // ステータスが完了状態かどうか
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
            $baseTickets = []; // 母数となるチケットのIDを保存

            foreach ($userData['issues'] as $issueId => $issueData) {
                if (isset($issueDetails[$issueId])) {
                    $baseTickets[$issueId] = true;
                    
                    $issue = $issueDetails[$issueId];
                    
                    if ($issue['is_completed_status']) {
                        $completedTickets++;
                        
                        if ($issue['estimated_hours'] > 0 && $issueData['spent_hours'] <= $issue['estimated_hours']) {
                            $consumedTickets++;
                            $consumedEstimatedHours += $issue['estimated_hours'];
                        }
                    }
                    
                    Log::info("チケット #{$issue['id']} ({$issue['subject']}): ステータス={$issue['status']}, 完了状態=" .
                        ($issue['is_completed_status'] ? 'はい' : 'いいえ') .
                        ", 予定工数={$issue['estimated_hours']}, 実績時間={$issueData['spent_hours']}");
                }
            }
            
            if (isset($userDueTickets[$userId])) {
                foreach ($userDueTickets[$userId] as $issueId => $value) {
                    if (!isset($baseTickets[$issueId]) && isset($issueDetails[$issueId])) {
                        $baseTickets[$issueId] = true;
                        
                        $issue = $issueDetails[$issueId];
                        
                        if ($issue['is_completed_status']) {
                            $completedTickets++;
                            
                            if ($issue['estimated_hours'] > 0) {
                                $consumedTickets++;
                                $consumedEstimatedHours += $issue['estimated_hours'];
                            }
                        }
                        
                        Log::info("期限付きチケット #{$issue['id']} ({$issue['subject']}): ステータス={$issue['status']}, 完了状態=" .
                            ($issue['is_completed_status'] ? 'はい' : 'いいえ') .
                            ", 予定工数={$issue['estimated_hours']}");
                    }
                }
            }
            
            $totalTickets = count($baseTickets);
            
            $workingHours = $userData['working_hours'];
            $excludedHours = isset($userData['excluded_hours']) ? $userData['excluded_hours'] : 0;
            
            $dateObj = Carbon::parse($startDate);
            $monthWorkingHours = $this->calculateMonthWorkingHours($dateObj, $userData['user_id']);
            
            $completedEstimatedHours = 0;
            foreach ($userData['issues'] as $issueId => $issueData) {
                if (isset($issueDetails[$issueId]) && $issueDetails[$issueId]['is_completed_status'] && $issueDetails[$issueId]['estimated_hours'] > 0) {
                    $completedEstimatedHours += $issueDetails[$issueId]['estimated_hours'];
                }
            }
            
            $adjustedMonthWorkingHours = $monthWorkingHours - $excludedHours;
            $progressRate = ($adjustedMonthWorkingHours > 0) ? round(($consumedEstimatedHours / $adjustedMonthWorkingHours) * 100) : 0;
            $ticketCompletionRate = ($totalTickets > 0) ? round(($completedTickets / $totalTickets) * 100) : 0;

            $excludedTicketsArray = [];
            if (isset($userData['excluded_tickets'])) {
                foreach ($userData['excluded_tickets'] as $ticketId => $ticketData) {
                    $excludedTicketsArray[] = $ticketData;
                }
            }
            
            $consumptionStats[] = [
                'user_id' => $userData['user_id'],
                'user_name' => $userData['user_name'],
                'consumed_estimated_hours' => $consumedEstimatedHours, // 完了時間（完了したチケットの予定工数）
                'working_hours' => $workingHours, // 稼働時間
                'excluded_hours' => $excludedHours, // 除外された時間（コアデイ、朝会、有給）
                'excluded_tickets' => $excludedTicketsArray, // 除外されたチケット情報
                'progress_rate' => $progressRate, // 進捗率（消化チケットの予定工数 / 月の稼働時間）
                'total_tickets' => $totalTickets, // 総チケット数
                'completed_tickets' => $completedTickets, // 完了チケット数
                'ticket_completion_rate' => $ticketCompletionRate, // チケット完了率
                'completed_estimated_hours' => $completedEstimatedHours, // 完了チケットの予定工数合計
                'month_working_hours' => $monthWorkingHours // 月の稼働時間（土日祝日を除く）
            ];
        }

        return $consumptionStats;
    }

    /**
     * 指定した月の稼働時間を計算（土日祝日を除いた日の合計×8h）
     *
     * @param Carbon $date
     * @return int
     */
    protected function calculateMonthWorkingHours(Carbon $date, $userId = null)
    {
        if ($userId) {
            $userSetting = \App\Models\UserSetting::where('user_id', $userId)->first();
            if ($userSetting && $userSetting->monthly_working_hours) {
                return $userSetting->monthly_working_hours;
            }
        }
        
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $currentDate = $startOfMonth->copy();
        
        $holidays = [
            '2025-01-01', // 元日
            '2025-01-13', // 成人の日
            '2025-02-11', // 建国記念日
            '2025-02-23', // 天皇誕生日
            '2025-03-21', // 春分の日
            '2025-04-29', // 昭和の日
            '2025-05-03', // 憲法記念日
            '2025-05-04', // みどりの日
            '2025-05-05', // こどもの日
            '2025-05-06', // 振替休日
            '2025-07-21', // 海の日
            '2025-08-11', // 山の日
            '2025-09-15', // 敬老の日
            '2025-09-23', // 秋分の日
            '2025-10-13', // スポーツの日
            '2025-11-03', // 文化の日
            '2025-11-23', // 勤労感謝の日
            '2025-12-23', // 天皇誕生日
        ];
        
        $workingDays = 0;
        
        while ($currentDate->lte($endOfMonth)) {
            $dayOfWeek = $currentDate->dayOfWeek;
            $dateString = $currentDate->format('Y-m-d');
            
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6 && !in_array($dateString, $holidays)) {
                $workingDays++;
            }
            
            $currentDate->addDay();
        }
        
        return $workingDays * 8;
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

    /**
     * 特定ユーザーのチケット詳細を取得
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array|null
     */
    public function getUserTicketDetails($userId, $startDate, $endDate, $projectId = null)
    {
        Log::info("ユーザーID: {$userId}のチケット詳細を取得します（期間: {$startDate}から{$endDate}）");

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
                'user_id' => $userId,
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
                Log::warning("ユーザーID: {$userId}の指定された日付範囲の時間エントリが見つかりませんでした");
                return [];
            }

            Log::info("ページネーション後、合計" . count($allTimeEntries) . "件の時間エントリを取得しました");

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
        }

        $userTimeEntries = array_filter($allTimeEntries, function($entry) use ($userId) {
            return $entry['user']['id'] == $userId;
        });

        if (empty($userTimeEntries)) {
            Log::warning("ユーザーID: {$userId}の時間エントリが見つかりませんでした");
            return [];
        }

        $issueIds = [];
        foreach ($userTimeEntries as $entry) {
            $issueIds[] = $entry['issue']['id'];
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
                    $statusName = $issue['status']['name'];
                    $statusId = $issue['status']['id'] ?? null;
                    $status = $this->upsertStatus($statusId, $statusName);

                    $isCompletedStatus = $status ? $status->is_completed : false;

                    // データベースに登録されていない場合はデフォルトのリストを使用
                    if (!$status) {
                        $completedStatuses = ['Closed', '完了', '終了', 'Resolved', '解決', 'Done', 'Fixed', '修正済み', 'Feedback', 'フィードバック'];
                        $isCompletedStatus = in_array($statusName, $completedStatuses);
                    }

                    $issueDetails[$issue['id']] = [
                        'id' => $issue['id'],
                        'subject' => $issue['subject'],
                        'status' => $issue['status']['name'],
                        'is_completed' => $isCompletedStatus,
                        'estimated_hours' => isset($issue['estimated_hours']) ? $issue['estimated_hours'] : 0,
                        'spent_hours' => 0 // 後で更新
                    ];
                }
            }
        }

        foreach ($userTimeEntries as $entry) {
            $issueId = $entry['issue']['id'];
            if (isset($issueDetails[$issueId])) {
                $issueDetails[$issueId]['spent_hours'] += $entry['hours'];
            }
        }

        $result = [];
        foreach ($issueDetails as $issueId => $issue) {
            $isConsumed = $issue['is_completed'] && $issue['estimated_hours'] > 0 && $issue['spent_hours'] <= $issue['estimated_hours'];

            $result[] = [
                'id' => $issue['id'],
                'subject' => $issue['subject'],
                'status' => $issue['status'],
                'estimated_hours' => $issue['estimated_hours'],
                'spent_hours' => $issue['spent_hours'],
                'is_completed' => $issue['is_completed'],
                'is_consumed' => $isConsumed
            ];
        }

        usort($result, function($a, $b) {
            return $a['id'] - $b['id'];
        });

        Log::info("ユーザーID: {$userId}のチケット詳細を" . count($result) . "件取得しました");

        return $result;
    }

    /**
     * ステータスをデータベースに登録または更新する
     *
     * @param int|null $redmineId
     * @param string $name
     * @param bool $isCompleted
     * @return \App\Models\RedmineStatus
     */
    protected function upsertStatus($redmineId, $name, $isCompleted = false)
    {
        try {
            $completedStatuses = ['Closed', '完了', '終了', 'Resolved', '解決', 'Done', 'Fixed', '修正済み', 'Feedback', 'フィードバック'];

            if (in_array($name, $completedStatuses)) {
                $isCompleted = true;
            }

            $status = RedmineStatus::updateOrCreate(
                ['name' => $name],
                [
                    'redmine_id' => $redmineId,
                    'is_completed' => $isCompleted
                ]
            );

            return $status;
        } catch (\Exception $e) {
            Log::error('ステータスのデータベースへの保存に失敗しました', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * 初期ステータスをデータベースに登録
     */
    protected function initializeStatuses()
    {
        $completedStatuses = ['Closed', '完了', '終了', 'Resolved', '解決', 'Done', 'Fixed', '修正済み', 'Feedback', 'フィードバック'];

        foreach ($completedStatuses as $status) {
            $this->upsertStatus(null, $status, true);
        }
    }
}
