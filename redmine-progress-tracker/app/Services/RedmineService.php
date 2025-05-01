<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class RedmineService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = env('REDMINE_API_URL', 'http://localhost/redmine');
        $this->apiKey = env('REDMINE_API_KEY', '');
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
        return $this->getMockDailyStats($startDate, $endDate);

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
        return $this->getMockMonthlyStats($startDate, $endDate);
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
        return $this->getMockProgressRateStats($startDate, $endDate);
    }

    /**
     * Generate mock daily statistics for prototype
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getMockDailyStats($startDate, $endDate)
    {
        $dailyStats = [];
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        for ($date = $startCarbon; $date->lte($endCarbon); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $dailyStats[] = [
                'date' => $dateStr,
                'completed' => rand(1, 10),
                'incomplete' => rand(1, 15)
            ];
        }

        return $dailyStats;
    }

    /**
     * Generate mock monthly statistics for prototype
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getMockMonthlyStats($startDate, $endDate)
    {
        $monthlyStats = [];
        $startCarbon = Carbon::parse($startDate)->startOfMonth();
        $endCarbon = Carbon::parse($endDate)->endOfMonth();

        for ($date = $startCarbon; $date->lte($endCarbon); $date->addMonth()) {
            $monthStr = $date->format('Y-m');
            $monthlyStats[] = [
                'month' => $monthStr,
                'completed' => rand(20, 50),
                'incomplete' => rand(10, 40)
            ];
        }

        return $monthlyStats;
    }

    /**
     * Generate mock progress rate statistics for prototype
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getMockProgressRateStats($startDate, $endDate)
    {
        $progressStats = [];
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        
        for ($date = $startCarbon; $date->lte($endCarbon); $date->addWeek()) {
            $weekStr = $date->format('Y-m-d');
            
            $estimatedHours = rand(40, 100);
            $spentHours = rand(20, $estimatedHours);
            $progressPercent = round(($spentHours / $estimatedHours) * 100);
            
            $totalDays = rand(10, 30);
            $elapsedDays = rand(1, $totalDays);
            $timeProgressPercent = round(($elapsedDays / $totalDays) * 100);
            
            $totalPoints = rand(20, 50);
            $completedPoints = rand(0, $totalPoints);
            $pointsProgressPercent = round(($completedPoints / $totalPoints) * 100);
            
            $progressStats[] = [
                'date' => $weekStr,
                'estimated_hours' => $estimatedHours,
                'spent_hours' => $spentHours,
                'hours_progress' => $progressPercent,
                'total_days' => $totalDays,
                'elapsed_days' => $elapsedDays,
                'time_progress' => $timeProgressPercent,
                'total_points' => $totalPoints,
                'completed_points' => $completedPoints,
                'points_progress' => $pointsProgressPercent
            ];
        }
        
        return $progressStats;
    }
}
