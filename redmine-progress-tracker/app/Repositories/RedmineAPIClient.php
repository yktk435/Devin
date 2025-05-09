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
        $params = [
            'status_id' => '*',
            'created_on' => urlencode('><') . $startDate . '|' . $endDate,
            'include' => 'time_entries',
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
}
