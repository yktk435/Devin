<?php

namespace App\Repositories;

use App\Interfaces\RedmineAPIClientInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Real implementation of RedmineAPIClient
 * 
 * This class implements the RedmineAPIClientInterface to interact with the Redmine API.
 * Configuration is read from .env file.
 */
class RedmineAPIClient implements RedmineAPIClientInterface
{
    protected $apiUrl;
    protected $apiKey;
    protected $isConfigured;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = env('REDMINE_API_URL');
        $this->apiKey = env('REDMINE_API_KEY');
        $this->isConfigured = !empty($this->apiUrl) && !empty($this->apiKey);
        
        if (!$this->isConfigured) {
            Log::warning('RedmineAPIClient is not properly configured. Check REDMINE_API_URL and REDMINE_API_KEY in .env');
        }
    }

    /**
     * Make an API request to Redmine
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

            Log::error('Redmine API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Exception in Redmine API request', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Get daily statistics from Redmine API
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
     * Get monthly statistics from Redmine API
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
     * Get progress rate statistics from Redmine API
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
     * Get individual consumption rate statistics from Redmine API
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array|null
     */
    public function getIndividualConsumptionStats($startDate, $endDate, $projectId = null)
    {
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
                Log::warning('Failed to retrieve time entries at offset ' . $currentOffset);
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
            
            Log::info("Retrieved {$entriesCount} time entries (offset: {$timeEntriesParams['offset']}, total so far: {$totalCount}, total available: {$totalAvailable})");
            
        } while ($entriesCount === $timeEntriesParams['limit']); // Continue if we got a full page
        
        if (empty($allTimeEntries)) {
            Log::warning('No time entries found for the specified date range');
            return null;
        }
        
        Log::info("Retrieved a total of " . count($allTimeEntries) . " time entries after pagination");
        
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
                    $issueDetails[$issue['id']] = [
                        'id' => $issue['id'],
                        'subject' => $issue['subject'],
                        'status' => $issue['status']['name'],
                        'is_completed' => ($issue['status']['name'] === 'Closed' || $issue['status']['name'] === '完了'),
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
     * Get available projects from Redmine API
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
            Log::warning('Failed to retrieve projects from Redmine API');
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
}
