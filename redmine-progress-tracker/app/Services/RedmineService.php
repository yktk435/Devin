<?php

namespace App\Services;

use App\Interfaces\RedmineAPIClientInterface;
use Illuminate\Support\Facades\Http;

class RedmineService
{
    protected $apiUrl;
    protected $apiKey;
    protected $redmineClient;

    /**
     * Constructor with dependency injection for RedmineAPIClientInterface
     * 
     * @param RedmineAPIClientInterface $redmineClient
     */
    public function __construct(RedmineAPIClientInterface $redmineClient)
    {
        $this->apiUrl = env('REDMINE_API_URL', 'http://localhost/redmine');
        $this->apiKey = env('REDMINE_API_KEY', '');
        $this->redmineClient = $redmineClient;
    }

    /**
     * Get issues from Redmine API
     *
     * @param array $params
     * @return array
     */
    public function getIssues(array $params = [])
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiUrl . '/issues.json', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => 'Failed to fetch issues', 'status' => $response->status()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get daily task completion statistics
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getDailyStats($startDate, $endDate, $projectId = null)
    {
        return $this->redmineClient->getDailyStats($startDate, $endDate, $projectId);
        
        /* 
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

        foreach ($issues['issues'] as $issue) {
            $createdDate = substr($issue['created_on'], 0, 10);
            
            if (isset($dailyStats[$createdDate])) {
                if ($issue['status']['id'] == 5) { // Assuming 5 is the ID for "Closed" status
                    $dailyStats[$createdDate]['completed']++;
                } else {
                    $dailyStats[$createdDate]['incomplete']++;
                }
            }
        }

        return array_values($dailyStats);
        */
    }

    /**
     * Get monthly task completion statistics
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getMonthlyStats($startDate, $endDate, $projectId = null)
    {
        return $this->redmineClient->getMonthlyStats($startDate, $endDate, $projectId);
    }

    /**
     * Get progress rate statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getProgressRateStats($startDate, $endDate, $projectId = null)
    {
        return $this->redmineClient->getProgressRateStats($startDate, $endDate, $projectId);
    }
    
    /**
     * Get individual consumption rate statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getIndividualConsumptionStats($startDate, $endDate, $projectId = null)
    {
        return $this->redmineClient->getIndividualConsumptionStats($startDate, $endDate, $projectId);
    }
    
    /**
     * Get available projects
     * 
     * @return array
     */
    public function getProjects()
    {
        return $this->redmineClient->getProjects();
    }
}
