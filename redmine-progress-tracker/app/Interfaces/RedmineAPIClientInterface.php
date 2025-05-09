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
     * Get individual consumption rate statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int|null $projectId
     * @return array
     */
    public function getIndividualConsumptionStats($startDate, $endDate, $projectId = null);
    
    /**
     * Get available projects
     * 
     * @return array
     */
    public function getProjects();
}
