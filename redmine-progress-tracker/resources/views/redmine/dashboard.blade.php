<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redmine 工数進捗率レポート - タスク完了/未完了</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #007bff;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0 sidebar">
                <div class="text-center mb-4">
                    <h5 class="text-white">Redmine Progress</h5>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('dashboard') }}">
                            <i class="bi bi-bar-chart-fill me-2"></i>タスク完了/未完了
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('progress-rate') }}">
                            <i class="bi bi-graph-up me-2"></i>進捗率
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h1 class="mb-4">Redmine 工数進捗率レポート - タスク完了/未完了</h1>
                
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="start-date" class="form-label">開始日</label>
                            <input type="date" id="start-date" class="form-control" value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end-date" class="form-label">終了日</label>
                            <input type="date" id="end-date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="project-id" class="form-label">プロジェクト</label>
                            <select id="project-id" class="form-select">
                                <option value="">すべて</option>
                                <!-- プロジェクトリストはAPIから取得して動的に追加 -->
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="update-btn" class="btn btn-primary w-100">更新</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>日別タスク完了/未完了</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="daily-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>月別タスク完了/未完了</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="monthly-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>進捗率サマリー</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h3 id="total-tasks">-</h3>
                                                <p>総タスク数</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h3 id="completed-tasks">-</h3>
                                                <p>完了タスク</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-warning">
                                            <div class="card-body text-center">
                                                <h3 id="completion-rate">-</h3>
                                                <p>完了率</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let dailyChart = null;
        let monthlyChart = null;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('update-btn').addEventListener('click', function() {
                fetchAndUpdateCharts();
            });

            fetchAndUpdateCharts();
        });

        function fetchAndUpdateCharts() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const projectId = document.getElementById('project-id').value;

            fetch(`/api/daily-stats?start_date=${startDate}&end_date=${endDate}&project_id=${projectId}`)
                .then(response => response.json())
                .then(data => {
                    updateDailyChart(data);
                    updateSummary(data);
                })
                .catch(error => console.error('日別データ取得エラー:', error));

            fetch(`/api/monthly-stats?start_date=${startDate}&end_date=${endDate}&project_id=${projectId}`)
                .then(response => response.json())
                .then(data => {
                    updateMonthlyChart(data);
                })
                .catch(error => console.error('月別データ取得エラー:', error));
        }

        function updateDailyChart(data) {
            const ctx = document.getElementById('daily-chart').getContext('2d');
            
            const labels = data.map(item => item.date);
            const completedData = data.map(item => item.completed);
            const incompleteData = data.map(item => item.incomplete);

            if (dailyChart) {
                dailyChart.destroy();
            }

            dailyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '完了タスク',
                            data: completedData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '未完了タスク',
                            data: incompleteData,
                            backgroundColor: 'rgba(255, 193, 7, 0.7)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function updateMonthlyChart(data) {
            const ctx = document.getElementById('monthly-chart').getContext('2d');
            
            const labels = data.map(item => item.month);
            const completedData = data.map(item => item.completed);
            const incompleteData = data.map(item => item.incomplete);

            if (monthlyChart) {
                monthlyChart.destroy();
            }

            monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '完了タスク',
                            data: completedData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '未完了タスク',
                            data: incompleteData,
                            backgroundColor: 'rgba(255, 193, 7, 0.7)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function updateSummary(data) {
            let totalCompleted = 0;
            let totalIncomplete = 0;

            data.forEach(item => {
                totalCompleted += item.completed;
                totalIncomplete += item.incomplete;
            });

            const totalTasks = totalCompleted + totalIncomplete;
            const completionRate = totalTasks > 0 ? Math.round((totalCompleted / totalTasks) * 100) : 0;

            document.getElementById('total-tasks').textContent = totalTasks;
            document.getElementById('completed-tasks').textContent = totalCompleted;
            document.getElementById('completion-rate').textContent = `${completionRate}%`;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
