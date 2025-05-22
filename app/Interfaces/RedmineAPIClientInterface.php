<?php

namespace App\Interfaces;

interface RedmineAPIClientInterface
{
    /**
     * Get daily statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getDailyStats($startDate, $endDate, $projectId = null);

    /**
     * Get monthly statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getMonthlyStats($startDate, $endDate, $projectId = null);

    /**
     * Get progress rate statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getProgressRateStats($startDate, $endDate, $projectId = null);
    
    /**
     * Get individual progress rate statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @param bool $forceRefresh
     * @return array
     */
    public function getIndividualProgressStats($startDate, $endDate, $projectId = null, $forceRefresh = false);
    
    /**
     * Get user ticket details
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @param bool $forceRefresh
     * @return array
     */
    public function getUserTicketDetails($userId, $startDate, $endDate, $projectId = null, $forceRefresh = false);
    
    /**
     * Get available projects
     * 
     * @return array
     */
    public function getProjects();
}
