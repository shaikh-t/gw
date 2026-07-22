<?php
/**
 * lib/mock_mysqli.php
 * A stateful mock of mysqli to allow local page rendering and complete integration testing when the database is unavailable.
 */

class MockDbHelper {
    public static function get_path() {
        return __DIR__ . '/../var/mock_db.json';
    }

    public static function init() {
        $path = self::get_path();
        if (!file_exists(dirname($path))) {
            @mkdir(dirname($path), 0777, true);
        }
        if (!file_exists($path) || @filesize($path) === 0) {
            $default_db = [
                "site_settings" => [
                    "ai_bot_global_status" => "enabled"
                ],
                "ads" => [
                    [
                        "id" => 99,
                        "is_active" => 1,
                        "destination_url" => "http://127.0.0.1:8000/index.php",
                        "ad_source_type" => "direct_sponsor",
                        "ad_billing_model" => "cpc",
                        "click_cost" => 2.00,
                        "max_budget" => 100.00,
                        "current_spend" => 10.00
                    ]
                ],
                "cases" => [
                    [
                        "uuid" => "test-case-uuid",
                        "customer_user_id" => 1,
                        "provider_id" => 1,
                        "service_id" => 1,
                        "status" => "Quoted",
                        "service_price" => 150.00,
                        "service_currency" => "AED",
                        "customer_name" => "John Doe",
                        "service_title" => "Golden Visa Assistance",
                        "provider_name" => "Apex Legal"
                    ]
                ],
                "providers" => [
                    [
                        "id" => 1,
                        "uuid" => "test-case-uuid",
                        "name" => "Apex Legal",
                        "team_size" => 3,
                        "deduction_type" => "percentage",
                        "deduction_value" => 10.00,
                        "status" => "active",
                        "is_active" => 1,
                        "slug" => "apex-legal",
                        "city" => "Dubai",
                        "description" => "Premium UAE Legal & Golden Visa advisory.",
                        "starting_price" => 500,
                        "rating_avg" => 4.9,
                        "rating_count" => 120,
                        "languages" => "English, Arabic",
                        "verification_status" => "verified",
                        "specialties" => "Golden Visa, Business Setup, PRO Services"
                    ]
                ],
                "provider_team_members" => [],
                "local_knowledge_base" => [
                    [
                        "text_content" => "This is the authoritative golden visa guide details.",
                        "file_name" => "golden_visa_regulations.pdf",
                        "page_number" => 4
                    ]
                ],
                "bot_ad_fraud_logs" => [],
                "bot_failed_questions" => [],
                "bot_approved_keywords" => [
                    ["id" => 1, "keyword_token" => "business", "language_code" => "en"],
                    ["id" => 2, "keyword_token" => "setup", "language_code" => "en"],
                    ["id" => 3, "keyword_token" => "company", "language_code" => "en"],
                    ["id" => 4, "keyword_token" => "immigration", "language_code" => "en"],
                    ["id" => 5, "keyword_token" => "visa", "language_code" => "en"],
                    ["id" => 6, "keyword_token" => "office", "language_code" => "en"],
                    ["id" => 7, "keyword_token" => "consultation", "language_code" => "en"],
                    ["id" => 8, "keyword_token" => "start", "language_code" => "en"],
                    ["id" => 9, "keyword_token" => "launch", "language_code" => "en"],
                    ["id" => 10, "keyword_token" => "open", "language_code" => "en"],
                    ["id" => 11, "keyword_token" => "incorporate", "language_code" => "en"],
                    ["id" => 12, "keyword_token" => "firm", "language_code" => "en"],
                    ["id" => 13, "keyword_token" => "services", "language_code" => "en"],
                    ["id" => 14, "keyword_token" => "meeting", "language_code" => "en"],
                    ["id" => 15, "keyword_token" => "schedule", "language_code" => "en"],
                    ["id" => 16, "keyword_token" => "register", "language_code" => "en"],
                    ["id" => 17, "keyword_token" => "welcome", "language_code" => "en"],
                    ["id" => 18, "keyword_token" => "funnel", "language_code" => "en"],
                    ["id" => 19, "keyword_token" => "selection", "language_code" => "en"],
                    ["id" => 20, "keyword_token" => "dispatch", "language_code" => "en"],
                    ["id" => 21, "keyword_token" => "visit", "language_code" => "en"],
                    ["id" => 22, "keyword_token" => "tourism", "language_code" => "en"],
                    ["id" => 23, "keyword_token" => "license", "language_code" => "en"],
                    ["id" => 24, "keyword_token" => "permit", "language_code" => "en"],
                    ["id" => 25, "keyword_token" => "emirates", "language_code" => "en"],
                    ["id" => 26, "keyword_token" => "national", "language_code" => "en"],
                    ["id" => 27, "keyword_token" => "stamping", "language_code" => "en"],
                    ["id" => 28, "keyword_token" => "attestation", "language_code" => "en"],
                    ["id" => 29, "keyword_token" => "renewal", "language_code" => "en"],
                    ["id" => 30, "keyword_token" => "consultant", "language_code" => "en"],
                    ["id" => 31, "keyword_token" => "advisory", "language_code" => "en"],
                    ["id" => 32, "keyword_token" => "partner", "language_code" => "en"],
                    ["id" => 33, "keyword_token" => "booking", "language_code" => "en"]
                ],
                "bot_intent_synonyms" => [
                    [
                        "id" => 1,
                        "system_intent_key" => "intent_business_setup",
                        "phrase_variant" => "start a business",
                        "language_code" => "en"
                    ],
                    [
                        "id" => 2,
                        "system_intent_key" => "intent_business_setup",
                        "phrase_variant" => "launch a company",
                        "language_code" => "en"
                    ],
                    [
                        "id" => 3,
                        "system_intent_key" => "intent_business_setup",
                        "phrase_variant" => "open an office",
                        "language_code" => "en"
                    ],
                    [
                        "id" => 4,
                        "system_intent_key" => "intent_business_setup",
                        "phrase_variant" => "incorporate a firm",
                        "language_code" => "en"
                    ],
                    [
                        "id" => 5,
                        "system_intent_key" => "intent_business_setup",
                        "phrase_variant" => "launch a brand new company",
                        "language_code" => "en"
                    ]
                ],
                "payment_transactions" => [],
                "customer_payments" => [],
                "customer_applications" => [],
                "login_attempts" => [],
                "registration_attempts" => [],
                "users" => [],
                "bot_workflow_steps" => [
                    [
                        "id" => 1,
                        "step_key" => "welcome_funnel",
                        "step_order" => 10,
                        "primary_question_en" => "Welcome to GlobalWays! Please select your service category below to personalize your journey.",
                        "primary_question_fr" => "Bienvenue sur GlobalWays ! Veuillez sélectionner votre catégorie de service ci-dessous pour personnaliser votre parcours.",
                        "primary_question_ar" => "مرحباً بك في غلوبال وايز! يرجى تحديد فئة الخدمة الخاصة بك أدناه لتخصيص رحلتك.",
                        "primary_question_ur" => "گلوبل ویز میں خوش آمدید! برائے مہربانی اپنا سفر ذاتی بنانے کے لیے نیچے اپنی سروس کیٹیگری منتخب کریں۔",
                        "interface_target" => "left_window",
                        "execution_action" => "none",
                        "parent_step_id" => null
                    ],
                    [
                        "id" => 2,
                        "step_key" => "category_selection",
                        "step_order" => 20,
                        "primary_question_en" => "Excellent! We have updated the right panel layout with customized service options. What would you like to do next?",
                        "primary_question_fr" => "Excellent ! Nous avons mis à jour la mise en page du panneau de droite avec des options de service personnalisées. Que souhaitez-vous faire ensuite ?",
                        "primary_question_ar" => "ممتاز! لقد قمنا بتحديث تخطيط اللوحة اليمنى بخيارات الخدمة المخصصة. ماذا تحب أن تفعل بعد ذلك؟",
                        "primary_question_ur" => "بہت خوب! ہم نے کسٹمائزڈ سروس آپشنز کے ساتھ دائیں پینل کا لے آؤٹ اپ ڈیٹ کر دیا ہے۔ اب آپ آگے کیا کرنا چاہیں گے؟",
                        "interface_target" => "right_window",
                        "execution_action" => "hydrate_right_panel",
                        "parent_step_id" => 1
                    ],
                    [
                        "id" => 3,
                        "step_key" => "business_setup_dispatch",
                        "step_order" => 30,
                        "primary_question_en" => "We can dispatch an automated meeting request to schedule a business setup consultation. Would you like to proceed?",
                        "primary_question_fr" => "Nous pouvons envoyer une demande de rendez-vous automatique pour planifier une consultation sur la création d'entreprise. Souhaitez-vous continuer ?",
                        "primary_question_ar" => "يمكننا إرسال طلب اجتماع تلقائي لجدولة استشارة لتأسيس الشركة. هل ترغب في المتابعة؟",
                        "primary_question_ur" => "ہم بزنس سیٹ اپ مشاورت کے لیے ایک خودکار میٹنگ کی درخواست بھیج سکتے ہیں۔ کیا آپ آگے بڑھنا چاہیں گے؟",
                        "interface_target" => "right_window",
                        "execution_action" => "dispatch_case_meeting",
                        "parent_step_id" => 2
                    ],
                    [
                        "id" => 4,
                        "step_key" => "intent_business_setup",
                        "step_order" => 25,
                        "primary_question_en" => "Loading the Business Setup module with customized service options. How can I help you today?",
                        "primary_question_fr" => "Chargement du module de création d'entreprise avec des options de service personnalisées. Comment puis-je vous aider ?",
                        "primary_question_ar" => "نقوم بتحميل قسم تأسيس الشركات بخيارات الخدمة المخصصة. كيف يمكنني مساعدتك اليوم؟",
                        "primary_question_ur" => "ہم کسٹمائزڈ سروس آپشنز کے ساتھ بزنس سیٹ اپ ماڈیول لوڈ کر رہے ہیں۔ آج آپ کی کیا مدد کر سکتا ہوں؟",
                        "interface_target" => "right_window",
                        "execution_action" => "hydrate_right_panel",
                        "parent_step_id" => 1
                    ]
                ],
                "bot_interaction_logs" => []
            ];
            @file_put_contents($path, json_encode($default_db, JSON_PRETTY_PRINT));
        } else {
            $db = json_decode(@file_get_contents($path), true) ?: [];
            $modified = false;
            if (!isset($db['bot_workflow_steps'])) {
                $db['bot_workflow_steps'] = [
                    [
                        "id" => 1,
                        "step_key" => "welcome_funnel",
                        "step_order" => 10,
                        "primary_question_en" => "Welcome to GlobalWays! Please select your service category below to personalize your journey.",
                        "primary_question_fr" => "Bienvenue sur GlobalWays ! Veuillez sélectionner votre catégorie de service ci-dessous pour personnaliser votre parcours.",
                        "primary_question_ar" => "مرحباً بك في غلوبال وايز! يرجى تحديد فئة الخدمة الخاصة بك أدناه لتخصيص رحلتك.",
                        "primary_question_ur" => "گلوبل ویز میں خوش آمدید! برائے مہربانی اپنا سفر ذاتی بنانے کے لیے نیچے اپنی سروس کیٹیگری منتخب کریں۔",
                        "interface_target" => "left_window",
                        "execution_action" => "none",
                        "parent_step_id" => null
                    ],
                    [
                        "id" => 2,
                        "step_key" => "category_selection",
                        "step_order" => 20,
                        "primary_question_en" => "Excellent! We have updated the right panel layout with customized service options. What would you like to do next?",
                        "primary_question_fr" => "Excellent ! Nous avons mis à jour la mise en page du panneau de droite avec des options de service personnalisées. Que souhaitez-vous faire ensuite ?",
                        "primary_question_ar" => "ممتاز! لقد قمنا بتحديث تخطيط اللوحة اليمنى بخيارات الخدمة المخصصة. ماذا تحب أن تفعل بعد ذلك؟",
                        "primary_question_ur" => "بہت خوب! ہم نے کسٹمائزڈ سروس آپشنز کے ساتھ دائیں پینل کا لے آؤٹ اپ ڈیٹ کر دیا ہے۔ اب آپ آگے کیا کرنا چاہیں گے؟",
                        "interface_target" => "right_window",
                        "execution_action" => "hydrate_right_panel",
                        "parent_step_id" => 1
                    ],
                    [
                        "id" => 3,
                        "step_key" => "business_setup_dispatch",
                        "step_order" => 30,
                        "primary_question_en" => "We can dispatch an automated meeting request to schedule a business setup consultation. Would you like to proceed?",
                        "primary_question_fr" => "Nous pouvons envoyer une demande de rendez-vous automatique pour planifier une consultation sur la création d'entreprise. Souhaitez-vous continuer ?",
                        "primary_question_ar" => "يمكننا إرسال طلب اجتماع تلقائي لجدولة استشارة لتأسيس الشركة. هل ترغب في المتابعة؟",
                        "primary_question_ur" => "ہم بزنس سیٹ اپ مشاورت کے لیے ایک خودکار میٹنگ کی درخواست بھیج سکتے ہیں۔ کیا آپ آگے بڑھنا چاہیں گے؟",
                        "interface_target" => "right_window",
                        "execution_action" => "dispatch_case_meeting",
                        "parent_step_id" => 2
                    ]
                ];
                $modified = true;
            } else {
                $has_intent_step = false;
                foreach ($db['bot_workflow_steps'] as $st) {
                    if (($st['step_key'] ?? '') === 'intent_business_setup') {
                        $has_intent_step = true;
                    }
                }
                if (!$has_intent_step) {
                    $db['bot_workflow_steps'][] = [
                        "id" => 4,
                        "step_key" => "intent_business_setup",
                        "step_order" => 25,
                        "primary_question_en" => "Loading the Business Setup module with customized service options. How can I help you today?",
                        "primary_question_fr" => "Chargement du module de création d'entreprise avec des options de service personnalisées. Comment puis-je vous aider ?",
                        "primary_question_ar" => "نقوم بتحميل قسم تأسيس الشركات بخيارات الخدمة المخصصة. كيف يمكنني مساعدتك اليوم؟",
                        "primary_question_ur" => "ہم کسٹمائزڈ سروس آپشنز کے ساتھ بزنس سیٹ اپ ماڈیول لوڈ کر رہے ہیں۔ آج آپ کی کیا مدد کر سکتا ہوں؟",
                        "interface_target" => "right_window",
                        "execution_action" => "hydrate_right_panel",
                        "parent_step_id" => 1
                    ];
                    $modified = true;
                }
            }

            if (!isset($db['bot_approved_keywords'])) {
                $db['bot_approved_keywords'] = [
                    ["id" => 1, "keyword_token" => "business", "language_code" => "en"],
                    ["id" => 2, "keyword_token" => "setup", "language_code" => "en"],
                    ["id" => 3, "keyword_token" => "company", "language_code" => "en"],
                    ["id" => 4, "keyword_token" => "immigration", "language_code" => "en"],
                    ["id" => 5, "keyword_token" => "visa", "language_code" => "en"],
                    ["id" => 6, "keyword_token" => "office", "language_code" => "en"],
                    ["id" => 7, "keyword_token" => "consultation", "language_code" => "en"],
                    ["id" => 8, "keyword_token" => "start", "language_code" => "en"],
                    ["id" => 9, "keyword_token" => "launch", "language_code" => "en"],
                    ["id" => 10, "keyword_token" => "open", "language_code" => "en"],
                    ["id" => 11, "keyword_token" => "incorporate", "language_code" => "en"],
                    ["id" => 12, "keyword_token" => "firm", "language_code" => "en"],
                    ["id" => 13, "keyword_token" => "services", "language_code" => "en"],
                    ["id" => 14, "keyword_token" => "meeting", "language_code" => "en"],
                    ["id" => 15, "keyword_token" => "schedule", "language_code" => "en"],
                    ["id" => 16, "keyword_token" => "register", "language_code" => "en"],
                    ["id" => 17, "keyword_token" => "welcome", "language_code" => "en"],
                    ["id" => 18, "keyword_token" => "funnel", "language_code" => "en"],
                    ["id" => 19, "keyword_token" => "selection", "language_code" => "en"],
                    ["id" => 20, "keyword_token" => "dispatch", "language_code" => "en"],
                    ["id" => 21, "keyword_token" => "visit", "language_code" => "en"],
                    ["id" => 22, "keyword_token" => "tourism", "language_code" => "en"],
                    ["id" => 23, "keyword_token" => "license", "language_code" => "en"],
                    ["id" => 24, "keyword_token" => "permit", "language_code" => "en"],
                    ["id" => 25, "keyword_token" => "emirates", "language_code" => "en"],
                    ["id" => 26, "keyword_token" => "national", "language_code" => "en"],
                    ["id" => 27, "keyword_token" => "stamping", "language_code" => "en"],
                    ["id" => 28, "keyword_token" => "attestation", "language_code" => "en"],
                    ["id" => 29, "keyword_token" => "renewal", "language_code" => "en"],
                    ["id" => 30, "keyword_token" => "consultant", "language_code" => "en"],
                    ["id" => 31, "keyword_token" => "advisory", "language_code" => "en"],
                    ["id" => 32, "keyword_token" => "partner", "language_code" => "en"],
                    ["id" => 33, "keyword_token" => "booking", "language_code" => "en"]
                ];
                $modified = true;
            }

            if (!isset($db['bot_intent_synonyms'])) {
                $db['bot_intent_synonyms'] = [
                    ["id" => 1, "system_intent_key" => "intent_business_setup", "phrase_variant" => "start a business", "language_code" => "en"],
                    ["id" => 2, "system_intent_key" => "intent_business_setup", "phrase_variant" => "launch a company", "language_code" => "en"],
                    ["id" => 3, "system_intent_key" => "intent_business_setup", "phrase_variant" => "open an office", "language_code" => "en"],
                    ["id" => 4, "system_intent_key" => "intent_business_setup", "phrase_variant" => "incorporate a firm", "language_code" => "en"]
                ];
                $modified = true;
            } else {
                $has_synonyms = false;
                foreach ($db['bot_intent_synonyms'] as $sy) {
                    if (($sy['phrase_variant'] ?? '') === 'start a business') {
                        $has_synonyms = true;
                    }
                }
                if (!$has_synonyms) {
                    $db['bot_intent_synonyms'][] = ["id" => 1, "system_intent_key" => "intent_business_setup", "phrase_variant" => "start a business", "language_code" => "en"];
                    $db['bot_intent_synonyms'][] = ["id" => 2, "system_intent_key" => "intent_business_setup", "phrase_variant" => "launch a company", "language_code" => "en"];
                    $db['bot_intent_synonyms'][] = ["id" => 3, "system_intent_key" => "intent_business_setup", "phrase_variant" => "open an office", "language_code" => "en"];
                    $db['bot_intent_synonyms'][] = ["id" => 4, "system_intent_key" => "intent_business_setup", "phrase_variant" => "incorporate a firm", "language_code" => "en"];
                    $db['bot_intent_synonyms'][] = ["id" => 5, "system_intent_key" => "intent_business_setup", "phrase_variant" => "launch a brand new company", "language_code" => "en"];
                    $modified = true;
                } else {
                    $has_brand_new = false;
                    foreach ($db['bot_intent_synonyms'] as $sy) {
                        if (($sy['phrase_variant'] ?? '') === 'launch a brand new company') {
                            $has_brand_new = true;
                        }
                    }
                    if (!$has_brand_new) {
                        $db['bot_intent_synonyms'][] = ["id" => 5, "system_intent_key" => "intent_business_setup", "phrase_variant" => "launch a brand new company", "language_code" => "en"];
                        $modified = true;
                    }
                }
            }

            if (!isset($db['bot_interaction_logs'])) {
                $db['bot_interaction_logs'] = [];
                $modified = true;
            }
            if (!isset($db['site_settings'])) {
                $db['site_settings'] = [];
                $modified = true;
            }
            $defaults = [
                "ai_bot_global_status" => "enabled",
                "google_analytics_status" => "OFF",
                "google_analytics_measurement_id" => "UA-XXXXX-Y",
                "elevenlabs_status" => "OFF",
                "elevenlabs_api_key" => "",
                "elevenlabs_voice_id" => "21m00Tcm4TlvDq8ikWAM",
                "elevenlabs_stability" => "0.75",
                "elevenlabs_clarity" => "0.75"
            ];
            foreach ($defaults as $k => $v) {
                if (!isset($db['site_settings'][$k])) {
                    $db['site_settings'][$k] = $v;
                    $modified = true;
                }
            }
            if (!isset($db['voice_telemetry_logs'])) {
                $db['voice_telemetry_logs'] = [];
                $modified = true;
            }
            if ($modified) {
                @file_put_contents($path, json_encode($db, JSON_PRETTY_PRINT));
            }
        }
    }

