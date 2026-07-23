<?php
// migrations/bot_intent_synonyms_migration.php
require_once __DIR__ . '/../lib/db_mysqli.php';

echo "Starting Intent Synonym Database migration...\n";

$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

// Create bot_intent_synonyms table
$sql = "CREATE TABLE IF NOT EXISTS `bot_intent_synonyms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `system_intent_key` VARCHAR(150) NOT NULL,
  `phrase_variant` VARCHAR(255) NOT NULL,
  `language_code` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_phrase_variant` (`phrase_variant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($sql)) {
    echo "bot_intent_synonyms table created successfully.\n";
} else {
    echo "Error creating bot_intent_synonyms table: " . $mysqli->error . "\n";
}

// Seed intent_business_setup step into bot_workflow_steps if it doesn't exist
$res_step = $mysqli->query("SELECT id FROM bot_workflow_steps WHERE step_key = 'intent_business_setup' LIMIT 1");
if ($res_step && $res_step->num_rows === 0) {
    $sql_step = "INSERT INTO `bot_workflow_steps` (`id`, `step_key`, `step_order`, `primary_question_en`, `primary_question_fr`, `primary_question_ar`, `primary_question_ur`, `interface_target`, `execution_action`, `parent_step_id`) VALUES
    (4, 'intent_business_setup', 25, 'Loading the Business Setup module with customized service options. How can I help you today?', 'Chargement du module de création d\'entreprise avec des options de service personnalisées. Comment puis-je vous aider ?', 'نقوم بتحميل قسم تأسيس الشركات بخيارات الخدمة المخصصة. كيف يمكنني مساعدتك اليوم؟', 'ہم کسٹمائزڈ سروس آپشنز کے ساتھ بزنس سیٹ اپ ماڈیول لوڈ کر رہے ہیں۔ آج آپ کی کیا مدد کر سکتا ہوں؟', 'right_window', 'hydrate_right_panel', 1);";
    if ($mysqli->query($sql_step)) {
        echo "intent_business_setup step seeded successfully into bot_workflow_steps.\n";
    } else {
        echo "Error/Note seeding intent_business_setup: " . $mysqli->error . "\n";
    }
}

// Seed synonyms
$synonyms = [
    ['intent_business_setup', 'start a business', 'en'],
    ['intent_business_setup', 'launch a company', 'en'],
    ['intent_business_setup', 'open an office', 'en'],
    ['intent_business_setup', 'incorporate a firm', 'en'],
    ['intent_business_setup', 'launch a brand new company', 'en'],
    ['intent_business_setup', 'کاروبار', 'ur'],
];

foreach ($synonyms as $syn) {
    $stmt = $mysqli->prepare("SELECT id FROM bot_intent_synonyms WHERE phrase_variant = ? AND language_code = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $syn[1], $syn[2]);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = ($res && $res->num_rows > 0);
        $stmt->close();
    } else {
        $exists = false;
    }

    if (!$exists) {
        $stmt_ins = $mysqli->prepare("INSERT INTO bot_intent_synonyms (system_intent_key, phrase_variant, language_code) VALUES (?, ?, ?)");
        if ($stmt_ins) {
            $stmt_ins->bind_param('sss', $syn[0], $syn[1], $syn[2]);
            $stmt_ins->execute();
            $stmt_ins->close();
            echo "Seeded synonym: " . $syn[1] . "\n";
        }
    }
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");
echo "Intent Synonym migration completed successfully.\n";
