<?php
/**
 * Landing Page - Educational Platform
 * Theme: Professional Blue & White with Floating Animations
 * Multi-language support: Arabic (RTL), French, English
 */

// Load all dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], AVAILABLE_LANGS)) {
    setLanguage($_GET['lang']);
    header('Location: ' . SITE_URL);
    exit;
}

$currentLang = getCurrentLang();
$direction = getDirection();

// Fetch educational levels
try {
    $levels = db_all("SELECT * FROM levels ORDER BY display_order ASC");
    
    // Get platform statistics
    $stats = db_row("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
            (SELECT COUNT(*) FROM lessons WHERE is_published = 1) as total_lessons,
            (SELECT COUNT(*) FROM subjects) as total_subjects
    ");
    
    if (!$stats) {
        $stats = ['total_students' => 0, 'total_lessons' => 0, 'total_subjects' => 0];
    }
} catch (Exception $e) {
    error_log('[INDEX] Database error: ' . $e->getMessage());
    $levels = [];
    $stats = ['total_students' => 0, 'total_lessons' => 0, 'total_subjects' => 0];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app_name'); ?> - <?php echo t('hero_title'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Professional Blue & White Theme with Floating Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        @keyframes floatSlow {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(10px, -15px); }
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.05); }
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            position: relative;
            overflow: hidden;
        }
        
        .gradient-bg::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,0.3) 0%, transparent 70%);
            top: -250px;
            right: -250px;
            animation: pulse-glow 8s ease-in-out infinite;
        }
        
        .gradient-bg::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(96,165,250,0.2) 0%, transparent 70%);
            bottom: -200px;
            left: -200px;
            animation: pulse-glow 6s ease-in-out infinite 2s;
        }
        
        .float-icon {
            animation: float 6s ease-in-out infinite;
        }
        
        .float-icon-slow {
            animation: floatSlow 8s ease-in-out infinite;
        }
        
        .card-hover {
            transition: all 0.3s ease;
            background: white;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(29,78,216,0.15);
        }
        
        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(29,78,216,0.4);
        }
        
        .btn-outline {
            border: 2px solid white;
            transition: all 0.3s ease;
        }
        
        .btn-outline:hover {
            background: white;
            color: #1d4ed8;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-white">

<!-- Navigation Bar -->
<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <i class="fas fa-graduation-cap text-3xl text-blue-700"></i>
                <span class="<?php echo $direction === 'rtl' ? 'mr-3' : 'ml-3'; ?> text-xl font-bold text-gray-800">
                    <?php echo t('app_name'); ?>
                </span>
            </div>
            
            <div class="hidden md:flex items-center space-x-6 <?php echo $direction === 'rtl' ? 'space-x-reverse' : ''; ?>">
                <?php if (isLoggedIn()): ?>
                    <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin', 'staff'], true)): ?>
                        <a href="admin/dashboard.php" class="text-gray-700 hover:text-blue-700 transition font-medium">
                            <?php echo t('admin_panel'); ?>
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-700 transition font-medium">
                            <?php echo t('dashboard'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="text-gray-700 hover:text-blue-700 transition font-medium">
                        <?php echo t('logout'); ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-blue-700 transition font-medium">
                        <?php echo t('login'); ?>
                    </a>
                    <a href="register.php" class="bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition font-semibold">
                        <?php echo t('register'); ?>
                    </a>
                <?php endif; ?>
                
                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center text-gray-700 hover:text-blue-700">
                        <i class="fas fa-globe"></i>
                        <span class="uppercase <?php echo $direction === 'rtl' ? 'mr-2' : 'ml-2'; ?>"><?php echo $currentLang; ?></span>
                        <i class="fas fa-chevron-down text-xs <?php echo $direction === 'rtl' ? 'mr-1' : 'ml-1'; ?>"></i>
                    </button>
                    <div x-show="open" @click.away="open = false" 
                         class="absolute <?php echo $direction === 'rtl' ? 'left-0' : 'right-0'; ?> mt-2 bg-white rounded-lg shadow-lg py-2 w-32">
                        <a href="?lang=ar" class="block px-4 py-2 hover:bg-blue-50 <?php echo $currentLang === 'ar' ? 'bg-blue-100' : ''; ?>">العربية</a>
                        <a href="?lang=fr" class="block px-4 py-2 hover:bg-blue-50 <?php echo $currentLang === 'fr' ? 'bg-blue-100' : ''; ?>">Français</a>
                        <a href="?lang=en" class="block px-4 py-2 hover:bg-blue-50 <?php echo $currentLang === 'en' ? 'bg-blue-100' : ''; ?>">English</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section with Floating Elements -->
<section class="gradient-bg text-white py-20 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="float-icon mb-6">
            <i class="fas fa-graduation-cap text-6xl opacity-90"></i>
        </div>
        <h1 class="text-4xl md:text-6xl font-bold mb-6"><?php echo t('hero_title'); ?></h1>
        <p class="text-xl md:text-2xl mb-8 text-blue-100"><?php echo t('hero_subtitle'); ?></p>
        <div class="flex justify-center gap-4 flex-wrap">
            <a href="#levels" class="btn-primary px-8 py-3 rounded-lg font-semibold text-white">
                <?php echo t('hero_cta'); ?>
            </a>
            <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn-outline px-8 py-3 rounded-lg font-semibold text-white">
                <?php echo t('register'); ?>
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Statistics with Floating Animation -->
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="stat-card rounded-lg p-6 float-icon">
                <div class="text-4xl font-bold text-blue-700"><?php echo number_format($stats['total_students']); ?>+</div>
                <div class="text-gray-600 mt-2 font-medium"><?php echo t('students'); ?></div>
            </div>
            <div class="stat-card rounded-lg p-6 float-icon-slow">
                <div class="text-4xl font-bold text-blue-700"><?php echo number_format($stats['total_lessons']); ?>+</div>
                <div class="text-gray-600 mt-2 font-medium"><?php echo t('lessons'); ?></div>
            </div>
            <div class="stat-card rounded-lg p-6 float-icon">
                <div class="text-4xl font-bold text-blue-700"><?php echo number_format($stats['total_subjects']); ?>+</div>
                <div class="text-gray-600 mt-2 font-medium"><?php echo t('subjects'); ?></div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section - Clean White Background -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-12">
            <?php echo t('features_title'); ?>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="text-center card-hover rounded-lg p-6 border border-gray-100">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-language text-3xl text-blue-700"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo t('feature_multilang_title'); ?></h3>
                <p class="text-gray-600"><?php echo t('feature_multilang_desc'); ?></p>
            </div>
            
            <div class="text-center card-hover rounded-lg p-6 border border-gray-100">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trophy text-3xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo t('feature_gamification_title'); ?></h3>
                <p class="text-gray-600"><?php echo t('feature_gamification_desc'); ?></p>
            </div>
            
            <div class="text-center card-hover rounded-lg p-6 border border-gray-100">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-book-open text-3xl text-blue-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo t('feature_content_title'); ?></h3>
                <p class="text-gray-600"><?php echo t('feature_content_desc'); ?></p>
            </div>
            
            <div class="text-center card-hover rounded-lg p-6 border border-gray-100">
                <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-brain text-3xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo t('feature_quizzes_title'); ?></h3>
                <p class="text-gray-600"><?php echo t('feature_quizzes_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Educational Levels Section -->
<section id="levels" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-4">
            <?php echo t('choose_level'); ?>
        </h2>
        <p class="text-center text-gray-600 mb-12 text-lg">
            <?php echo t('select_your_level_desc'); ?>
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($levels as $level): ?>
            <a href="register.php?level=<?php echo $level['id']; ?>" 
               class="block bg-white rounded-xl shadow-lg overflow-hidden card-hover border border-gray-100">
                <div class="p-8">
                    <div class="text-center mb-6">
                        <div class="bg-gradient-to-r from-blue-700 to-blue-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 float-icon">
                            <i class="fas fa-school text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">
                            <?php echo $level['name_' . $currentLang]; ?>
                        </h3>
                    </div>
                    
                    <?php
                    $subjectStmt = db()->prepare("SELECT COUNT(*) as count FROM subjects WHERE level_id = ?");
                    $subjectStmt->execute([$level['id']]);
                    $subjectCount = $subjectStmt->fetch()['count'];
                    ?>
                    
                    <div class="space-y-3 text-gray-600 mb-6">
                        <div class="flex items-center justify-between">
                            <span><?php echo t('subjects'); ?>:</span>
                            <span class="font-semibold text-blue-700"><?php echo $subjectCount; ?></span>
                        </div>
                    </div>
                    
                    <div class="bg-blue-700 text-white text-center py-3 rounded-lg font-semibold hover:bg-blue-800 transition">
                        <?php echo t('start_learning'); ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h3 class="text-2xl font-bold mb-4"><?php echo t('app_name'); ?></h3>
            <p class="text-gray-400 mb-6"><?php echo t('footer_description'); ?></p>
            <p class="text-gray-500">&copy; 2024 <?php echo t('app_name'); ?>. <?php echo t('all_rights_reserved'); ?></p>
        </div>
    </div>
</footer>

</body>
</html>