    public static function read() {
        self::init();
        $path = self::get_path();
        return json_decode(@file_get_contents($path), true) ?: [];
    }

    public static function write($data) {
        $path = self::get_path();
        @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }
}

class MockMySQLi {
    public $connect_errno = 0;
    public $connect_error = '';
    public $error = '';
    public $insert_id = 1;

    public function set_charset($charset) {
        return true;
    }

    public function query($sql) {
        $sql = trim(preg_replace('/\s+/', ' ', $sql));
        if (stripos($sql, 'CREATE TABLE') !== false) {
            return new MockMySQLiResult();
        }

        $db = MockDbHelper::read();
        if (stripos($sql, 'bot_workflow_steps') !== false) {
            $rows = $db['bot_workflow_steps'] ?? [];
            usort($rows, function($a, $b) {
                return ($a['step_order'] ?? 0) <=> ($b['step_order'] ?? 0);
            });
            return new MockMySQLiResult($rows);
        }
        if (stripos($sql, 'bot_approved_keywords') !== false) {
            return new MockMySQLiResult($db['bot_approved_keywords'] ?? []);
        }
        if (stripos($sql, 'bot_failed_questions') !== false) {
            $failed_list = [];
            foreach ($db['bot_failed_questions'] ?? [] as $q) {
                $failed_list[] = [
                    'id' => $q['id'],
                    'session_id' => $q['session_id'] ?? 1,
                    'user_id' => $q['user_id'] ?? null,
                    'customer_name' => 'Guest Customer',
                    'customer_email' => '',
                    'language_iso' => $q['language_iso'] ?? 'en',
                    'unanswered_question' => $q['unanswered_question'] ?? '',
                    'page_context_url' => $q['page_context_url'] ?? 'bot-landing.php',
                    'session_token' => 'mock-token',
                    'entry_point' => 'mock-entry',
                    'created_at' => $q['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            return new MockMySQLiResult($failed_list);
        }
        if (stripos($sql, 'bot_interaction_logs') !== false) {
            if (stripos($sql, 'COUNT(*)') !== false && stripos($sql, 'GROUP BY') !== false) {
                $counts = [];
                foreach ($db['bot_interaction_logs'] ?? [] as $log) {
                    $token = $log['active_state_token'] ?? '';
                    if (!isset($counts[$token])) {
                        $counts[$token] = 0;
                    }
                    $counts[$token]++;
                }
                $rows = [];
                foreach ($counts as $tok => $c) {
                    $rows[] = ['active_state_token' => $tok, 'cnt' => $c];
                }
                return new MockMySQLiResult($rows);
            }
            return new MockMySQLiResult($db['bot_interaction_logs'] ?? []);
        }
        if (stripos($sql, 'site_settings') !== false) {
            $rows = [];
            foreach ($db['site_settings'] as $k => $v) {
                $rows[] = ['key' => $k, 'value' => $v];
            }
            return new MockMySQLiResult($rows);
        }
        if (stripos($sql, 'FROM provider_team_members') !== false || stripos($sql, 'FROM `provider_team_members`') !== false) {
            $provider_team = $db['provider_team_members'] ?? [];
            return new MockMySQLiResult($provider_team);
        }
        if (stripos($sql, 'FROM providers') !== false || stripos($sql, 'FROM `providers`') !== false) {
            return new MockMySQLiResult($db['providers']);
        }
        if (stripos($sql, 'FROM service_categories') !== false || stripos($sql, 'FROM `service_categories`') !== false) {
            return new MockMySQLiResult([
                ['id' => 1, 'uuid' => 'cat-imm-123', 'name' => 'Immigration Services'],
                ['id' => 2, 'uuid' => 'cat-vis-456', 'name' => 'Visit Visa'],
                ['id' => 3, 'uuid' => 'cat-bus-789', 'name' => 'Business Setup']
            ]);
        }
        if (stripos($sql, 'FROM services') !== false || stripos($sql, 'FROM `services`') !== false) {
            return new MockMySQLiResult([
                [
                    'id' => 1,
                    'uuid' => 'test-service-uuid-1',
                    'provider_id' => 1,
                    'category_id' => 1,
                    'title' => 'Golden Visa Assistance',
                    'slug' => 'golden-visa',
                    'short_description' => 'Get your 10-year Golden Visa with guaranteed UAE approval.',
                    'description' => 'Our premium Golden Visa assistance package covers all document clearance and application stages.',
                    'price' => 5000.00,
                    'currency' => 'AED',
                    'duration_minutes' => 120,
                    'duration_text' => '5-7 days',
                    'status' => 'published',
                    'rating_avg' => 4.9,
                    'rating_count' => 120,
                    'icon_class' => 'bi-award',
                    'category_name' => 'Immigration Services',
                    'category_slug' => 'immigration'
                ]
            ]);
        }

        return new MockMySQLiResult();
    }

    public function prepare($sql) {
        return new MockMySQLiStmt($sql);
    }

    public function real_escape_string($str) {
        return addslashes($str);
    }

    public function begin_transaction() {
        return true;
    }

    public function commit() {
        return true;
    }

    public function rollback() {
        return true;
    }
}

class MockMySQLiResult {
    public $num_rows = 0;
    private $rows = [];
    private $currentIndex = 0;

    public function __construct($rows = []) {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc() {
        if ($this->currentIndex < $this->num_rows) {
            return $this->rows[$this->currentIndex++];
        }
        return null;
    }

    public function fetch_row() {
        if ($this->currentIndex < $this->num_rows) {
            return array_values($this->rows[$this->currentIndex++]);
        }
        return null;
    }

    public function free() {
        return true;
    }
}

class MockMySQLiStmt {
    private $sql;
    private $params = [];
    public $insert_id = 1;
    public $error = '';
    private $result_rows = [];
    private $currentRowIndex = 0;
    private $bound_results = [];

    public function __construct($sql) {
        $this->sql = $sql;
    }

    public function bind_param($types, &...$args) {
        $this->params = [];
        foreach ($args as $arg) {
            $this->params[] = $arg;
        }
        return true;
    }

    public function bind_result(&...$args) {
        $this->bound_results = &$args;
        return true;
    }

    public function fetch() {
        if ($this->currentRowIndex < count($this->result_rows)) {
            $row = array_values($this->result_rows[$this->currentRowIndex++]);
            for ($i = 0; $i < count($row) && $i < count($this->bound_results); $i++) {
                $this->bound_results[$i] = $row[$i];
            }
            return true;
        }
        return null;
    }

    public function execute() {
        $db = MockDbHelper::read();
        $sql = trim(preg_replace('/\s+/', ' ', $this->sql));
        $sql = str_replace('`', '', $sql);
        $this->currentRowIndex = 0;

        if (stripos($sql, 'INSERT INTO bot_workflow_steps') !== false) {
            $new_id = 1;
            foreach (($db['bot_workflow_steps'] ?? []) as $item) {
                if ($item['id'] >= $new_id) {
                    $new_id = $item['id'] + 1;
                }
            }
            $db['bot_workflow_steps'][] = [
                'id' => $new_id,
                'step_key' => $this->params[0] ?? '',
                'step_order' => (int)($this->params[1] ?? 0),
                'primary_question_en' => $this->params[2] ?? '',
                'primary_question_fr' => $this->params[3] ?? '',
                'primary_question_ar' => $this->params[4] ?? '',
                'primary_question_ur' => $this->params[5] ?? '',
                'interface_target' => $this->params[6] ?? 'left_window',
                'execution_action' => $this->params[7] ?? 'none',
                'parent_step_id' => isset($this->params[8]) ? (int)$this->params[8] : null
            ];
            MockDbHelper::write($db);
            $this->insert_id = $new_id;
            return true;
        }
        elseif (stripos($sql, 'UPDATE bot_workflow_steps') !== false) {
            $id = (int)($this->params[9] ?? 0);
            file_put_contents('php://stderr', "MOCK DB UPDATE ID: " . $id . " PARAMS: " . json_encode($this->params) . "\n");
            if (isset($db['bot_workflow_steps'])) {
                foreach ($db['bot_workflow_steps'] as &$item) {
                    if ($item['id'] === $id) {
                        $item['step_key'] = $this->params[0] ?? '';
                        $item['step_order'] = (int)($this->params[1] ?? 0);
                        $item['primary_question_en'] = $this->params[2] ?? '';
                        $item['primary_question_fr'] = $this->params[3] ?? '';
                        $item['primary_question_ar'] = $this->params[4] ?? '';
                        $item['primary_question_ur'] = $this->params[5] ?? '';
                        $item['interface_target'] = $this->params[6] ?? 'left_window';
                        $item['execution_action'] = $this->params[7] ?? 'none';
                        $item['parent_step_id'] = !empty($this->params[8]) ? (int)$this->params[8] : null;
                    }
                }
            }
            MockDbHelper::write($db);
            return true;
        }
        elseif (stripos($sql, 'DELETE FROM bot_workflow_steps') !== false) {
            $id = (int)($this->params[0] ?? 0);
            $filtered = [];
            if (isset($db['bot_workflow_steps'])) {
                foreach ($db['bot_workflow_steps'] as $item) {
                    if ($item['id'] !== $id) {
                        $filtered[] = $item;
                    }
                }
            }
            $db['bot_workflow_steps'] = $filtered;
            MockDbHelper::write($db);
            return true;
        }
        elseif (stripos($sql, 'INSERT INTO bot_interaction_logs') !== false) {
            $new_id = count($db['bot_interaction_logs'] ?? []) + 1;
            $db['bot_interaction_logs'][] = [
                'id' => $new_id,
                'session_id' => $this->params[0] ?? '',
                'user_id' => isset($this->params[1]) ? (int)$this->params[1] : null,
                'spoken_text_transcript' => $this->params[2] ?? '',
                'bot_response_text' => $this->params[3] ?? '',
                'match_type' => $this->params[4] ?? 'workflow_step',
                'active_state_token' => $this->params[5] ?? 'welcome_funnel',
                'created_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = $new_id;
            return true;
        }
        elseif (stripos($sql, 'FROM bot_interaction_logs') !== false) {
            $matched = $db['bot_interaction_logs'] ?? [];
            usort($matched, function($a, $b) {
                return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
            });
            $this->result_rows = $matched;
            return true;
        }
        elseif (stripos($sql, 'FROM bot_workflow_steps') !== false) {
            $step_key = null;
            if (!empty($this->params)) {
                $step_key = $this->params[0];
            }
            $matched = [];
            foreach (($db['bot_workflow_steps'] ?? []) as $row) {
                if ($step_key !== null) {
                    if ($row['step_key'] === $step_key || $row['id'] == $step_key) {
                        $matched[] = $row;
                        break;
                    }
                } else {
                    $matched[] = $row;
                }
            }
            $this->result_rows = $matched;
            return true;
        }
        elseif (stripos($sql, 'SELECT text_content, file_name, page_number FROM local_knowledge_base') !== false) {
            $search = $this->params[0] ?? '';
            $matched_rows = [];
            if (!empty($search)) {
                foreach ($db['local_knowledge_base'] as $row) {
                    if (stripos($row['text_content'], $search) !== false) {
                        $matched_rows[] = $row;
                    }
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT COUNT(*) FROM `login_attempts`') !== false || stripos($sql, 'SELECT COUNT(*) FROM login_attempts') !== false) {
            $ip = $this->params[0] ?? '';
            $count = 0;
            foreach ($db['login_attempts'] as $log) {
                if ($log['ip_address'] === $ip) {
                    $count++;
                }
            }
            $this->result_rows = [[$count]];
        }
        elseif (stripos($sql, 'SELECT COUNT(*) FROM `registration_attempts`') !== false || stripos($sql, 'SELECT COUNT(*) FROM registration_attempts') !== false) {
            $ip = $this->params[0] ?? '';
            $count = 0;
            foreach ($db['registration_attempts'] as $log) {
                if ($log['ip_address'] === $ip) {
                    $count++;
                }
            }
            $this->result_rows = [[$count]];
        }
        elseif (stripos($sql, 'INSERT INTO `login_attempts`') !== false || stripos($sql, 'INSERT INTO login_attempts') !== false) {
            $ip = $this->params[0] ?? '';
            $db['login_attempts'][] = [
                'ip_address' => $ip,
                'attempt_time' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['login_attempts']);
        }
        elseif (stripos($sql, 'INSERT INTO `registration_attempts`') !== false || stripos($sql, 'INSERT INTO registration_attempts') !== false) {
            $ip = $this->params[0] ?? '';
            $db['registration_attempts'][] = [
                'ip_address' => $ip,
                'attempt_time' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['registration_attempts']);
        }
        elseif (stripos($sql, 'DELETE FROM `login_attempts`') !== false || stripos($sql, 'DELETE FROM login_attempts') !== false) {
            $ip = $this->params[0] ?? '';
            $filtered = [];
            foreach ($db['login_attempts'] as $log) {
                if ($log['ip_address'] !== $ip) {
                    $filtered[] = $log;
                }
            }
            $db['login_attempts'] = $filtered;
            MockDbHelper::write($db);
        }
        elseif (stripos($sql, 'SELECT key, value FROM site_settings') !== false) {
            $rows = [];
            foreach ($db['site_settings'] as $k => $v) {
                $rows[] = ['key' => $k, 'value' => $v];
            }
            $this->result_rows = $rows;
        }
        elseif (stripos($sql, 'SELECT value FROM site_settings WHERE key =') !== false || stripos($sql, 'SELECT value FROM site_settings') !== false) {
            $key = $this->params[0] ?? 'ai_bot_global_status';
            $val = $db['site_settings'][$key] ?? 'enabled';
            $this->result_rows = [['value' => $val]];
        }
        elseif (stripos($sql, 'UPDATE site_settings SET value = ? WHERE key = ?') !== false) {
            $val = $this->params[0] ?? '';
            $key = $this->params[1] ?? '';
            $db['site_settings'][$key] = $val;
            MockDbHelper::write($db);
        }
        elseif (stripos($sql, 'INSERT INTO voice_telemetry_logs') !== false) {
            $db['voice_telemetry_logs'][] = [
                'id' => count($db['voice_telemetry_logs'] ?? []) + 1,
                'engine' => $this->params[0] ?? 'native',
                'characters_used' => (int)($this->params[1] ?? 0),
                'is_error' => (int)($this->params[2] ?? 0),
                'error_message' => $this->params[3] ?? '',
                'server_load' => (float)($this->params[4] ?? 0.0),
                'created_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['voice_telemetry_logs']);
        }
        elseif (stripos($sql, 'SELECT') !== false && stripos($sql, 'voice_telemetry_logs') !== false) {
            $this->result_rows = $db['voice_telemetry_logs'] ?? [];
        }
        elseif (stripos($sql, 'INSERT INTO bot_failed_questions') !== false) {
            // Check table exists or init
            if (!isset($db['bot_failed_questions'])) {
                $db['bot_failed_questions'] = [];
            }
            $new_id = count($db['bot_failed_questions']) + 1;
            $db['bot_failed_questions'][] = [
                'id' => $new_id,
                'session_id' => $this->params[0] ?? null,
                'user_id' => $this->params[1] ?? null,
                'language_iso' => $this->params[2] ?? null,
                'unanswered_question' => $this->params[3] ?? null,
                'page_context_url' => $this->params[4] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = $new_id;
        }
        elseif (stripos($sql, 'DELETE FROM bot_failed_questions') !== false) {
            $q_id = (int)($this->params[0] ?? 0);
            $filtered = [];
            if (isset($db['bot_failed_questions'])) {
                foreach ($db['bot_failed_questions'] as $item) {
                    if ((int)$item['id'] !== $q_id) {
                        $filtered[] = $item;
                    }
                }
                $db['bot_failed_questions'] = $filtered;
                MockDbHelper::write($db);
            }
        }
        elseif (stripos($sql, 'INSERT INTO bot_intent_synonyms') !== false) {
            if (!isset($db['bot_intent_synonyms'])) {
                $db['bot_intent_synonyms'] = [];
            }
            $new_id = count($db['bot_intent_synonyms']) + 1;
            $db['bot_intent_synonyms'][] = [
                'id' => $new_id,
                'system_intent_key' => $this->params[0] ?? '',
                'phrase_variant' => $this->params[1] ?? '',
                'language_code' => $this->params[2] ?? ''
            ];
            MockDbHelper::write($db);
            $this->insert_id = $new_id;
        }
        elseif (stripos($sql, 'FROM bot_intent_synonyms') !== false) {
            $matched = [];
            if (stripos($sql, 'phrase_variant') !== false && count($this->params) >= 2) {
                $phrase = $this->params[0];
                $lang_val = $this->params[1];
                foreach ($db['bot_intent_synonyms'] ?? [] as $syn) {
                    if ($syn['phrase_variant'] === $phrase && $syn['language_code'] === $lang_val) {
                        $matched[] = $syn;
                    }
                }
            } else {
                $matched = $db['bot_intent_synonyms'] ?? [];
            }
            $this->result_rows = $matched;
        }
        elseif (stripos($sql, 'INSERT INTO bot_approved_keywords') !== false) {
            if (!isset($db['bot_approved_keywords'])) {
                $db['bot_approved_keywords'] = [];
            }
            $new_id = count($db['bot_approved_keywords']) + 1;
            $db['bot_approved_keywords'][] = [
                'id' => $new_id,
                'keyword_token' => $this->params[0] ?? '',
                'language_code' => $this->params[1] ?? 'en'
            ];
            MockDbHelper::write($db);
            $this->insert_id = $new_id;
        }
        elseif (stripos($sql, 'DELETE FROM bot_approved_keywords') !== false) {
            $k_id = (int)($this->params[0] ?? 0);
            $filtered = [];
            if (isset($db['bot_approved_keywords'])) {
                foreach ($db['bot_approved_keywords'] as $item) {
                    if ((int)$item['id'] !== $k_id) {
                        $filtered[] = $item;
                    }
                }
                $db['bot_approved_keywords'] = $filtered;
                MockDbHelper::write($db);
            }
        }
        elseif (stripos($sql, 'FROM bot_approved_keywords') !== false) {
            $this->result_rows = $db['bot_approved_keywords'] ?? [];
        }
        elseif (stripos($sql, 'FROM bot_failed_questions') !== false) {
            // Emulate the failed questions list with sessions joined if needed
            $failed_list = [];
            foreach ($db['bot_failed_questions'] ?? [] as $q) {
                $failed_list[] = [
                    'id' => $q['id'],
                    'session_id' => $q['session_id'],
                    'user_id' => $q['user_id'],
                    'customer_name' => 'Guest Customer',
                    'customer_email' => '',
                    'language_iso' => $q['language_iso'],
                    'unanswered_question' => $q['unanswered_question'],
                    'page_context_url' => $q['page_context_url'],
                    'session_token' => 'mock-token',
                    'entry_point' => 'mock-entry',
                    'created_at' => $q['created_at']
                ];
            }
            $this->result_rows = $failed_list;
        }
        elseif (stripos($sql, 'SELECT * FROM bot_ads WHERE id = ?') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $matched_rows = [];
            foreach ($db['ads'] as $ad) {
                if ($ad['id'] === $ad_id && $ad['is_active'] == 1) {
                    $matched_rows[] = $ad;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT COUNT(*) AS click_count FROM bot_ad_fraud_logs') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $ip = $this->params[1] ?? '';
            $count = 0;
            foreach ($db['bot_ad_fraud_logs'] as $log) {
                if ($log['ad_id'] === $ad_id && $log['ip_address'] === $ip) {
                    $count++;
                }
            }
            $this->result_rows = [['click_count' => $count]];
        }
        elseif (stripos($sql, 'INSERT INTO bot_ad_fraud_logs') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $ip = $this->params[1] ?? '';
            $db['bot_ad_fraud_logs'][] = [
                'ad_id' => $ad_id,
                'ip_address' => $ip,
                'clicked_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['bot_ad_fraud_logs']);
        }
        elseif (stripos($sql, 'INSERT INTO bot_ad_clicks') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $session_id = $this->params[1] ?? null;
            $earned = (float)($this->params[2] ?? 0.0);
            $db['bot_ad_clicks'][] = [
                'ad_id' => $ad_id,
                'session_id' => $session_id,
                'earned_amount' => $earned,
                'clicked_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['bot_ad_clicks']);
        }
        elseif (stripos($sql, 'UPDATE bot_ads SET current_spend') !== false) {
            $cost = (float)($this->params[0] ?? 0.0);
            $ad_id = (int)($this->params[1] ?? 0);
            foreach ($db['ads'] as &$ad) {
                if ($ad['id'] === $ad_id) {
                    $ad['current_spend'] += $cost;
                    if ($ad['current_spend'] >= $ad['max_budget']) {
                        $ad['is_active'] = 0;
                    }
                }
            }
            MockDbHelper::write($db);
        }
        elseif (stripos($sql, 'SELECT id FROM payment_transactions WHERE transaction_id = ?') !== false) {
            $tx_id = $this->params[0] ?? '';
            $matched_rows = [];
            foreach ($db['payment_transactions'] as $tx) {
                if ($tx['transaction_id'] === $tx_id) {
                    $matched_rows[] = ['id' => 1];
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT c.*, p.name as provider_name, p.owner_user_id as provider_owner_id') !== false) {
            $case_uuid = $this->params[0] ?? '';
            $matched_rows = [];
            foreach ($db['cases'] as $case) {
                if ($case['uuid'] === $case_uuid) {
                    $matched_rows[] = [
                        'uuid' => $case['uuid'],
                        'customer_user_id' => $case['customer_user_id'],
                        'provider_id' => $case['provider_id'],
                        'service_id' => $case['service_id'],
                        'status' => $case['status'],
                        'service_price' => $case['service_price'],
                        'service_currency' => $case['service_currency'],
                        'customer_name' => $case['customer_name'],
                        'service_title' => $case['service_title'],
                        'provider_name' => $case['provider_name'],
                        'provider_owner_id' => 2
                    ];
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT c.*, p.name as provider_name') !== false) {
            $case_uuid = $this->params[0] ?? '';
            $matched_rows = [];
            foreach ($db['cases'] as $case) {
                if ($case['uuid'] === $case_uuid) {
                    $matched_rows[] = $case;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT deduction_type, deduction_value FROM providers WHERE id = ?') !== false) {
            $provider_id = (int)($this->params[0] ?? 0);
            $matched_rows = [];
            foreach ($db['providers'] as $prov) {
                if ($prov['id'] === $provider_id) {
                    $matched_rows[] = $prov;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'INSERT INTO payment_transactions') !== false) {
            $db['payment_transactions'][] = [
                'transaction_id' => $this->params[0] ?? '',
                'gross_amount' => $this->params[1] ?? 0.0,
                'platform_fee' => $this->params[2] ?? 0.0,
                'vendor_net_amount' => $this->params[3] ?? 0.0,
                'case_uuid' => $this->params[4] ?? '',
                'provider_id' => $this->params[5] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['payment_transactions']);
        }
        elseif (stripos($sql, 'UPDATE `cases` SET status =') !== false || stripos($sql, 'UPDATE cases SET status =') !== false) {
            $case_uuid = $this->params[0] ?? '';
            foreach ($db['cases'] as &$case) {
                if ($case['uuid'] === $case_uuid) {
                    $case['status'] = 'Booked';
                }
            }
            MockDbHelper::write($db);
        }
        elseif (stripos($sql, 'SELECT id FROM roles WHERE name = \'viewer\'') !== false) {
            $this->result_rows = [['id' => 3]];
        }
        elseif (stripos($sql, 'SELECT id FROM users WHERE email = ?') !== false) {
            $email = $this->params[0] ?? '';
            $matched_rows = [];
            foreach ($db['users'] as $user) {
                if ($user['email'] === $email) {
                    $matched_rows[] = $user;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT id, uuid, name, email, avatar FROM users') !== false) {
            $id_val = $this->params[0] ?? 1;
            $matched_rows = [];
            foreach ($db['users'] as $user) {
                if ($user['id'] == $id_val || $user['uuid'] === $id_val) {
                    $matched_rows[] = [
                        'id' => $user['id'],
                        'uuid' => $user['uuid'] ?? 'test-uuid-jane',
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'avatar' => null
                    ];
                }
            }
            if (empty($matched_rows)) {
                $matched_rows[] = [
                    'id' => 1,
                    'uuid' => 'test-user-uuid',
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'avatar' => null
                ];
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'FROM user_roles') !== false) {
            $this->result_rows = [['name' => 'viewer']];
        }
        elseif (stripos($sql, 'FROM provider_team_members') !== false || stripos($sql, 'FROM `provider_team_members`') !== false) {
            $this->result_rows = $db['provider_team_members'] ?? [];
        }
        elseif (stripos($sql, 'FROM providers') !== false || stripos($sql, 'FROM `providers`') !== false) {
            $this->result_rows = $db['providers'] ?? [];
        }
        elseif (stripos($sql, 'FROM services') !== false || stripos($sql, 'FROM `services`') !== false) {
            $this->result_rows = [
                [
                    'id' => 1,
                    'uuid' => 'test-service-uuid-1',
                    'provider_id' => 1,
                    'category_id' => 1,
                    'title' => 'Golden Visa Assistance',
                    'slug' => 'golden-visa',
                    'short_description' => 'Get your 10-year Golden Visa with guaranteed UAE approval.',
                    'description' => 'Our premium Golden Visa assistance package covers all document clearance and application stages.',
                    'price' => 5000.00,
                    'currency' => 'AED',
                    'duration_minutes' => 120,
                    'duration_text' => '5-7 days',
                    'status' => 'published',
                    'rating_avg' => 4.9,
                    'rating_count' => 120,
                    'icon_class' => 'bi-award',
                    'category_name' => 'Immigration Services',
                    'category_slug' => 'immigration',
                    'provider_name' => 'Apex Legal',
                    'provider_slug' => 'apex-legal'
                ]
            ];
        }
        elseif (stripos($sql, 'INSERT INTO `users`') !== false || stripos($sql, 'INSERT INTO users') !== false) {
            $uuid = isset($this->params[0]) ? $this->params[0] : 'test-user-uuid';
            $name = isset($this->params[1]) ? $this->params[1] : '';
            $email = isset($this->params[2]) ? $this->params[2] : '';
            $db['users'][] = [
                'id' => count($db['users']) + 1,
                'uuid' => $uuid,
                'name' => $name,
                'email' => $email,
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['users']);
        }
        else {
            $this->result_rows = [];
        }

        return true;
    }

    public function get_result() {
        return new MockMySQLiResult($this->result_rows);
    }

    public function close() {
        return true;
    }
}
?>