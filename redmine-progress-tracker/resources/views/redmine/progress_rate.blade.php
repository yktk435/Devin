<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redmine 工数進捗率レポート - 進捗率</title>
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
        .progress-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .progress-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 20px;
        }
        .progress-card .card-body {
            padding: 20px;
        }
        .progress-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .progress-label {
            font-size: 0.9rem;
            color: #6c757d;
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
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="bi bi-bar-chart-fill me-2"></i>タスク完了/未完了
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('progress-rate') }}">
                            <i class="bi bi-graph-up me-2"></i>進捗率
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h1 class="mb-4">Redmine 工数進捗率レポート - 進捗率</h1>
                
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="start-date" class="form-label">開始日</label>
                            <input type="date" id="start-date" class="form-control" value="{{ date('Y-m-d', strtotime('-3 months')) }}">
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

                <!-- Progress Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card progress-card">
                            <div class="card-header">
                                <h5>工数進捗率</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="progress-value" id="hours-progress">-</div>
                                <div class="progress-label">予定工数に対する実績</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card progress-card">
                            <div class="card-header">
                                <h5>期間進捗率</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="progress-value" id="time-progress">-</div>
                                <div class="progress-label">期限に対する経過日数</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card progress-card">
                            <div class="card-header">
                                <h5>ポイント進捗率</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="progress-value" id="points-progress">-</div>
                                <div class="progress-label">総ポイントに対する完了</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>進捗率の推移</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="progress-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>工数の比較</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="hours-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>ポイントの比較</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="points-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let progressChart = null;
        let hoursChart = null;
        let pointsChart = null;

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

            fetch(`/api/progress-rate-stats?start_date=${startDate}&end_date=${endDate}&project_id=${projectId}`)
                .then(response => response.json())
                .then(data => {
                    updateProgressChart(data);
                    updateHoursChart(data);
                    updatePointsChart(data);
                    updateSummary(data);
                })
                .catch(error => console.error('進捗率データ取得エラー:', error));
        }

        function updateProgressChart(data) {
            const ctx = document.getElementById('progress-chart').getContext('2d');
            
            const labels = data.map(item => item.date);
            const hoursProgressData = data.map(item => item.hours_progress);
            const timeProgressData = data.map(item => item.time_progress);
            const pointsProgressData = data.map(item => item.points_progress);

            if (progressChart) {
                progressChart.destroy();
            }

            progressChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '工数進捗率',
                            data: hoursProgressData,
                            borderColor: 'rgba(40, 167, 69, 1)',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: '期間進捗率',
                            data: timeProgressData,
                            borderColor: 'rgba(0, 123, 255, 1)',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'ポイント進捗率',
                            data: pointsProgressData,
                            borderColor: 'rgba(255, 193, 7, 1)',
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: '進捗率 (%)'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }

        function updateHoursChart(data) {
            const ctx = document.getElementById('hours-chart').getContext('2d');
            
            const labels = data.map(item => item.date);
            const estimatedHoursData = data.map(item => item.estimated_hours);
            const spentHoursData = data.map(item => item.spent_hours);

            if (hoursChart) {
                hoursChart.destroy();
            }

            hoursChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '予定工数',
                            data: estimatedHoursData,
                            backgroundColor: 'rgba(0, 123, 255, 0.7)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '実績工数',
                            data: spentHoursData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '工数 (時間)'
                            }
                        }
                    }
                }
            });
        }

        function updatePointsChart(data) {
            const ctx = document.getElementById('points-chart').getContext('2d');
            
            const labels = data.map(item => item.date);
            const totalPointsData = data.map(item => item.total_points);
            const completedPointsData = data.map(item => item.completed_points);

            if (pointsChart) {
                pointsChart.destroy();
            }

            pointsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '総ポイント',
                            data: totalPointsData,
                            backgroundColor: 'rgba(255, 193, 7, 0.7)',
                            borderColor: 'rgba(255, 193, 7, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '完了ポイント',
                            data: completedPointsData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'ストーリーポイント'
                            }
                        }
                    }
                }
            });
        }

        function updateSummary(data) {
            if (data.length > 0) {
                const latestData = data[data.length - 1];
                
                document.getElementById('hours-progress').textContent = `${latestData.hours_progress}%`;
                document.getElementById('time-progress').textContent = `${latestData.time_progress}%`;
                document.getElementById('points-progress').textContent = `${latestData.points_progress}%`;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</body>
</html>
