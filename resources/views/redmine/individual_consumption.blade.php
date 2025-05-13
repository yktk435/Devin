<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redmine 工数進捗率レポート - 個人別チケット消化率</title>
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
        .user-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .user-card:hover {
            transform: translateY(-5px);
        }
        .user-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 20px;
        }
        .user-card .card-body {
            padding: 20px;
        }
        .achievement-rate {
            font-size: 2rem;
            font-weight: bold;
        }
        .achievement-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .progress {
            height: 20px;
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
                        <a class="nav-link" href="{{ route('progress-rate') }}">
                            <i class="bi bi-graph-up me-2"></i>進捗率
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('individual-consumption') }}">
                            <i class="bi bi-person-fill me-2"></i>個人別消化率
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h1 class="mb-4">Redmine 工数進捗率レポート - 個人別チケット消化率</h1>
                
                <!-- フラッシュメッセージ -->
                <div id="flash-message" class="alert d-none mb-3" role="alert"></div>
                
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
                                @foreach($projects as $project)
                                    <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="update-btn" class="btn btn-primary w-100 position-relative">
                                更新
                                <span id="loading-spinner" class="spinner-border spinner-border-sm position-absolute d-none" style="right: 10px;" role="status">
                                    <span class="visually-hidden">読み込み中...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Achievement Rate Chart -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>個人別達成率</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="achievement-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Individual User Cards -->
                <div class="row" id="user-cards-container">
                    <!-- User cards will be dynamically added here -->
                </div>

                <!-- Detailed Table -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>詳細データ</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ユーザー</th>
                                                <th>消化時間</th>
                                                <th>稼働時間</th>
                                                <th>達成率</th>
                                                <th>総チケット数</th>
                                                <th>完了チケット数</th>
                                                <th>消化チケット数</th>
                                                <th>チケット消化率</th>
                                            </tr>
                                        </thead>
                                        <tbody id="stats-table-body">
                                            <!-- Table rows will be dynamically added here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let achievementChart = null;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('update-btn').addEventListener('click', function() {
                fetchAndUpdateData();
            });

            @if(isset($initialData) && !empty($initialData))
                const initialData = @json($initialData);
                updateAchievementChart(initialData);
                updateUserCards(initialData);
                updateStatsTable(initialData);
            @else
                fetchAndUpdateData();
            @endif
        });

        function fetchAndUpdateData() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const projectId = document.getElementById('project-id').value;
            const loadingSpinner = document.getElementById('loading-spinner');
            const flashMessage = document.getElementById('flash-message');
            
            flashMessage.classList.add('d-none');
            flashMessage.classList.remove('alert-success', 'alert-danger');
            flashMessage.textContent = '';
            
            loadingSpinner.classList.remove('d-none');
            
            document.getElementById('update-btn').disabled = true;

            fetch(`/api/individual-consumption-stats?start_date=${startDate}&end_date=${endDate}&project_id=${projectId}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || 'データの取得に失敗しました');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        showFlashMessage('danger', data.message || 'データの取得に失敗しました');
                    } else {
                        showFlashMessage('success', 'データを正常に取得しました');
                        
                        updateAchievementChart(data);
                        updateUserCards(data);
                        updateStatsTable(data);
                    }
                })
                .catch(error => {
                    console.error('個人別消化率データ取得エラー:', error);
                    showFlashMessage('danger', error.message || 'データの取得中にエラーが発生しました');
                })
                .finally(() => {
                    loadingSpinner.classList.add('d-none');
                    
                    document.getElementById('update-btn').disabled = false;
                });
        }
        
        function showFlashMessage(type, message) {
            const flashMessage = document.getElementById('flash-message');
            flashMessage.classList.remove('d-none');
            flashMessage.classList.remove('alert-success', 'alert-danger');
            flashMessage.classList.add(`alert-${type}`);
            flashMessage.textContent = message;
            
            setTimeout(() => {
                flashMessage.classList.add('d-none');
            }, 5000);
        }

        function updateAchievementChart(data) {
            const ctx = document.getElementById('achievement-chart').getContext('2d');
            
            const labels = data.map(item => item.user_name);
            const achievementRates = data.map(item => item.achievement_rate);
            const consumptionRates = data.map(item => item.ticket_consumption_rate);

            if (achievementChart) {
                achievementChart.destroy();
            }

            achievementChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '達成率（消化時間/稼働時間）',
                            data: achievementRates,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'チケット消化率（消化チケット/総チケット）',
                            data: consumptionRates,
                            backgroundColor: 'rgba(0, 123, 255, 0.7)',
                            borderColor: 'rgba(0, 123, 255, 1)',
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
                            max: 100,
                            title: {
                                display: true,
                                text: '率 (%)'
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

        function updateUserCards(data) {
            const container = document.getElementById('user-cards-container');
            container.innerHTML = '';

            data.forEach(user => {
                const card = document.createElement('div');
                card.className = 'col-md-4 mb-4';
                
                let achievementClass = 'bg-success';
                if (user.achievement_rate < 50) {
                    achievementClass = 'bg-danger';
                } else if (user.achievement_rate < 75) {
                    achievementClass = 'bg-warning';
                }

                card.innerHTML = `
                    <div class="card user-card">
                        <div class="card-header">
                            <h5>${user.user_name}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 text-center">
                                    <div class="achievement-rate ${achievementClass} text-white p-2 rounded">${user.achievement_rate}%</div>
                                    <div class="achievement-label">達成率</div>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">消化時間: ${user.consumed_estimated_hours}時間</p>
                                    <p class="mb-1">稼働時間: ${user.working_hours}時間</p>
                                    <p class="mb-1">消化チケット: ${user.consumed_tickets}/${user.total_tickets}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="progress">
                                    <div class="progress-bar ${achievementClass}" role="progressbar" style="width: ${user.achievement_rate}%" 
                                        aria-valuenow="${user.achievement_rate}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.appendChild(card);
            });
        }

        function updateStatsTable(data) {
            const tableBody = document.getElementById('stats-table-body');
            tableBody.innerHTML = '';

            data.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.user_name}</td>
                    <td>${user.consumed_estimated_hours}時間</td>
                    <td>${user.working_hours}時間</td>
                    <td>${user.achievement_rate}%</td>
                    <td>${user.total_tickets}</td>
                    <td>${user.completed_tickets}</td>
                    <td>${user.consumed_tickets}</td>
                    <td>${user.ticket_consumption_rate}%</td>
                `;
                tableBody.appendChild(row);
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</body>
</html>
