<?php
/** Isolated local adapter. Not shipped in the plugin and never proxies requests. */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ['http://localhost:8000', 'http://127.0.0.1:8000', 'null'], true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
if ($path === '/assets/altcha.min.js') {
    header('Content-Type: application/javascript');
    readfile('/local-assets/altcha.min.js');
    exit;
}
if (preg_match('#^/widget/(booking|seminars)\.js$#', $path, $match)) {
    header('Content-Type: application/javascript');
    $file = '/widgets/' . $match[1] . '.js';
    if (!is_file($file)) { http_response_code(503); echo 'throw new Error("Build the real Tebuto widgets first")'; exit; }
    // Keep the built React implementation; substitute service origins for isolation.
    $bundle = str_replace('https://cdn.jsdelivr.net/npm/altcha/dist/altcha.min.js', 'http://localhost:8001/assets/altcha.min.js', file_get_contents($file));
    echo preg_replace('#https://(?:api\.|bff\.|widget\.|termin\.|link\.)?tebuto\.(?:local|dev|de)#', 'http://localhost:8001', $bundle);
    exit;
}
header('Content-Type: application/json');
function respond($data, $status = 200) { http_response_code($status); echo json_encode($data); exit; }
$stateFile = '/state/state.json';
$state = is_file($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
if ($path === '/__control') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $state = !empty($input['reset']) ? [] : array_merge($state, $input);
        file_put_contents($stateFile, json_encode($state), LOCK_EX);
    }
    respond($state ?: (object)[]);
}
if ($path === '/runtimeEnv') respond(['flags' => ['connectTebutoMeetEnabled' => false, 'connectZoomEnabled' => true, 'connectMicrosoftTeamsEnabled' => true, 'connectGoogleCalendarEnabled' => true, 'connectOutlookEnabled' => true, 'optionalClientDataEnabled' => false, 'secureMessagesEnabled' => false], 'payPal' => ['clientId' => '']]);
if ($path === '/health') respond(['status' => 'ok', 'mode' => 'synthetic-local-api']);
$therapist = ['id' => 1, 'uuid' => '11111111-1111-4111-8111-111111111111', 'slug' => 'lokale-vorschau', 'name' => 'Lokale Testpraxis', 'firstName' => 'Lena', 'lastName' => 'Beispiel', 'type' => 'therapist', 'image' => null, 'description' => null, 'title' => null, 'showWatermark' => false, 'address' => ['streetAndNumber' => 'Beispielweg 1', 'city' => ['name' => 'Berlin', 'zip' => '10115']], 'settings' => ['bookingEnabled' => true, 'clientPhoneNumberRequired' => false, 'clientAddressRequired' => false], 'features' => ['featureSeminarsEnabled' => true, 'featureSeminarsAvailable' => true]];
$category = ['id' => 101, 'name' => 'Erstgespräch (Test)', 'description' => 'Lokale Beispieldaten', 'color' => '#00B4A9', 'price' => '80.00', 'taxRate' => '0', 'duration' => 50, 'location' => 'onsite', 'publicBookingEnabled' => true, 'privateBookingEnabled' => true, 'outageFeeEnabled' => false, 'paymentEnabled' => false, 'paymentDuringBooking' => false];
$privateCategory = array_merge($category, ['id' => 102, 'name' => 'Privater Folgetermin (Test)', 'publicBookingEnabled' => false]);
$secondCategory = array_merge($category, ['id' => 103, 'name' => 'Beratung (Test)']);
$categories = $state['categoryData'] ?? [$category, $privateCategory, $secondCategory];
$seminar = ['id' => 201, 'slug' => 'achtsamkeit-test', 'title' => 'Achtsamkeit (Testseminar)', 'subtitle' => null, 'topic' => 'Lokale Vorschau', 'description' => 'Synthetische Beispieldaten', 'price' => '25.00', 'taxRate' => '0', 'locationType' => 'onsite', 'locationName' => 'Testpraxis', 'bannerUrl' => null, 'streetAndNumber' => 'Beispielweg 1', 'cityZip' => '10115', 'cityName' => 'Berlin', 'additionalInformation' => null, 'requireEmailConfirmation' => false, 'isInherited' => false, 'publicPageEnabled' => true, 'openOccurrences' => []];
$occurrence = ['id' => 601, 'publicId' => '22222222-2222-4222-8222-222222222222', 'status' => 'registration_open', 'registrationOpensAt' => null, 'registrationClosesAt' => null, 'label' => 'Lokaler Beispieltermin', 'capacity' => 10, 'remainingSeats' => 8, 'waitlistEnabled' => false, 'price' => '25.00', 'taxRate' => '0', 'paymentEnabled' => false, 'locationType' => 'onsite', 'locationName' => 'Testpraxis', 'streetAndNumber' => 'Beispielweg 1', 'cityZip' => '10115', 'cityName' => 'Berlin', 'additionalInformation' => null, 'outageFeeEnabled' => false, 'outageFeeDays' => null, 'outageFeePrice' => null, 'therapistUuid' => $therapist['uuid'], 'therapistName' => $therapist['name'], 'therapistTitle' => null, 'therapistImage' => null, 'therapistDescription' => null, 'sessions' => [['id' => 701, 'position' => 0, 'label' => 'Achtsamkeit (Beispieldaten)', 'start' => gmdate('Y-m-d\\T14:00:00\\Z', strtotime('+14 days')), 'end' => gmdate('Y-m-d\\T16:00:00\\Z', strtotime('+14 days'))]]];
$seminar['openOccurrences'] = [$occurrence];
$privateSeminar = array_merge($seminar, ['id' => 202, 'slug' => 'privates-testseminar', 'title' => 'Privates Testseminar', 'publicPageEnabled' => false]);
if ($path === '/who-am-i') respond(['id' => 1, 'isMultiUserManager' => false, 'therapists' => [['therapist' => $therapist]]]);
if ($path === '/therapists/1' || str_starts_with($path, '/therapists/uuid/')) respond($therapist);
if (preg_match('#/event-categories(?:/(\d+))?$#', $path, $match)) {
    if (($state['categories'] ?? '') === 'error') respond(['message' => 'Lokaler Testfehler: Kategorien nicht verfügbar.'], 503);
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST') { $body['id'] = time(); $categories[] = $body; }
    if ($method === 'PUT') { foreach ($categories as &$cat) { if ($cat['id'] === (int)$match[1]) $cat = array_merge($cat, $body); } unset($cat); }
    if ($method === 'DELETE') $categories = array_values(array_filter($categories, fn($cat) => $cat['id'] !== (int)$match[1]));
    if ($method !== 'GET') { $state['categoryData'] = $categories; file_put_contents($stateFile, json_encode($state), LOCK_EX); respond($body ?: ['success' => true]); }
    respond($categories);
}
$booking = ['id' => 501, 'isConfirmed' => false, 'locationSelection' => 'onsite', 'client' => ['firstName' => 'Anna', 'lastName' => 'Testperson', 'email' => 'anna@example.test'], 'event' => ['id' => 401, 'status' => $state['bookingStatus'] ?? 'booked', 'category' => $category, 'start' => gmdate('Y-m-d\TH:00:00\Z', strtotime('+2 days')), 'end' => gmdate('Y-m-d\TH:50:00\Z', strtotime('+2 days'))]];
if ($path === '/therapists/1/bookings') respond(['bookings' => [$booking], 'total' => 1, 'totalItems' => 1, 'page' => 1, 'totalPages' => 1]);
if (preg_match('#^/therapists/1/bookings/501/(confirm|reject|cancel)$#', $path, $match)) {
    if (($state['bookingActions'] ?? '') === 'error') respond(['message' => 'Lokaler Testfehler: Buchung konnte nicht geändert werden.'], 503);
    $state['bookingStatus'] = ['confirm' => 'approved', 'reject' => 'rejected', 'cancel' => 'cancelled'][$match[1]];
    file_put_contents($stateFile, json_encode($state), LOCK_EX);
    respond(['success' => true]);
}
if ($path === '/therapists/1/events') respond([]);
if (preg_match('#^/events/[a-f0-9-]+$#', $path)) {
    $visibleCategories = array_values(array_filter($categories, fn($cat) => !empty($cat['publicBookingEnabled'])));
    if (isset($_GET['categories'])) {
        $selectedIds = array_map('intval', explode(',', $_GET['categories']));
        $visibleCategories = array_values(array_filter($visibleCategories, fn($cat) => in_array($cat['id'], $selectedIds, true)));
    }
    $events = [];
    foreach ($visibleCategories as $index => $publicCategory) {
        $day = strtotime('+' . ($index + 2) . ' days');
        $events[] = array_merge($publicCategory, ['title' => $publicCategory['name'], 'start' => gmdate('Y-m-d\T10:00:00\Z', $day), 'end' => gmdate('Y-m-d\T10:50:00\Z', $day), 'eventRuleId' => 300 + $publicCategory['id'], 'eventCategoryId' => $publicCategory['id'], 'therapist' => $therapist, 'featurePaymentEnabled' => false]);
    }
    respond(['events' => $events, 'categories' => $visibleCategories, 'bookingNote' => 'Lokale Vorschau mit Beispieldaten – keine echte Buchung.', 'websiteEmbedEnabled' => true]);
}
if (preg_match('#^/events/[a-f0-9-]+/claim$#', $path) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    respond(['isAvailable' => true, 'requirePhoneNumber' => false, 'requireAddress' => false, 'requireBirthdate' => false]);
}
if (preg_match('#^/events/[a-f0-9-]+/unclaim$#', $path) && $_SERVER['REQUEST_METHOD'] === 'POST') respond(['success' => true]);
if (preg_match('#^/events/[a-f0-9-]+/payment-configuration$#', $path)) respond(['paymentTypes' => ['invoice'], 'onlinePaymentMethods' => [], 'onlineProviderType' => null]);
if ($path === '/widget-auth/me') respond(['message' => 'Nicht angemeldet'], 401);
if (str_starts_with($path, '/seminars/bootstrap/')) respond(['seminarsPageTitle' => 'Seminare (lokale Vorschau)', 'seminarsPageIntro' => 'Beispieldaten', 'seminarsPageLayout' => 'cards', 'therapistUuid' => $therapist['uuid'], 'therapistSlug' => $therapist['slug'], 'therapistName' => $therapist['name'], 'therapistImage' => null, 'therapistDescription' => null, 'therapistTitle' => null, 'theme' => null, 'logoUrl' => null, 'clientPhoneNumberRequired' => false, 'clientAddressRequired' => false, 'dataPrivacyAgreementUrl' => null, 'termsAndConditionsUrl' => null, 'therapistSeminarsPageEnabled' => true, 'seminars' => [$seminar]]);
if (str_starts_with($path, '/therapists/1/seminars')) {
    if (($state['seminars'] ?? '') === 'error') respond(['message' => 'Lokaler Testfehler: Seminare nicht verfügbar.'], 503);
    if (str_ends_with($path, '/occurrences')) respond([]);
    if ($path !== '/therapists/1/seminars') respond($seminar);
    respond([$seminar, $privateSeminar]);
}
// Unknown writes (booking, payments, OAuth) fail closed instead of resembling success.
respond(['message' => 'Dieser Endpunkt ist in der isolierten lokalen Vorschau nicht implementiert.', 'path' => $path], 501);
