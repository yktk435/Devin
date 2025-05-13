<?php

namespace App\Repositories;

use App\Interfaces\RedmineAPIClientInterface;
use Carbon\Carbon;

class MockRedmineAPIClient implements RedmineAPIClientInterface
{
    /**
     * Get daily statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getDailyStats($startDate, $endDate, $projectId = null)
    {
        return $this->getMockDailyStats($startDate, $endDate);
    }

    /**
     * Get monthly statistics
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
     * Get individual progress rate statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getIndividualConsumptionStats($startDate, $endDate, $projectId = null)
    {
        return $this->getMockIndividualConsumptionStats($startDate, $endDate);
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
            $totalTasks = rand(10, 50);
            $completedTasks = rand(0, $totalTasks);
            
            $dailyStats[] = [
                'date' => $dateStr,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'incomplete_tasks' => $totalTasks - $completedTasks
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
            $totalTasks = rand(50, 200);
            $completedTasks = rand(0, $totalTasks);
            
            $monthlyStats[] = [
                'month' => $monthStr,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'incomplete_tasks' => $totalTasks - $completedTasks
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
    
    /**
     * Generate mock individual progress rate statistics for prototype
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getMockIndividualConsumptionStats($startDate, $endDate)
    {
        $users = [
            ['id' => 1, 'name' => '山田太郎'],
            ['id' => 2, 'name' => '佐藤花子'],
            ['id' => 3, 'name' => '鈴木一郎'],
            ['id' => 4, 'name' => '田中美咲'],
            ['id' => 5, 'name' => '伊藤健太']
        ];
        
        $consumptionStats = [];
        
        foreach ($users as $user) {
            $consumedEstimatedHours = rand(20, 80);
            
            $workingHours = rand($consumedEstimatedHours, 100);
            
            $achievementRate = round(($consumedEstimatedHours / $workingHours) * 100);
            
            $totalTickets = rand(10, 30);
            $completedTickets = rand(5, $totalTickets);
            $consumedTickets = rand(0, $completedTickets); // 消化したチケット（完了かつ予定工数以内）
            
            $consumptionStats[] = [
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'consumed_estimated_hours' => $consumedEstimatedHours, // 消化時間（消化したチケットの予定工数）
                'working_hours' => $workingHours, // 稼働時間
                'achievement_rate' => $achievementRate, // 達成率
                'total_tickets' => $totalTickets, // 総チケット数
                'completed_tickets' => $completedTickets, // 完了チケット数
                'consumed_tickets' => $consumedTickets, // 消化チケット数（完了かつ予定工数以内）
                'ticket_consumption_rate' => round(($consumedTickets / $totalTickets) * 100) // チケット消化率
            ];
        }
        
        return $consumptionStats;
    }
    
    /**
     * Get available projects
     * 
     * @return array
     */
    public function getProjects()
    {
        return [
            [
                'id' => 1,
                'name' => '開発プロジェクトA',
                'identifier' => 'dev-project-a',
                'description' => '主要開発プロジェクト'
            ],
            [
                'id' => 2,
                'name' => '保守プロジェクトB',
                'identifier' => 'maintenance-b',
                'description' => 'システム保守プロジェクト'
            ],
            [
                'id' => 3,
                'name' => '新規開発C',
                'identifier' => 'new-dev-c',
                'description' => '新規機能開発プロジェクト'
            ],
            [
                'id' => 4,
                'name' => 'バグ修正D',
                'identifier' => 'bugfix-d',
                'description' => 'バグ修正プロジェクト'
            ],
            [
                'id' => 5,
                'name' => 'リファクタリングE',
                'identifier' => 'refactor-e',
                'description' => 'コードリファクタリングプロジェクト'
            ]
        ];
    }
}
