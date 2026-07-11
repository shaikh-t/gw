<?php
// database_migration.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/uuid_helper.php';

echo "Starting database migrations...\n";

// 1. Create cms_pages table
$sql = "CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_name` VARCHAR(50) NOT NULL UNIQUE,
  `content` JSON NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($mysqli->query($sql)) {
    echo "cms_pages table checked/created successfully.\n";
} else {
    die("Error creating cms_pages table: " . $mysqli->error . "\n");
}

// 2. Insert default CMS page contents
// About Us Page Defaults
$about_default = [
    'story_kicker' => 'Our Story',
    'story_title' => 'We Built the Platform We Wished Existed',
    'story_sub' => 'Two expats — tired of chasing PRO agents, losing documents, and paying without any guarantee — decided to build a marketplace that puts customers first.',
    'story_cta_text' => 'Explore Services',
    'story_cta_url' => 'services.php',
    'stats' => [
        ['number' => '50,000+', 'label' => 'Happy Customers', 'highlight' => false],
        ['number' => '500+', 'label' => 'Verified Vendors', 'highlight' => true],
        ['number' => '99.8%', 'label' => 'Success Rate', 'highlight' => false],
        ['number' => '150+', 'label' => 'Nationalities Served', 'highlight' => false]
    ],
    'mission_kicker' => 'Our Mission',
    'mission_title' => 'Making UAE Documentation Simple, Safe & Stress-Free',
    'mission_copy' => 'We connect individuals and businesses with verified UAE service providers — with escrow payments, real-time tracking, and transparent pricing at every step. Every vendor on our platform is vetted for licensing, success rate, and customer satisfaction. We don\'t just list services; we guarantee accountability.',
    'mission_proof' => [
        'Verified all 500+ vendors for licensing, credentials, and compliance',
        'Protected over AED 48M in escrow payments for UAE customers',
        'Maintained a 99.8% application success rate across services',
        'Served customers from 150+ nationalities with real-time tracking',
        'Built transparent pricing with no hidden fees at any step'
    ],
    'values_kicker' => 'What We Stand For',
    'values_title' => 'Our Values',
    'values' => [
        ['icon' => 'bi-shield', 'title' => 'Trust First', 'desc' => 'Every decision we make starts with ‘does this protect our customers?’'],
        ['icon' => 'bi-lightning', 'title' => 'Radical Transparency', 'desc' => 'No hidden fees, no surprises. What you see is exactly what you get.'],
        ['icon' => 'bi-heart', 'title' => 'People-Centred', 'desc' => 'Behind every application is a person’s dream. We take that seriously.'],
        ['icon' => 'bi-graph-up-arrow', 'title' => 'Continuous Excellence', 'desc' => 'We obsess over making every part of the platform better, every day.']
    ],
    'journey_kicker' => 'Our Journey',
    'journey_title' => 'Six Years of Impact',
    'journey' => [
        ['year' => '2020', 'title' => 'Founded in Dubai', 'desc' => 'Started as a PRO services comparison tool by two frustrated expats'],
        ['year' => '2021', 'title' => '100 Vendors', 'desc' => 'Reached our first 100 verified vendor partners across 5 Emirates'],
        ['year' => '2022', 'title' => '10,000 Customers', 'desc' => 'Crossed 10,000 customers and launched our Document Vault'],
        ['year' => '2023', 'title' => 'Escrow Payments', 'desc' => 'Launched industry-first escrow payment protection for UAE services'],
        ['year' => '2024', 'title' => 'Series A Raised', 'desc' => 'Raised AED 16M to expand our vendor network and tech platform'],
        ['year' => '2026', 'title' => '50,000 Customers', 'desc' => 'Trusted by 50,000+ customers across 150+ nationalities']
    ]
];

// Contact Us Page Defaults
$contact_default = [
    'hero_kicker' => 'Contact',
    'hero_title' => 'Get in Touch',
    'hero_sub' => 'Our team responds within 2 hours during business hours. For urgent matters, reach us on WhatsApp.',
    'whatsapp' => '+971 4 400 0000',
    'whatsapp_url' => 'https://wa.me/97144000000',
    'whatsapp_meta' => 'Avg. reply · under 30 min',
    'email' => 'hello@globalways.ae',
    'email_meta' => 'Avg. reply · under 2 hours',
    'phone' => '+971 4 400 0000',
    'phone_meta' => 'Sun–Thu · 8:00 AM – 6:00 PM GST',
    'hq_title' => 'Dubai, UAE',
    'hq_address' => "GlobalWays Advisory\nDubai International Financial Centre\nLevel 6, Gate Avenue\nDubai, UAE 000001",
    'hours' => [
        ['days' => 'Sun – Thu', 'time' => '8:00 AM – 6:00 PM GST', 'closed' => false],
        ['days' => 'Friday', 'time' => '8:00 AM – 1:00 PM GST', 'closed' => false],
        ['days' => 'Saturday', 'time' => 'Closed', 'closed' => true]
    ]
];

