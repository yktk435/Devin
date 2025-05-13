<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RedmineService;
use Carbon\Carbon;

class RedmineController extends Controller
{
    protected $redmineService;

    public function __construct(RedmineService $redmineService)
    {
        $this->redmineService = $redmineService;
    }

    /**
     * Display the dashboard
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $projects = $this->redmineService->getProjects();
        return view('redmine.dashboard', compact('projects'));
    }

    /**
     * Display the progress rate page
     *
     * @return \Illuminate\View\View
     */
    public function progressRate()
    {
        $projects = $this->redmineService->getProjects();
        return view('redmine.progress_rate', compact('projects'));
    }

    /**
     * Get daily statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDailyStats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $projectId = $request->input('project_id');

        $stats = $this->redmineService->getDailyStats($startDate, $endDate, $projectId);

        return response()->json($stats);
    }

    /**
     * Get monthly statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyStats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonths(6)->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $projectId = $request->input('project_id');

        $stats = $this->redmineService->getMonthlyStats($startDate, $endDate, $projectId);

        return response()->json($stats);
    }

    /**
     * Get progress rate statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgressRateStats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $projectId = $request->input('project_id');

        $stats = $this->redmineService->getProgressRateStats($startDate, $endDate, $projectId);

        return response()->json($stats);
    }
    
    /**
     * Display the individual consumption rate page
     *
     * @return \Illuminate\View\View
     */
    public function individualConsumption()
    {
        $projects = $this->redmineService->getProjects();
        
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        $initialData = $this->redmineService->getIndividualConsumptionStats($startDate, $endDate);
        
        return view('redmine.individual_consumption', compact('projects', 'initialData'));
    }
    
    /**
     * Get individual consumption rate statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndividualConsumptionStats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonths(1)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $projectId = $request->input('project_id');

        $stats = $this->redmineService->getIndividualConsumptionStats($startDate, $endDate, $projectId);

        return response()->json($stats);
    }
}
