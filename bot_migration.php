<?php
// bot_migration.php
require_once __DIR__ . '/lib/db_mysqli.php';

echo "Starting Chat & Voice Bot database migrations...\n";

// Disable foreign key checks temporarily during migration setup if needed
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

// 1. Create bot_nodes table
$sql = "CREATE TABLE IF NOT EXISTS `bot_nodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `node_type` VARCHAR(50) NOT NULL,
  `language_iso` VARCHAR(10) NOT NULL DEFAULT 'en',
  `display_text` TEXT NOT NULL,
  `spoken_text` TEXT NOT NULL,
  `target_action` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bot_nodes_parent` FOREIGN KEY (`parent_id`) REFERENCES `bot_nodes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "bot_nodes table checked/created successfully.\n";
} else {
    echo "Warning/Error creating bot_nodes table: " . $mysqli->error . "\n";
}

// 2. Create bot_sessions table
$sql = "CREATE TABLE IF NOT EXISTS `bot_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_token` VARCHAR(64) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `selected_language` VARCHAR(10) DEFAULT NULL,
  `current_node_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bot_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bot_sessions_node` FOREIGN KEY (`current_node_id`) REFERENCES `bot_nodes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "bot_sessions table checked/created successfully.\n";
} else {
    echo "Warning/Error creating bot_sessions table: " . $mysqli->error . "\n";
}

// 3. Create bot_chat_logs table
$sql = "CREATE TABLE IF NOT EXISTS `bot_chat_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` INT UNSIGNED NOT NULL,
  `sender` ENUM('user', 'bot') NOT NULL,
  `message_content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bot_chat_logs_session` FOREIGN KEY (`session_id`) REFERENCES `bot_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "bot_chat_logs table checked/created successfully.\n";
} else {
    echo "Warning/Error creating bot_chat_logs table: " . $mysqli->error . "\n";
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");

// 4. Programmatically seed bot_nodes table with our brand-new dialogue and option matrix
$nodes = [
    // 1. Language Agnostic Welcome Root Node
    [
        'id' => 1,
        'parent_id' => null,
        'node_type' => 'greeting',
        'language_iso' => 'en',
        'display_text' => 'Welcome to GlobalWays! Please select your preferred language to begin.',
        'spoken_text' => 'Welcome to GlobalWays! Please select your preferred language to begin.',
        'target_action' => null
    ],

    // 2. English Welcome & Voice/Independent Choice Parent Tree
    [
        'id' => 10,
        'parent_id' => 1,
        'node_type' => 'voice_selection',
        'language_iso' => 'en',
        'display_text' => 'Thank you. Would you prefer to explore our platform with the assistance of our AI Voice Companion, or would you like to browse the site independently at your own pace?',
        'spoken_text' => 'Thank you. Would you prefer to explore our platform with the assistance of our AI Voice Companion, or would you like to browse the site independently at your own pace?',
        'target_action' => null
    ],
    // 3. French Welcome & Voice/Independent Choice Parent Tree
    [
        'id' => 11,
        'parent_id' => 1,
        'node_type' => 'voice_selection',
        'language_iso' => 'fr',
        'display_text' => 'Merci. Préféreriez-vous explorer notre plateforme avec l\'aide de notre compagnon vocal IA, ou préférez-vous naviguer sur le site de manière indépendante à votre propre rythme ?',
        'spoken_text' => 'Merci. Préféreriez-vous explorer notre plateforme avec l\'aide de notre compagnon vocal IA, ou préférez-vous naviguer sur le site de manière indépendante à votre propre rythme ?',
        'target_action' => null
    ],
    // 4. Arabic Welcome & Voice/Independent Choice Parent Tree
    [
        'id' => 12,
        'parent_id' => 1,
        'node_type' => 'voice_selection',
        'language_iso' => 'ar',
        'display_text' => 'شكراً لك. هل تفضل استكشاف منصتنا بمساعدة الرفيق الصوتي المدعوم بالذكاء الاصطناعي، أم ترغب في تصفح الموقع بشكل مستقل وبسرعتك الخاصة؟',
        'spoken_text' => 'شكراً لك. هل تفضل استكشاف منصتنا بمساعدة الرفيق الصوتي المدعوم بالذكاء الاصطناعي، أم ترغب في تصفح الموقع بشكل مستقل وبسرعتك الخاصة؟',
        'target_action' => null
    ],
    // 5. Urdu/Hindi Welcome & Voice/Independent Choice Parent Tree
    [
        'id' => 13,
        'parent_id' => 1,
        'node_type' => 'voice_selection',
        'language_iso' => 'ur',
        'display_text' => 'شکریہ۔ کیا آپ ہمارے AI وائس ساتھی کی مدد سے ہمارے پلیٹ فارم کو دریافت کرنا پسند کریں گے، یا آپ اپنی رفتار سے خود ویب سائٹ دیکھنا پسند کریں گے؟',
        'spoken_text' => 'شکریہ۔ کیا آپ ہمارے AI وائس ساتھی کی مدد سے ہمارے پلیٹ فارم کو دریافت کرنا پسند کریں گے، یا آپ اپنی رفتار سے خود ویب سائٹ دیکھنا پسند کریں گے؟',
        'target_action' => null
    ],

    // 6. Voice Onboarding Paths (Category Selectors)
    [
        'id' => 2,
        'parent_id' => 10,
        'node_type' => 'category_select',
        'language_iso' => 'en',
        'display_text' => 'Hello! Welcome to GlobalWays. We simplify UAE documentation. Please select a service category to get started.',
        'spoken_text' => 'Hello! Welcome to GlobalWays. We simplify UAE documentation. Please select a service category to get started.',
        'target_action' => 'fetch_categories'
    ],
    [
        'id' => 3,
        'parent_id' => 11,
        'node_type' => 'category_select',
        'language_iso' => 'fr',
        'display_text' => 'Bonjour! Bienvenue sur GlobalWays. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.',
        'spoken_text' => 'Bonjour! Bienvenue sur GlobalWays. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.',
        'target_action' => 'fetch_categories'
    ],
    [
        'id' => 4,
        'parent_id' => 12,
        'node_type' => 'category_select',
        'language_iso' => 'ar',
        'display_text' => 'مرحباً بك في غلوبال وايز! نحن نبسط الإجراءات والمعاملات الرسمية في دولة الإمارات العربية المتحدة. يرجى اختيار قسم الخدمة للبدء.',
        'spoken_text' => 'مرحباً بك في غلوبال وايز! نحن نبسط الإجراءات والمعاملات الرسمية في دولة الإمارات العربية المتحدة. يرجى اختيار قسم الخدمة للبدء.',
        'target_action' => 'fetch_categories'
    ],
    [
        'id' => 5,
        'parent_id' => 13,
        'node_type' => 'category_select',
        'language_iso' => 'ur',
        'display_text' => 'گلوبل ویز میں خوش آمدید! ہم متحدہ عرب امارات کے کاغذی کام کو آسان بناتے ہیں۔ شروع کرنے کے لیے برائے مہربانی ایک سروس کیٹیگری منتخب کریں۔',
        'spoken_text' => 'گلوبل ویز میں خوش آمدید! ہم متحدہ عرب امارات کے کاغذی کام کو آسان بناتے ہیں۔ شروع کرنے کے لیے برائے مہربانی ایک سروس کیٹیگری منتخب کریں۔',
        'target_action' => 'fetch_categories'
    ],

    // 7. Independent Browse Choice Paths
    [
        'id' => 20,
        'parent_id' => 10,
        'node_type' => 'independent_browse',
        'language_iso' => 'en',
        'display_text' => 'Understood. I will remain silently available in the bottom corner of your screen. As you browse our services, I will automatically keep track of your progress so that whenever you require guidance, we can seamlessly pick up exactly where you left off.',
        'spoken_text' => 'Understood. I will remain silently available in the bottom corner of your screen. As you browse our services, I will automatically keep track of your progress so that whenever you require guidance, we can seamlessly pick up exactly where you left off.',
        'target_action' => 'independent_browse'
    ],
    [
        'id' => 21,
        'parent_id' => 11,
        'node_type' => 'independent_browse',
        'language_iso' => 'fr',
        'display_text' => 'Compris. Je resterai discrètement disponible dans le coin inférieur de votre écran. Pendant votre navigation, je suivrai automatiquement votre progression afin que, dès que vous aurez besoin de conseils, nous puissions reprendre exactement là où vous vous étiez arrêté.',
        'spoken_text' => 'Compris. Je resterai discrètement disponible dans le coin inférieur de votre écran. Pendant votre navigation, je suivrai automatiquement votre progression afin que, dès que vous aurez besoin de conseils, nous puissions reprendre exactement là où vous vous étiez arrêté.',
        'target_action' => 'independent_browse'
    ],
    [
        'id' => 22,
        'parent_id' => 12,
        'node_type' => 'independent_browse',
        'language_iso' => 'ar',
        'display_text' => 'مفهوم. سأظل متاحاً بصمت في الزاوية السفلية من شاشتك. بينما تتصفح خدماتنا، سأتابع تقدمك تلقائياً حتى نتمكن من المتابعة من حيث توقفت تماماً كلما احتجت إلى توجيه.',
        'spoken_text' => 'مفهوم. سأظل متاحاً بصمت في الزاوية السفلية من شاشتك. بينما تتصفح خدماتنا، سأتابع تقدمك تلقائياً حتى نتمكن من المتابعة من حيث توقفت تماماً كلما احتجت إلى توجيه.',
        'target_action' => 'independent_browse'
    ],
    [
        'id' => 23,
        'parent_id' => 13,
        'node_type' => 'independent_browse',
        'language_iso' => 'ur',
        'display_text' => 'سمجھ گیا۔ میں آپ کی اسکرین کے نچلے کونے میں خاموشی سے دستیاب رہوں گا۔ جیسے ہی آپ ہماری خدمات کو براؤز کریں گے، میں خود بخود آپ کی پیشرفت پر نظر رکھوں گا تاکہ جب بھی آپ کو رہنمائی کی ضرورت ہو، ہم وہیں سے بغیر کسی رکاوٹ کے شروع کر سکیں جہاں سے آپ نے چھوڑا تھا۔',
        'spoken_text' => 'سمجھ گیا۔ میں آپ کی اسکرین کے نچلے کونے میں خاموشی سے دستیاب رہوں گا۔ جیسے ہی آپ ہماری خدمات کو براؤز کریں گے، میں خود بخود آپ کی پیشرفت پر نظر رکھوں گا تاکہ جب بھی آپ کو رہنمائی کی ضرورت ہو، ہم وہیں سے بغیر کسی رکاوٹ کے شروع کر سکیں جہاں سے آپ نے چھوڑا تھا۔',
        'target_action' => 'independent_browse'
    ],

    // 8. Shared Handlers
    [
        'id' => 6,
        'parent_id' => null,
        'node_type' => 'category_handler',
        'language_iso' => 'en',
        'display_text' => 'Got it! You\'ve selected a service category. We are preparing the registration and booking context for you.',
        'spoken_text' => 'Got it! You\'ve selected a service category. We are preparing the registration and booking context for you.',
        'target_action' => 'handle_category'
    ]
];

echo "Seeding bot_nodes baseline prompt vectors...\n";
foreach ($nodes as $n) {
    $parent_id_val = is_null($n['parent_id']) ? null : (int)$n['parent_id'];

    // Check if node exists
    $stmt_check = $mysqli->prepare("SELECT id FROM bot_nodes WHERE id = ?");
    if ($stmt_check) {
        $stmt_check->bind_param('i', $n['id']);
        $stmt_check->execute();
        $res = $stmt_check->get_result();
        $exists = ($res && $res->num_rows > 0);
        $stmt_check->close();
    } else {
        $exists = false;
    }

    if ($exists) {
        $stmt = $mysqli->prepare("UPDATE bot_nodes SET parent_id = ?, node_type = ?, language_iso = ?, display_text = ?, spoken_text = ?, target_action = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('isssssi', $parent_id_val, $n['node_type'], $n['language_iso'], $n['display_text'], $n['spoken_text'], $n['target_action'], $n['id']);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $mysqli->prepare("INSERT INTO bot_nodes (id, parent_id, node_type, language_iso, display_text, spoken_text, target_action) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('iisssss', $n['id'], $parent_id_val, $n['node_type'], $n['language_iso'], $n['display_text'], $n['spoken_text'], $n['target_action']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

echo "Database migrations completed successfully.\n";