// How It Works Page Defaults
$how_it_works_default = [
    'hero_kicker' => 'How It Works',
    'hero_title' => 'From Application to Approval in 4 Steps',
    'hero_sub' => 'We\'ve simplified UAE bureaucracy into a transparent, guaranteed process — whether you need a visa, a trade license, or an Emirates ID.',
    'steps' => [
        [
            'num' => '01',
            'icon' => 'bi-search',
            'title' => 'Browse & Compare Vendors',
            'desc' => 'Search 500+ verified vendors by service type, rating, price, language, and location. Every vendor has been background-checked and has real verified reviews.',
            'bullets' => [
                'Filter by service type, city, language & rating',
                'Read real reviews from verified customers',
                'Compare price, timeline & success rate side-by-side',
                'View vendor credentials and certifications'
            ],
            'mockup_label' => 'Top Vendors',
            'mockup_items' => [
                ['avatar' => 'E', 'name' => 'Emirates Pro Services', 'sub' => 'Golden Visa Specialist', 'rating' => '4.9', 'active' => false],
                ['avatar' => 'D', 'name' => 'Dubai Business Hub', 'sub' => 'Business Setup Expert', 'rating' => '4.9', 'active' => true],
                ['avatar' => 'A', 'name' => 'Al Maha Consultants', 'sub' => 'PRO Services Leader', 'rating' => '4.9', 'active' => false]
            ]
        ],
        [
            'num' => '02',
            'icon' => 'bi-credit-card',
            'title' => 'Book & Pay via Secure Escrow',
            'desc' => 'Select your vendor and pay through our secure platform. Your payment is held in escrow — the vendor only receives it after you confirm the work is done.',
            'bullets' => [
                'Pay by card, bank transfer or Apple/Google Pay',
                'Funds held in escrow until you confirm completion',
                'No upfront risk — money returned if vendor fails',
                'VAT receipts issued automatically'
            ],
            'mockup_label' => 'Order Summary',
            'mockup_title' => 'Golden Visa — Emirates Pro Services',
            'mockup_lines' => [
                ['label' => 'Vendor quote', 'amount' => 'AED 5,000'],
                ['label' => 'Platform Fee (3%)', 'amount' => 'AED 150'],
                ['label' => 'Government Fees (est.)', 'amount' => 'AED 2,720']
            ],
            'mockup_total' => 'AED 7,870'
        ],
        [
            'num' => '03',
            'icon' => 'bi-bell',
            'title' => 'Track Your Application Live',
            'desc' => 'Once booked, your vendor starts working and updates each milestone in real-time. You\'ll receive WhatsApp and email notifications at every stage.',
            'bullets' => [
                'FedEx-style milestone tracking dashboard',
                'WhatsApp & email notifications at every step',
                'Direct in-app messaging with your vendor',
                'Estimated completion date always visible'
            ],
            'mockup_label' => 'Application Tracker',
            'mockup_statuses' => [
                ['label' => 'Submitted', 'status' => 'done'],
                ['label' => 'Docs Verified', 'status' => 'done'],
                ['label' => 'Gov. Submitted', 'status' => 'done'],
                ['label' => 'Biometrics', 'status' => 'in-progress'],
                ['label' => 'Approved', 'status' => 'pending']
            ]
        ],
        [
            'num' => '04',
            'icon' => 'bi-check2-circle',
            'title' => 'Receive & Review',
            'desc' => 'Your documents are delivered to your encrypted Document Vault. Confirm delivery, rate your vendor, and access your documents forever.',
            'bullets' => [
                'Documents stored in encrypted cloud vault',
                'Download, share or forward to other services',
                'Rate your vendor to help other customers',
                'Renewal reminders sent before documents expire'
            ],
            'mockup_label' => 'Document Vault',
            'mockup_docs' => [
                ['name' => '🏆 Golden Visa Certificate.pdf', 'expires' => 'Expires 2031'],
                ['name' => '🪪 Emirates ID.pdf', 'expires' => 'Expires 2028'],
                ['name' => '✈️ Entry Stamp.pdf', 'expires' => 'Expires —']
            ]
        ]
    ],
    'vendor_kicker' => 'For Vendors',
    'vendor_title' => 'How Vendors Join & Grow',
    'vendor_sub' => 'Becoming a verified vendor is simple. Get discovered by thousands of customers actively searching for your services.',
    'vendor_steps' => [
        ['icon' => 'bi-cloud-arrow-up', 'step' => 'STEP 1', 'title' => 'Apply & Get Verified', 'desc' => 'Submit your business details, license and certifications. Reviewed within 48 hours.'],
        ['icon' => 'bi-globe2', 'step' => 'STEP 2', 'title' => 'Set Up Your Profile', 'desc' => 'List services, set pricing, upload portfolio, and connect your availability.'],
        ['icon' => 'bi-file-earmark-text', 'step' => 'STEP 3', 'title' => 'Receive Orders', 'desc' => 'Customers discover and book you directly. Respond and accept new orders.'],
        ['icon' => 'bi-arrow-repeat', 'step' => 'STEP 4', 'title' => 'Deliver & Get Paid', 'desc' => 'Update milestones, deliver outcomes, and get paid to your bank quickly.']
    ],
    'cta_kicker' => 'Ready to Start?',
    'cta_title' => 'UAE Documentation, Simplified.',
    'cta_sub' => 'Browse 500+ verified vendors and find the right one for your UAE documentation needs — for free.'
];

