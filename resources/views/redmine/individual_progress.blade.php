<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redmine 工数進捗率レポート - 個人別チケット進捗率</title>
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
        .progress-rate {
            font-size: 2rem;
            font-weight: bold;
        }
        .progress-label {
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
                        <a class="nav-link active" href="{{ route('individual-progress') }}">
                            <i class="bi bi-person-fill me-2"></i>個人別進捗率
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h1 class="mb-4">Redmine 工数進捗率レポート - 個人別チケット進捗率</h1>
                
                <!-- フラッシュメッセージ -->
                <div id="flash-message" class="alert d-none mb-3" role="alert"></div>
                
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="start-date" class="form-label">開始月</label>
                            <input type="month" id="start-date" class="form-control" value="{{ date('Y-m', strtotime('-3 months')) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end-date" class="form-label">終了月</label>
                            <input type="month" id="end-date" class="form-control" value="{{ date('Y-m') }}">
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
                                <h5>個人別進捗率</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="progress-chart"></canvas>
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
                                                <th>完了時間</th>
                                                <th>稼働時間</th>
                                                <th>進捗率</th>
                                                <th>総チケット数</th>
                                                <th>完了チケット数</th>
                                                <th>チケット完了率</th>
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
        let progressChart = null;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('update-btn').addEventListener('click', function() {
                fetchAndUpdateData();
            });

            @if(isset($initialData) && !empty($initialData))
                const initialData = @json($initialData);
                updateProgressChart(initialData);
                updateUserCards(initialData);
                updateStatsTable(initialData);
            @else
                fetchAndUpdateData();
            @endif
        });

        function fetchAndUpdateData() {
            let startDate = document.getElementById('start-date').value;
            let endDate = document.getElementById('end-date').value;
            const projectId = document.getElementById('project-id').value;
            const loadingSpinner = document.getElementById('loading-spinner');
            const flashMessage = document.getElementById('flash-message');
            
            if (startDate) {
                startDate = startDate + '-01'; // 月の初日
            }
            if (endDate) {
                const endDateObj = new Date(endDate + '-01');
                const lastDay = new Date(endDateObj.getFullYear(), endDateObj.getMonth() + 1, 0).getDate();
                endDate = endDate + '-' + lastDay; // 月の最終日
            }
            
            flashMessage.classList.add('d-none');
            flashMessage.classList.remove('alert-success', 'alert-danger');
            flashMessage.textContent = '';
            
            loadingSpinner.classList.remove('d-none');
            
            document.getElementById('update-btn').disabled = true;

            fetch(`/api/individual-progress-stats?start_date=${startDate}&end_date=${endDate}&project_id=${projectId}`)
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
                        
                        updateProgressChart(data);
                        updateUserCards(data);
                        updateStatsTable(data);
                    }
                })
                .catch(error => {
                    console.error('個人別進捗率データ取得エラー:', error);
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

        function updateProgressChart(data) {
            const ctx = document.getElementById('progress-chart').getContext('2d');
            
            const labels = data.map(item => item.user_name);
            const progressRates = data.map(item => item.progress_rate);
            const completionRates = data.map(item => item.ticket_completion_rate);

            if (progressChart) {
                progressChart.destroy();
            }

            progressChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '進捗率（完了時間/稼働時間）',
                            data: progressRates,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'チケット完了率（完了チケット/総チケット）',
                            data: completionRates,
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
                
                let progressClass = 'bg-success';
                if (user.progress_rate < 50) {
                    progressClass = 'bg-danger';
                } else if (user.progress_rate < 75) {
                    progressClass = 'bg-warning';
                }

                card.innerHTML = `
                    <div class="card user-card" data-user-id="${user.user_id}" style="cursor: pointer;">
                        <div class="card-header">
                            <h5>${user.user_name}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 text-center">
                                    <div class="progress-rate ${progressClass} text-white p-2 rounded">${user.progress_rate}%</div>
                                    <div class="progress-label">進捗率</div>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">完了時間: ${parseFloat(user.consumed_estimated_hours).toFixed(2)}時間</p>
                                    <p class="mb-1">稼働時間: ${parseFloat(user.working_hours).toFixed(2)}時間</p>
                                    <p class="mb-1">完了チケット: ${user.completed_tickets}/${user.total_tickets}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="progress">
                                    <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${user.progress_rate}%" 
                                        aria-valuenow="${user.progress_rate}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.appendChild(card);
                
                card.querySelector('.card.user-card').addEventListener('click', function() {
                    showUserTicketDetails(user.user_id, user.user_name);
                });
            });
        }
        
        function showUserTicketDetails(userId, userName) {
            let startDate = document.getElementById('start-date').value;
            let endDate = document.getElementById('end-date').value;
            const projectId = document.getElementById('project-id').value;
            const loadingSpinner = document.getElementById('loading-spinner');
            
            if (startDate) {
                startDate = startDate + '-01'; // 月の初日
            }
            if (endDate) {
                const endDateObj = new Date(endDate + '-01');
                const lastDay = new Date(endDateObj.getFullYear(), endDateObj.getMonth() + 1, 0).getDate();
                endDate = endDate + '-' + lastDay; // 月の最終日
            }
            
            const modalHtml = `
                <div class="modal fade" id="ticketDetailsModal" tabindex="-1" aria-labelledby="ticketDetailsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="ticketDetailsModalLabel">${userName}のチケット詳細</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center my-5" id="modal-loading">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">読み込み中...</span>
                                    </div>
                                    <p class="mt-2">チケット情報を取得中...</p>
                                </div>
                                <div id="ticket-details-container" class="d-none">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>チケットID</th>
                                                    <th>件名</th>
                                                    <th>ステータス</th>
                                                    <th>予定工数</th>
                                                    <th>実績時間</th>
                                                    <th>完了</th>
                                                    <th>完了判定</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ticket-details-body">
                                                <!-- チケット詳細がここに動的に追加されます -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const existingModal = document.getElementById('ticketDetailsModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            const modal = new bootstrap.Modal(document.getElementById('ticketDetailsModal'));
            modal.show();
            
            fetch(`/api/user-ticket-details?user_id=${userId}&start_date=${startDate}&end_date=${endDate}&project_id=${projectId}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || 'チケット詳細の取得に失敗しました');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('modal-loading').classList.add('d-none');
                    document.getElementById('ticket-details-container').classList.remove('d-none');
                    
                    const tableBody = document.getElementById('ticket-details-body');
                    tableBody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">チケットが見つかりませんでした</td></tr>';
                        return;
                    }
                    
                    data.forEach(ticket => {
                        const row = document.createElement('tr');
                        const isCompleted = ticket.is_completed ? '✓' : '✗';
                        const isCompletedStatus = ticket.is_completed ? '✓' : '✗';
                        
                        row.innerHTML = `
                            <td>${ticket.id}</td>
                            <td>${ticket.subject}</td>
                            <td>${ticket.status}</td>
                            <td>${parseFloat(ticket.estimated_hours).toFixed(2)}時間</td>
                            <td>${parseFloat(ticket.spent_hours).toFixed(2)}時間</td>
                            <td>${isCompleted}</td>
                            <td>${isCompletedStatus}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('チケット詳細取得エラー:', error);
                    document.getElementById('modal-loading').classList.add('d-none');
                    document.getElementById('ticket-details-container').classList.remove('d-none');
                    
                    const tableBody = document.getElementById('ticket-details-body');
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">エラーが発生しました: ${error.message}</td></tr>`;
                });
        }

        function updateStatsTable(data) {
            const tableBody = document.getElementById('stats-table-body');
            tableBody.innerHTML = '';

            data.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.user_name}</td>
                    <td>${parseFloat(user.consumed_estimated_hours).toFixed(2)}時間</td>
                    <td>${parseFloat(user.working_hours).toFixed(2)}時間</td>
                    <td>${user.progress_rate}%</td>
                    <td>${user.total_tickets}</td>
                    <td>${user.completed_tickets}</td>
                    <td>${user.ticket_completion_rate}%</td>
                `;
                tableBody.appendChild(row);
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
</body>
</html>
