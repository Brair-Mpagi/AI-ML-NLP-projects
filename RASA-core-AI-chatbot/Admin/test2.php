<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Chatbot Panel</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="images/mmu_logo_- no bg.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="css/materialize.css" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/style-mob.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f4f7fa 0%, #e0e7ff 100%);
            font-family: 'Poppins', sans-serif;
            color: #1a1a2e;
        }
        /* Sidebar Styling */
        .sb2-1 {
            background: #1a1a2e;
            border-radius: 0 15px 15px 0;
            transition: all 0.3s ease;
        }
        .sb2-13 ul.collapsible li a {
            color: #d1d5db;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .sb2-13 ul.collapsible li a:hover {
            background: #3b82f6;
            color: #fff;
            transform: translateX(5px);
        }
        .sb2-13 ul.collapsible li a i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .left-sub-menu li a {
            padding-left: 50px;
            color: #9ca3af;
        }
        .left-sub-menu li a:hover {
            color: #fff;
            background: #2563eb;
        }

        /* Header */
        .sb1 {
            background: linear-gradient(90deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 15px 0;
            border-bottom: 2px solid #2563eb;
        }
        .sb1 h3 {
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }
        .admin-notification {
            position: relative;
            margin-left: 15px;
        }
        .admin-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 0.8rem;
        }

        /* Widgets */
        .widget-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .widget-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .widget-card i {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 2rem;
            opacity: 0.2;
            color: #3b82f6;
        }
        .widget-card h5 {
            font-size: 1.1rem;
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .widget-card p {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e3a8a;
        }

        /* Chart Containers */
        .chart-container {
            background: #fff;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .chart-container h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a2e;
            display: flex;
            align-items: center;
        }
        .chart-container h2 i {
            margin-right: 10px;
            color: #3b82f6;
        }
        .text-insight {
            font-size: 0.9rem;
            color: #4b5563;
            margin-top: 15px;
        }
        .pie-chart {
            max-width: 250px;
            margin: 0 auto;
        }

        /* Breadcrumbs */
        .sb2-2-2 ul li a {
            color: #4b5563;
            transition: color 0.3s ease;
        }
        .sb2-2-2 ul li a:hover {
            color: #3b82f6;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sb2-1 {
                position: relative;
                border-radius: 0;
            }
            .widget-card {
                margin-bottom: 15px;
            }
            .chart-container {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <!--== MAIN CONTAINER ==-->
    <div class="container-fluid sb1">
        <div class="row">
            <h3>
                <i class="fas fa-robot mr-2"></i> Campus Query Chatbot Admin Panel
                <span class="admin-notification" id="notification">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <span class="admin-badge" id="not-yet-count"></span>
                </span>
            </h3>
        </div>
    </div>

    <!--== BODY CONTAINER ==-->
    <div class="container-fluid sb2">
        <div class="row">
            <div class="sb2-1">
                <!--== USER INFO ==-->
                <div class="sb2-12">
                    <ul>
                        <li><img src="images/mmu_logo_- no bg.png" alt="Logo" style="max-width: 80px;"></li>
                        <li><h5>B - Codz <span>Kampala, Ug</span></h5></li>
                    </ul>
                </div>
                <!--== LEFT MENU ==-->
                <div class="sb2-13">
                    <ul class="collapsible" data-collapsible="accordion">
                        <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="admin-setting.php"><i class="fas fa-user-cog"></i> Account Information</a></li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-chart-line"></i> Chatbot Usage Analytics</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatlogs.php"><i class="fas fa-history"></i> Chatlogs</a></li>
                                    <li><a href="user_interactions.php"><i class="fas fa-users"></i> User Interaction Data</a></li>
                                    <li><a href="FAQ.php"><i class="fas fa-question-circle"></i> Frequently Asked Questions</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-database"></i> AI Chatbot Model </a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="chatbot-data.php"><i class="fas fa-table"></i> AI Chatbot Model </a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-comment-alt"></i> Feedback</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-inbox"></i> Pushed Queries</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="pushed_query.php"><i class="fas fa-envelope"></i> All Queries</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="javascript:void(0)" class="collapsible-header"><i class="fas fa-file-alt"></i> Report Overview</a>
                            <div class="collapsible-body left-sub-menu">
                                <ul>
                                    <li><a href="report.php"><i class="fas fa-chart-bar"></i> Report</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="http://127.0.0.1:5000"><i class="fas fa-robot"></i> Chatbot</a></li>
                        <li by class="collapsible-header"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>

            <!--== BODY INNER CONTAINER ==-->
            <div class="sb2-2">
                <!--== Breadcrumbs ==-->
                <div class="sb2-2-2">
                    <ul>
                        <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                        <li class="active-bre"><a href="#"> Dashboard</a></li>
                        <li class="page-back"><a href="admin.php"><i class="fas fa-arrow-left"></i> Back</a></li>
                    </ul>
                </div>

                <!--== DASHBOARD INFO ==-->
                <div class="container" style="width: 100%;">
                    <h1 class="text-center mb-4"><i class="fas fa-chart-pie mr-2"></i> Chatbot Dashboard</h1>

                    <!-- Widgets -->
                    <div class="row mb-4" style="padding: 20px; display: flex; justify-content: center;">
                        <div class="col-md-3 col-sm-4 mb-3">
                            <div class="widget-card">
                                <i class="fas fa-users"></i>
                                <h5>Total Users</h5>
                                <p><?php echo $total_users; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="widget-card">
                                <i class="fas fa-user-check"></i>
                                <h5>Active Users</h5>
                                <p><?php echo $active_users_5min; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="widget-card">
                                <i class="fas fa-exchange-alt"></i>
                                <h5>Interactions in past 24hrs</h5>
                                <p><?php echo $active_users_24hr; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4 mb-3">
                            <div class="widget-card">
                                <i class="fas fa-thumbs-up"></i>
                                <h5>Feedback Today</h5>
                                <p><?php echo $feedback_today; ?></p>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-4 mb-3">
                            <div class="widget-card">
                                <i class="fas fa-hourglass-half"></i>
                                <h5>Awaiting Queries</h5>
                                <p><?php echo $pushed_awaiting; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Chatlogs: Queries Per Day -->
                    <section class="chart-container">
                        <h2><i class="fas fa-calendar-day"></i> Chatlogs: Queries Per Day</h2>
                        <canvas id="chatlogsPerDayChart"></canvas>
                        <div class="text-insight">
                            <p><strong>Overview:</strong> Tracks daily query volume.</p>
                            <p><strong>Trends:</strong> Peak on <?php echo $chatlogs_per_day[array_search(max(array_column($chatlogs_per_day, 'count')), array_column($chatlogs_per_day, 'count'))]['date'] ?? 'N/A'; ?> (<?php echo max(array_column($chatlogs_per_day, 'count')) ?? 0; ?> queries).</p>
                            <p><strong>Stats:</strong> Total: <?php echo array_sum(array_column($chatlogs_per_day, 'count')); ?>, Avg/day: <?php echo count($chatlogs_per_day) ? round(array_sum(array_column($chatlogs_per_day, 'count')) / count($chatlogs_per_day), 1) : 0; ?>.</p>
                        </div>
                    </section>

                    <!-- Chatlogs: Queries by Hour -->
                    <section class="chart-container">
                        <h2><i class="fas fa-clock"></i> Chatlogs: Queries by Hour</h2>
                        <canvas id="chatlogsByHourChart"></canvas>
                        <div class="text-insight">
                            <p><strong>Overview:</strong> Displays query frequency by hour (0-23).</p>
                            <p><strong>Peak Activity Range:</strong> Users are most active between <?php echo $time_range; ?> (<?php echo $max_range_count; ?> queries).</p>
                            <p><strong>Total Queries:</strong> <?php echo $total_queries_hour; ?> queries in the last 24 hours.</p>
                            <p><strong>Insight:</strong> 
                                <?php echo ($peak_range['start_hour'] !== null && $peak_range['end_hour'] !== null) 
                                    ? "User activity peaks during $time_range. Consider enhancing support during these hours."
                                    : "No significant peak range detected. Activity is evenly distributed."; ?>
                            </p>
                        </div>
                    </section>

                    <div style="display: flex; justify-content: center; padding: 10px;">
                        <!-- FAQ Frequency: Top 5 Queries -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2><i class="fas fa-question"></i> FAQ Frequency: Top 5 Queries</h2>
                            <canvas id="faqFrequencyChart"></canvas>
                            <div class="text-insight">
                                <p><strong>Overview:</strong> Displays the top 5 most frequent queries.</p>
                                <p><strong>Insights:</strong> "<?php echo $faq_frequency[0]['query'] ?? 'N/A'; ?>" leads (<?php echo $faq_frequency[0]['frequency'] ?? 0; ?> times).</p>
                                <p><strong>Stats:</strong> Total frequency: <?php echo array_sum(array_column($faq_frequency, 'frequency')); ?>.</p>
                            </div>
                        </section>

                        <!-- FAQ Cache: Top 5 Cached Answers -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2><i class="fas fa-cache"></i> FAQ Cache: Top 5 Cached Answers</h2>
                            <canvas id="faqCacheChart"></canvas>
                            <div class="text-insight">
                                <p><strong>Overview:</strong> Shows the most frequent cached answers.</p>
                                <p><strong>Insights:</strong> "<?php echo substr($faq_cache_answers[0]['answer'] ?? 'N/A', 0, 20); ?>..." tops the list (<?php echo $faq_cache_answers[0]['count'] ?? 0; ?> times).</p>
                                <p><strong>Stats:</strong> Total of top 5: <?php echo array_sum(array_column($faq_cache_answers, 'count')); ?>.</p>
                            </div>
                        </section>
                    </div>

                    <div style="display: flex; justify-content: center; padding: 10px;">
                        <!-- Feedback: Like vs Dislike -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2><i class="fas fa-thumbs-up"></i> Feedback: Like vs Dislike</h2>
                            <div class="pie-chart">
                                <canvas id="feedbackChart"></canvas>
                            </div>
                            <div class="text-insight">
                                <p><strong>Overview:</strong> Distribution of feedback types.</p>
                                <p><strong>Insights:</strong> Dislikes (<?php echo $feedback_counts[0]['count'] ?? 0; ?>) vs Likes (<?php echo $feedback_counts[1]['count'] ?? 0; ?>).</p>
                                <p><strong>Stats:</strong> Total: <?php echo array_sum(array_column($feedback_counts, 'count')); ?>, Like %: <?php echo array_sum(array_column($feedback_counts, 'count')) ? round(($feedback_counts[0]['count'] ?? 0) / array_sum(array_column($feedback_counts, 'count')) * 100, 1) : 0; ?>%.</p>
                            </div>
                        </section>

                        <!-- Feedback: Over Time -->
                        <section class="chart-container" style="margin: 10px;">
                            <h2><i class="fas fa-chart-line"></i> Feedback: Over Time</h2>
                            <canvas id="feedbackOverTimeChart"></canvas>
                            <div class="text-insight">
                                <p><strong>Overview:</strong> Tracks feedback sentiment over time.</p>
                                <p><strong>Trends:</strong> Consistent likes with occasional dislikes (e.g., <?php echo $feedback_over_time[0]['date'] ?? 'N/A'; ?>).</p>
                                <p><strong>Stats:</strong> Total: <?php echo array_sum(array_column($feedback_over_time, 'count')); ?>.</p>
                            </div>
                        </section>
                    </div>

                    <!-- User: Session Duration vs Queries -->
                    <section class="chart-container">
                        <h2><i class="fas fa-user-clock"></i> User: Session Duration vs Queries</h2>
                        <canvas id="userChart"></canvas>
                        <div class="text-insight">
                            <p><strong>Overview:</strong> Compares session duration (seconds) to query count.</p>
                            <p><strong>Insights:</strong> Longest session: <?php echo max(array_column($user_sessions, 'session_duration') ?: [0]); ?>s with <?php echo max(array_column($user_sessions, 'number_of_queries') ?: [0]); ?> queries.</p>
                            <p><strong>Stats:</strong> Avg duration: <?php echo count($user_sessions) ? round(array_sum(array_column($user_sessions, 'session_duration')) / count($user_sessions), 1) : 0; ?>s, Avg queries: <?php echo count($user_sessions) ? round(array_sum(array_column($user_sessions, 'number_of_queries')) / count($user_sessions), 1) : 0; ?>.</p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <script>
        // Notification update function
        function updateNotificationCount() {
            fetch('fetch_queries.php')
                .then(response => response.json())
                .then(data => {
                    const notYetCount = document.getElementById('not-yet-count');
                    if (notYetCount) {
                        if (data.not_yet_count > 0) {
                            notYetCount.textContent = data.not_yet_count;
                            notYetCount.style.display = 'inline';
                        } else {
                            notYetCount.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error fetching notification count:', error));
        }

        updateNotificationCount();
        setInterval(updateNotificationCount, 60000);

        // Common Chart.js Options
        const commonOptions = {
            plugins: {
                legend: { labels: { font: { size: 12, family: 'Poppins' } } },
                tooltip: { backgroundColor: '#1a1a2e', titleFont: { family: 'Poppins' }, bodyFont: { family: 'Poppins' } }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        };

        // Chart: Queries Per Day (Smoothed)
        const chatlogsPerDayCtx = document.getElementById('chatlogsPerDayChart').getContext('2d');
        new Chart(chatlogsPerDayCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chatlogs_per_day, 'date')); ?>,
                datasets: [{
                    label: 'Queries',
                    data: <?php echo json_encode(array_column($chatlogs_per_day, 'count')); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4, // Smooth curves
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Queries' } },
                    x: { title: { display: true, text: 'Date' } }
                }
            }
        });

        // Chart: Queries by Hour (Rounded Bars)
        const chatlogsByHourCtx = document.getElementById('chatlogsByHourChart').getContext('2d');
        new Chart(chatlogsByHourCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($h) { return sprintf("%02d:00", $h['hour']); }, $chatlogs_by_hour)); ?>,
                datasets: [{
                    label: 'Number of Queries',
                    data: <?php echo json_encode(array_column($chatlogs_by_hour, 'count')); ?>,
                    backgroundColor: '#1e3a8a',
                    borderRadius: 10 // Rounded bars
                }]
            },
            options: {
                ...commonOptions,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Number of Queries' } },
                    y: { title: { display: true, text: 'Hour of Day (24-hour)' } }
                }
            }
        });

        // Chart: FAQ Frequency (Rounded Bars)
        const faqFrequencyCtx = document.getElementById('faqFrequencyChart').getContext('2d');
        new Chart(faqFrequencyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($faq_frequency, 'query')); ?>,
                datasets: [{
                    label: 'Frequency',
                    data: <?php echo json_encode(array_column($faq_frequency, 'frequency')); ?>,
                    backgroundColor: '#3b82f6',
                    borderRadius: 10
                }]
            },
            options: {
                ...commonOptions,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Frequency' } } }
            }
        });

        // Chart: FAQ Cache (Rounded Bars)
        const faqCacheCtx = document.getElementById('faqCacheChart').getContext('2d');
        new Chart(faqCacheCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($row) { return substr($row['answer'], 0, 20) . '...'; }, $faq_cache_answers)); ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode(array_column($faq_cache_answers, 'count')); ?>,
                    backgroundColor: '#1a1a2e',
                    borderRadius: 10
                }]
            },
            options: {
                ...commonOptions,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Count' } } }
            }
        });

        // Chart: Feedback Pie
        const feedbackCtx = document.getElementById('feedbackChart').getContext('2d');
        new Chart(feedbackCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($feedback_counts, 'feedback_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($feedback_counts, 'count')); ?>,
                    backgroundColor: ['#e94560', '#3b82f6'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                ...commonOptions,
                maintainAspectRatio: false,
                responsive: true
            }
        });

        // Chart: Feedback Over Time (Smoothed)
        const feedbackOverTimeCtx = document.getElementById('feedbackOverTimeChart').getContext('2d');
        const dates = [...new Set(<?php echo json_encode(array_column($feedback_over_time, 'date')); ?>)];
        const likes = dates.map(date => {
            const row = <?php echo json_encode($feedback_over_time); ?>.find(r => r.date === date && r.feedback_type === 'like');
            return row ? row.count : 0;
        });
        const dislikes = dates.map(date => {
            const row = <?php echo json_encode($feedback_over_time); ?>.find(r => r.date === date && r.feedback_type === 'dislike');
            return row ? row.count : 0;
        });
        new Chart(feedbackOverTimeCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    { label: 'Likes', data: likes, borderColor: '#e94560', backgroundColor: 'rgba(233, 69, 96, 0.1)', fill: true, tension: 0.4 },
                    { label: 'Dislikes', data: dislikes, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true, tension: 0.4 }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Count' } },
                    x: { title: { display: true, text: 'Date' } }
                }
            }
        });

        // Chart: User Sessions
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Sessions',
                    data: <?php echo json_encode(array_map(function($row) { return ['x' => $row['session_duration'], 'y' => $row['number_of_queries']]; }, $user_sessions)); ?>,
                    backgroundColor: '#e94560',
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    x: { title: { display: true, text: 'Duration (s)' } },
                    y: { title: { display: true, text: 'Queries' }, beginAtZero: true }
                }
            }
        });
    </script>
    <script src="js/main.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/materialize.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>