$pages = [
    'about' => $about_default,
    'contact' => $contact_default,
    'how_it_works' => $how_it_works_default
];

foreach ($pages as $p_name => $content_arr) {
    $stmt = $mysqli->prepare("INSERT INTO cms_pages (page_name, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = VALUES(content)");
    $json = json_encode($content_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt->bind_param('ss', $p_name, $json);
    if ($stmt->execute()) {
        echo "Default content for page '$p_name' upserted.\n";
    } else {
        echo "Error upserting '$p_name': " . $mysqli->error . "\n";
    }
    $stmt->close();
}

// 3. Create contact_messages table
$sql = "CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `topic` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `reply_text` TEXT DEFAULT NULL,
  `replied_at` DATETIME DEFAULT NULL,
  `replied_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($mysqli->query($sql)) {
    echo "contact_messages table checked/created successfully.\n";
} else {
    die("Error creating contact_messages table: " . $mysqli->error . "\n");
}

// 4. Create blog_posts table
$sql = "CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` CHAR(36) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `excerpt` TEXT DEFAULT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `category` VARCHAR(100) NOT NULL,
  `reading_time` VARCHAR(50) DEFAULT '5 min read',
  `author_user_id` INT UNSIGNED DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('draft', 'published') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($mysqli->query($sql)) {
    echo "blog_posts table checked/created successfully.\n";
} else {
    die("Error creating blog_posts table: " . $mysqli->error . "\n");
}

// 5. Add columns to providers table if not exists
$columns_to_add_providers = [
    'team_size' => "INT DEFAULT 1",
    'languages' => "VARCHAR(255) DEFAULT 'English'",
    'starting_price' => "DECIMAL(10,2) DEFAULT NULL",
    'specialties' => "TEXT DEFAULT NULL"
];
foreach ($columns_to_add_providers as $col => $definition) {
    $res = $mysqli->query("SHOW COLUMNS FROM providers LIKE '$col'");
    if ($res && $res->num_rows === 0) {
        if ($mysqli->query("ALTER TABLE providers ADD COLUMN `$col` $definition")) {
            echo "Column '$col' added to providers.\n";
        } else {
            echo "Error adding column '$col' to providers: " . $mysqli->error . "\n";
        }
    } else {
        echo "Column '$col' already exists in providers.\n";
    }
}

// 6. Add columns to users table if not exists
$columns_to_add_users = [
    'nationality' => "VARCHAR(100) DEFAULT NULL",
    'goal' => "VARCHAR(100) DEFAULT NULL",
    'emirate' => "VARCHAR(100) DEFAULT NULL"
];
foreach ($columns_to_add_users as $col => $definition) {
    $res = $mysqli->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($res && $res->num_rows === 0) {
        if ($mysqli->query("ALTER TABLE users ADD COLUMN `$col` $definition")) {
            echo "Column '$col' added to users.\n";
        } else {
            echo "Error adding column '$col' to users: " . $mysqli->error . "\n";
        }
    } else {
        echo "Column '$col' already exists in users.\n";
    }
}

// 7. Seed permissions and assign to Admin/Super Admin
$permissions_to_seed = [
    'cms.manage' => 'CMS: Manage',
    'blog.manage' => 'Blog: Manage',
    'messages.manage' => 'Contact Messages: Manage'
];

// Check roles: admin (id 1) and Super Admin (id 4)
foreach ($permissions_to_seed as $pname => $plabel) {
    $res = $mysqli->query("SELECT id FROM permissions WHERE name = '$pname' LIMIT 1");
    $pid = null;
    if ($res && $row = $res->fetch_assoc()) {
        $pid = $row['id'];
        echo "Permission '$pname' already exists (ID: $pid).\n";
    } else {
        $puuid = generate_uuid();
        $sql = "INSERT INTO permissions (uuid, name, label, description) VALUES ('$puuid', '$pname', '$plabel', 'Allows managing $pname')";
        if ($mysqli->query($sql)) {
            $pid = $mysqli->insert_id;
            echo "Permission '$pname' created successfully (ID: $pid).\n";
        } else {
            echo "Error creating permission '$pname': " . $mysqli->error . "\n";
        }
    }

    if ($pid) {
        // assign to role 1 (admin) and role 4 (Super Admin) if they exist
        $roles_to_assign = [1, 4];
        foreach ($roles_to_assign as $rid) {
            $res_role = $mysqli->query("SELECT id FROM roles WHERE id = $rid LIMIT 1");
            if ($res_role && $res_role->num_rows > 0) {
                $mysqli->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($rid, $pid)");
                echo "Permission '$pname' assigned to role ID $rid.\n";
            }
        }
    }
}

// 8. Seed sample blog posts if table is empty
$res_blog = $mysqli->query("SELECT COUNT(*) as cnt FROM blog_posts");
$cnt_row = $res_blog->fetch_assoc();
if ($cnt_row['cnt'] == 0) {
    // Let's get an existing user id as author
    $res_user = $mysqli->query("SELECT id FROM users LIMIT 1");
    $author_id = ($res_user && $row_user = $res_user->fetch_assoc()) ? $row_user['id'] : 'NULL';

    $sample_posts = [
        [
            'title' => 'Why Scaling Breaks Businesses Without Operating Discipline',
            'slug' => 'scaling-breaks-businesses',
            'excerpt' => 'Growth exposes weak systems, not market demand. Learn how to build operational discipline before you scale.',
            'content' => '<p class="lead">Growth exposes weak systems, not market demand. When revenue grows faster than your operations, cracks appear everywhere — and the businesses that survive are the ones that build structure before they scale.</p><h2>The False Comfort of Demand</h2><p>High demand often masks operational risk. When teams chase top-line growth without strong delivery systems, churn and service failures follow. Revenue can climb while customer satisfaction quietly erodes.</p><h2>Misaligned Leadership Compounds the Problem</h2><p>Strategy fails when execution ownership is unclear. Define accountability across teams and map every process handoff. Without shared visibility, each department optimizes locally while the customer experience breaks globally.</p><h2>Complexity as a Risk Factor</h2><p>As service lines increase, complexity multiplies. Standardized workflows and clear decision rights are the only way to scale sustainably. Every new offering should come with a defined owner and a measurable outcome.</p>',
            'category' => 'Consultancy',
            'reading_time' => '5 min read',
            'tags' => 'UAE,Documentation,Marketing,GlobalWays,2026',
            'status' => 'published',
            'image_url' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=85'
        ],
        [
            'title' => 'Strategy Fails When Execution Lacks Structural Accountability',
            'slug' => 'strategy-fails-without-accountability',
            'excerpt' => 'Most strategy documents fail not because the ideas are wrong, but because no one owns the outcome.',
            'content' => '<p class="lead">Most strategy documents fail not because the ideas are wrong, but because no one owns the outcome.</p><h2>The Accountability Gap</h2><p>When leadership rolls out major changes without individual owners, alignment decays. Accountability isn\'t about blame; it\'s about knowing exactly who is responsible for driving each milestone to completion.</p>',
            'category' => 'Consultancy',
            'reading_time' => '5 min read',
            'tags' => 'UAE,Business Setup,Strategy',
            'status' => 'published',
            'image_url' => 'https://images.unsplash.com/photo-1518684079-3c830dcef090?w=1200&q=85'
        ],
        [
            'title' => 'Growth Exposes Weak Systems, Not Market Demand',
            'slug' => 'growth-exposes-weak-systems',
            'excerpt' => 'When revenue grows faster than your operations, cracks appear everywhere. Here\'s how to stay ahead.',
            'content' => '<p class="lead">When revenue grows faster than your operations, cracks appear everywhere. Here\'s how to stay ahead.</p><p>We focus on simplifying ownership, strengthening systems, and aligning leadership around what actually moves your UAE journey forward.</p>',
            'category' => 'Marketing',
            'reading_time' => '4 min read',
            'tags' => 'Escrow,Payments,Secure',
            'status' => 'published',
            'image_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&q=85'
        ]
    ];

    foreach ($sample_posts as $post) {
        $buuid = generate_uuid();
        $stmt = $mysqli->prepare("INSERT INTO blog_posts (uuid, title, slug, excerpt, content, category, reading_time, author_user_id, image_url, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, $author_id, ?, ?, ?)");
        $stmt->bind_param('ssssssssss', $buuid, $post['title'], $post['slug'], $post['excerpt'], $post['content'], $post['category'], $post['reading_time'], $post['image_url'], $post['tags'], $post['status']);
        $stmt->execute();
        $stmt->close();
    }
    echo "Sample blog posts seeded.\n";
}

echo "Database migrations completed successfully.\n";
