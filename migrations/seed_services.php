<?php
// seed_services.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/uuid_helper.php';

echo "Seeding public published services...\n";

// Fetch first provider id
$p_res = $mysqli->query("SELECT id FROM providers LIMIT 1");
if (!$p_res || $p_res->num_rows === 0) {
    die("No providers found to assign services to. Run migration first.\n");
}
$provider_id = $p_res->fetch_assoc()['id'];

// Seed Service Categories
$categories = [
    'visa' => 'Visa Services',
    'business' => 'Business Setup',
    'documentation' => 'Documentation',
    'financial' => 'Financial'
];
$cat_ids = [];

foreach ($categories as $slug => $name) {
    $res = $mysqli->query("SELECT id FROM service_categories WHERE slug = '$slug' OR name = '$name' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $cat_ids[$slug] = $row['id'];
    } else {
        $cuuid = generate_uuid();
        $mysqli->query("INSERT INTO service_categories (uuid, name, slug, description) VALUES ('$cuuid', '" . $mysqli->real_escape_string($name) . "', '$slug', 'All services related to $name')");
        $cat_ids[$slug] = $mysqli->insert_id;
    }
}

// Published Services
$services_data = [
    [
        'title' => 'Golden Visa',
        'slug' => 'golden-visa',
        'short_description' => 'Long-term residency visa for investors, entrepreneurs, and exceptional talent',
        'description' => 'The UAE Golden Visa offers long-term residency for investors, entrepreneurs, specialized talents, and outstanding students — without the need for a local sponsor. GlobalWays connects you with verified vendors who handle eligibility assessment, filing, biometrics, and follow-through until approval.',
        'price' => 5000.00,
        'currency' => 'AED',
        'duration_minutes' => 5 * 24 * 60, // 5 days
        'category' => 'visa',
        'rating_avg' => 4.9,
        'rating_count' => 1250
    ],
    [
        'title' => 'Investor Visa',
        'slug' => 'investor-visa',
        'short_description' => 'Residency visa for property investors and business owners',
        'description' => 'Obtain UAE residency through property or business investment with guided vendor matching. Investor visas open long-term residency pathways through property purchase or qualifying business investment.',
        'price' => 4000.00,
        'currency' => 'AED',
        'duration_minutes' => 7 * 24 * 60, // 7 days
        'category' => 'visa',
        'rating_avg' => 4.8,
        'rating_count' => 980
    ],
    [
        'title' => 'Family Visa',
        'slug' => 'family-visa',
        'short_description' => 'Sponsor your family members to join you in the UAE',
        'description' => 'Sponsor your spouse, children, and dependents with streamlined family visa processing. Family visas let eligible sponsors bring dependents to the UAE with clear documentation and processing steps handled by verified vendors.',
        'price' => 3000.00,
        'currency' => 'AED',
        'duration_minutes' => 5 * 24 * 60, // 5 days
        'category' => 'visa',
        'rating_avg' => 4.9,
        'rating_count' => 620
    ],
    [
        'title' => 'Employment Visa',
        'slug' => 'employment-visa',
        'short_description' => 'Work permit and residency for employed professionals',
        'description' => 'Work permits and employment visas for all nationalities and business sectors. Employment visas cover labour contracts, MOHRE processing, and status changes — coordinated end-to-end by verified PRO partners.',
        'price' => 2500.00,
        'currency' => 'AED',
        'duration_minutes' => 4 * 24 * 60,
        'category' => 'visa',
        'rating_avg' => 4.8,
        'rating_count' => 48
    ],
    [
        'title' => 'Business Setup',
        'slug' => 'business-setup',
        'short_description' => 'Full company formation, trade license & bank account opening',
        'description' => 'End-to-end company formation including trade license, visa quotas, and bank account support. From activity selection to trade license and banking introductions, verified setup partners guide your company formation.',
        'price' => 8000.00,
        'currency' => 'AED',
        'duration_minutes' => 3 * 24 * 60,
        'category' => 'business',
        'rating_avg' => 4.9,
        'rating_count' => 310
    ],
    [
        'title' => 'Emirates ID',
        'slug' => 'emirates-id',
        'short_description' => 'National ID application, biometrics & renewal services',
        'description' => 'Emirates ID application, biometrics, renewal, and replacement services. Emirates ID filing, biometrics coordination, and renewal reminders — handled by ICP-experienced vendors.',
        'price' => 500.00,
        'currency' => 'AED',
        'duration_minutes' => 2 * 24 * 60,
        'category' => 'financial',
        'rating_avg' => 4.8,
        'rating_count' => 1200
    ],
    [
        'title' => 'PRO Services',
        'slug' => 'pro-services',
        'short_description' => 'Attestation, stamping, and government coordination under one desk',
        'description' => 'Government liaison, document attestation, stamping, and typing services. PRO services cover government typing, attestation, and submissions with same-day options from verified local partners.',
        'price' => 1500.00,
        'currency' => 'AED',
        'duration_minutes' => 1 * 24 * 60,
        'category' => 'documentation',
        'rating_avg' => 4.9,
        'rating_count' => 157
    ]
];

foreach ($services_data as $s) {
    $res = $mysqli->query("SELECT id FROM services WHERE slug = '{$s['slug']}' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        // Update existing service status to published
        $mysqli->query("UPDATE services SET status = 'published', price = {$s['price']}, rating_avg = {$s['rating_avg']}, rating_count = {$s['rating_count']} WHERE slug = '{$s['slug']}'");
        echo "Service '{$s['title']}' updated to published.\n";
    } else {
        $suuid = generate_uuid();
        $cat_id = $cat_ids[$s['category']];
        $sql = "INSERT INTO services (uuid, provider_id, category_id, title, slug, short_description, description, price, currency, duration_minutes, status, rating_avg, rating_count, created_at)
                VALUES ('$suuid', $provider_id, $cat_id, '{$s['title']}', '{$s['slug']}', '" . $mysqli->real_escape_string($s['short_description']) . "', '" . $mysqli->real_escape_string($s['description']) . "', {$s['price']}, '{$s['currency']}', {$s['duration_minutes']}, 'published', {$s['rating_avg']}, {$s['rating_count']}, NOW())";
        if ($mysqli->query($sql)) {
            echo "Service '{$s['title']}' seeded successfully.\n";
        } else {
            echo "Error seeding '{$s['title']}': " . $mysqli->error . "\n";
        }
    }
}

// Update first provider (GoProAlpha) as active and verified with extra details so they look beautiful!
$mysqli->query("UPDATE providers SET status = 'active', verification_status = 'verified', rating_avg = 4.9, rating_count = 250, team_size = 15, languages = 'English, Arabic, Russian, Urdu', starting_price = 500.00, specialties = 'Golden Visa, Business Setup, Family Visa, PRO Services' WHERE id = 1");
echo "Provider 'GoProAlpha' updated to active/verified.\n";

echo "Seeding completed successfully!\n";
