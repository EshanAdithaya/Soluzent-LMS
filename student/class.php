<?php
require_once __DIR__ . '/../asset/php/config.php';
require_once __DIR__ . '/../asset/php/db.php';

// Get class ID from URL
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if class ID is valid
if ($class_id <= 0) {
    die('
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Invalid Class</h1>
                <p class="text-gray-600 mb-4">The class you are looking for is not available.</p>
                <a href="dashboard.php" class="text-blue-500 hover:text-blue-600">Return to Dashboard</a>
            </div>
        </div>
    ');
}

// Check if class exists
$stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
if (!$stmt->fetch()) {
    die('
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Class Not Found</h1>
                <p class="text-gray-600 mb-4">The class you are looking for does not exist.</p>
                <a href="dashboard.php" class="text-blue-500 hover:text-blue-600">Return to Dashboard</a>
            </div>
        </div>
    ');
}

// Verify student's enrollment in this class
$stmt = $pdo->prepare("
    SELECT e.* 
    FROM enrollments e 
    WHERE e.student_id = ? AND e.class_id = ?
");
$stmt->execute([$_SESSION['user_id'], $class_id]);
if (!$stmt->fetch()) {
    die('
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Not Permitted</h1>
                <p class="text-gray-600 mb-4">You are not enrolled in this class.</p>
                <a href="dashboard.php" class="text-blue-500 hover:text-blue-600">Return to Dashboard</a>
            </div>
        </div>
    ');
}

// Get class details
$stmt = $pdo->prepare("
    SELECT c.*, u.name as teacher_name
    FROM classes c
    LEFT JOIN users u ON c.created_by = u.id
    WHERE c.id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch();
echo "<script>console.log('Class details: " . json_encode($class) . "');</script>";

// Get class materials grouped by week
$stmt = $pdo->prepare("
    SELECT * FROM materials 
    WHERE class_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$class_id]);
$materials = $stmt->fetchAll();

// Group materials by week
$materialsByWeek = [];
foreach ($materials as $material) {
    $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($material['created_at'])));
    $materialsByWeek[$weekStart][] = $material;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($class['name']) ?> - EduPortal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Plyr CSS and JS -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <style>
        .week-divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }
        .week-divider::before,
        .week-divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: calc(50% - 100px);
            height: 1px;
            background-color: #e5e7eb;
        }
        .week-divider::before {
            left: 0;
        }
        .week-divider::after {
            right: 0;
        }
        .plyr {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden; 
            -webkit-user-select: none;
             -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Hide the default right-click menu */
        .plyr__video-wrapper::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-gray-50">
<?php include_once 'student-header.php';?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Class Header -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($class['name']) ?></h2>
                <p class="mt-1 text-sm text-gray-500">
                    Instructor: <?= htmlspecialchars($class['teacher_name']) ?>
                </p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <p class="text-gray-700"><?= htmlspecialchars($class['description']) ?></p>
            </div>
        </div>

        <!-- Materials Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium text-gray-900">Course Materials</h3>
            </div>
            <div class="border-t border-gray-200">
                <?php if (empty($materialsByWeek)): ?>
                    <div class="px-4 py-5 sm:px-6 text-gray-500">
                        No materials available yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($materialsByWeek as $weekStart => $weekMaterials): ?>
                        <div class="week-divider">
                            <span class="bg-white px-4 text-sm text-gray-500">
                                Week of <?= date('F j, Y', strtotime($weekStart)) ?>
                            </span>
                        </div>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($weekMaterials as $material): ?>
                                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                                    <div>
                                        <div class="flex items-center">
                                            <!-- Icon based on material type -->
                                            <div class="flex-shrink-0">
                                                <?php if ($material['type'] === 'pdf'): ?>
                                                    <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                <?php elseif ($material['type'] === 'link'): ?>
                                                    <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                <?php elseif ($material['type'] === 'youtubeLink'): ?>
                                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($material['title']) ?></h4>
                                                <p class="text-sm text-gray-500">
                                                    Added on <?= date('F j, Y', strtotime($material['created_at'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <?php if ($material['type'] === 'youtubeLink'): ?>
                                            <?php
                                                // Extract video ID if not already stored
                                                if (!$material['video_id']) {
                                                    $video_id = '';
                                                    $url = $material['content'];
                                                    
                                                    // Pattern for youtu.be format
                                                    if (preg_match('/^.*youtu\.be\/([^\/\?\&]+)/', $url, $matches)) {
                                                        $video_id = $matches[1];
                                                    }
                                                    // Pattern for youtube.com format
                                                    else if (preg_match('/^.*youtube\.com\/(?:watch\?v=|embed\/|v\/)([^\/\?\&]+)/', $url, $matches)) {
                                                        $video_id = $matches[1];
                                                    }
                                                    
                                                    // Remove any additional parameters
                                                    $video_id = strtok($video_id, '?&');
                                                } else {
                                                    $video_id = $material['video_id'];
                                                }
                                            ?>
                                            <div class="flex justify-center mt-4">
                                                <div class="plyr__video-embed" id="player-<?= htmlspecialchars($material['id']) ?>">
                                                    <iframe
                                                        src="https://www.youtube.com/embed/<?= htmlspecialchars($video_id) ?>?origin=<?= urlencode(APP_URL) ?>&amp;iv_load_policy=3&amp;modestbranding=1&amp;playsinline=1&amp;showinfo=0&amp;rel=0&amp;enablejsapi=1&amp;nocookie=1"
                                                        allowfullscreen
                                                        allowtransparency
                                                        allow="autoplay"
                                                    ></iframe>
                                                </div>
                                            </div>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    // Initialize Plyr with restricted controls
                                                    const player = new Plyr('#player-<?= htmlspecialchars($material['id']) ?>', {
                                                        controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'fullscreen'],
                                                        settings: ['quality', 'speed'],
                                                        youtube: {
                                                            noCookie: true,
                                                            rel: 0,
                                                            showinfo: 0,
                                                            iv_load_policy: 3,
                                                            modestbranding: 1,
                                                            playsinline: 1
                                                        },
                                                        tooltips: { controls: false, seek: false }
                                                    });

                                                    // Prevent right-click on the player
                                                    const playerElement = document.getElementById('player-<?= htmlspecialchars($material['id']) ?>');
                                                    playerElement.addEventListener('contextmenu', (e) => {
                                                        e.preventDefault();
                                                        return false;
                                                    });
                                                });
                                            </script>
                                        <?php elseif ($material['type'] === 'image'): ?>
                                            <div class="mt-4">
                                                <img 
                                                    src="<?= APP_URL ?>/uploads/materials/<?= htmlspecialchars($material['content']) ?>" 
                                                    alt="<?= htmlspecialchars($material['title']) ?>"
                                                    class="max-w-full h-auto rounded-lg shadow-lg"
                                                >
                                            </div>
                                        <?php elseif ($material['type'] === 'pdf'): ?>
                                            <a href="<?= APP_URL ?>/uploads/materials/<?= htmlspecialchars($material['content']) ?>" 
                                               download
                                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                                Download PDF
                                            </a>
                                        <?php elseif ($material['type'] === 'link'): ?>
                                            <a href="<?= htmlspecialchars($material['content']) ?>" 
                                            target="_blank"
                                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200">
                                                Open Link
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Global right-click prevention on video elements
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('.plyr')) {
                e.preventDefault();
                return false;
            }
        });

        // Additional security measures
        window.addEventListener('keydown', function(e) {
            // Prevent common keyboard shortcuts for viewing source/inspecting
            if ((e.ctrlKey && (e.key === 'u' || e.key === 's')) || 
                (e.key === 'F12') || 
                (e.ctrlKey && e.shiftKey && e.key === 'i')) {
                e.preventDefault();
                return false;
            }
        });

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                const response = await fetch('../asset/php/logout.php');
                const data = await response.json();
                if (data.success) {
                    console.log('Logout successful');
                    window.location.href = '../login.php';
                } else {
                    console.log('Logout failed');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>