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
     * Display the individual progress rate page
     *
     * @return \Illuminate\View\View
     */
    public function individualProgress()
    {
        $projects = $this->redmineService->getProjects();
        
        $currentMonth = Carbon::now();
        $startDate = $currentMonth->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        $initialData = $this->redmineService->getIndividualProgressStats($startDate, $endDate);
        
        return view('redmine.individual_progress', compact('projects', 'initialData'));
    }
    
    /**
     * Get individual progress rate statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndividualProgressStats(Request $request)
    {
        $selectedMonth = $request->input('selected_month');
        $projectId = $request->input('project_id');
        
        if ($selectedMonth) {
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
        } else {
            $currentMonth = Carbon::now();
            $startDate = $currentMonth->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }

        try {
            $stats = $this->redmineService->getIndividualProgressStats($startDate, $endDate, $projectId);
            
            if (!$stats) {
                return response()->json([
                    'error' => true,
                    'message' => 'データの取得に失敗しました。'
                ], 500);
            }
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'エラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * ユーザーのチケット詳細を取得
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserTicketDetails(Request $request)
    {
        $userId = $request->input('user_id');
        $selectedMonth = $request->input('selected_month');
        $projectId = $request->input('project_id');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        
        if ($selectedMonth) {
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $startDate = $date->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $date->copy()->endOfMonth()->format('Y-m-d');
        } else {
            $currentMonth = Carbon::now();
            $startDate = $currentMonth->copy()->startOfMonth()->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        if (!$userId) {
            return response()->json([
                'error' => true,
                'message' => 'ユーザーIDが指定されていません。'
            ], 400);
        }
        
        try {
            $allTicketDetails = $this->redmineService->getUserTicketDetails($userId, $startDate, $endDate, $projectId);
            
            if (!$allTicketDetails) {
                return response()->json([
                    'error' => true,
                    'message' => 'チケット詳細の取得に失敗しました。'
                ], 500);
            }
            
            $totalItems = count($allTicketDetails);
            $totalPages = ceil($totalItems / $perPage);
            
            $offset = ($page - 1) * $perPage;
            $currentPageItems = array_slice($allTicketDetails, $offset, $perPage);
            
            return response()->json([
                'tickets' => $currentPageItems,
                'pagination' => [
                    'total_items' => $totalItems,
                    'total_pages' => $totalPages,
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'エラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
