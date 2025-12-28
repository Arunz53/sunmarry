<?php
require_once 'auth.php';
requireLogin();

// Ensure `deleted_at` column exists in `profiles` to avoid SQL errors when filtering
try {
    $pdo = getDB();
    $colStmt = $pdo->query("SHOW COLUMNS FROM profiles");
    $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'Field');
    if (!in_array('deleted_at', $colNames)) {
        // Add nullable deleted_at column for soft deletes
        $pdo->exec("ALTER TABLE profiles ADD COLUMN deleted_at DATETIME DEFAULT NULL");
    }
    if (!in_array('dosham', $colNames)) {
        $pdo->exec("ALTER TABLE profiles ADD COLUMN dosham VARCHAR(255) DEFAULT ''");
    }
} catch (PDOException $e) {
    // If the table doesn't exist or user lacks privileges, continue so the page can still load where possible.
}

// Initialize filter variables
$sort_id = isset($_GET['sort_id']) ? $_GET['sort_id'] : '';
$id_from = isset($_GET['id_from']) ? trim($_GET['id_from']) : '';
$id_to = isset($_GET['id_to']) ? trim($_GET['id_to']) : '';
$age_from = $_GET['age_from'] ?? '';
$age_to = $_GET['age_to'] ?? '';
$gender = $_GET['gender'] ?? '';
$selectedDistricts = $_GET['districts'] ?? [];
$city = $_GET['city'] ?? '';
$marriage_type = $_GET['marriage_type'] ?? '';
$selectedCastes = isset($_GET['castes']) ? (array)$_GET['castes'] : [];
$selectedNakshatram = isset($_GET['nakshatram']) ? (array)$_GET['nakshatram'] : [];
$selectedEducation = isset($_GET['education']) ? (array)$_GET['education'] : [];
$selectedDosham = isset($_GET['dosham']) ? (array)$_GET['dosham'] : [];
$phone = $_GET['phone'] ?? ''; // mobile search (visible to super_admin & manager only in UI)
$name = $_GET['name'] ?? '';

// Define caste and subcaste relationships
// Subcaste mapping removed (subcaste is no longer treated as a separate searchable field)

// Castes and their subcastes are defined in select elements and JavaScript

$nakshatramOptions = [
    'அசுவினி', 'பரணி', 'கிருத்திகை', 'ரோஹிணி', 'மிருகசீரிடம்', 'திருவாதிரை', 'புனர்பூசம்', 'பூசம்', 'ஆயில்யம்', 'மகம்', 'பூரம்', 'உத்திரம்', 'ஹஸ்தம்', 'சித்திரை', 'சுவாதி', 'விசாகம்', 'அனுஷம்', 'கேட்டை', 'மூலம்', 'பூராடம்', 'உத்திராடம்', 'திருவோணம்', 'அவிட்டம்', 'சதயம்', 'பூரட்டாதி', 'உத்திரட்டாதி', 'ரேவதி'
];

$doshamOptions = [
    'ராகு கேது',
    'பரிகார செவ்வாய்',
    'சுத்த ஜாதகம்'
];

$educationOptions = [
    '10 ஆம் வகுப்பு, 12 ஆம் வகுப்பு, ஐ.டி.ஐ, டிப்ளமோ',
    'இளங்கலை (UG)',
    'முதுகலை (PG)'
];

// Prepare WHERE clause
if ($id_from !== '' && $id_to !== '') {
    $where[] = "id BETWEEN ? AND ?";
    $params[] = min($id_from, $id_to);
    $params[] = max($id_from, $id_to);
} elseif ($id_from !== '') {
    $where[] = "id >= ?";
    $params[] = $id_from;
} elseif ($id_to !== '') {
    $where[] = "id <= ?";
    $params[] = $id_to;
}

// Initialize where/params and by default exclude deleted profiles unless explicitly requested
$where = [];
$params = [];
$show_deleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1';
if (!$show_deleted) {
    $where[] = 'deleted_at IS NULL';
}

// Handle ID range filter (moved below initialization)
if ($id_from !== '' && $id_to !== '') {
    $where[] = "id BETWEEN ? AND ?";
    $params[] = min($id_from, $id_to);
    $params[] = max($id_from, $id_to);
} elseif ($id_from !== '') {
    $where[] = "id >= ?";
    $params[] = $id_from;
} elseif ($id_to !== '') {
    $where[] = "id <= ?";
    $params[] = $id_to;
}

// Handle age range filter
if ($age_from && $age_to) {
    $where[] = "age BETWEEN ? AND ?";
    $params[] = min($age_from, $age_to); // Use min to handle if user selects "to" less than "from"
    $params[] = max($age_from, $age_to);
} elseif ($age_from) {
    $where[] = "age >= ?";
    $params[] = $age_from;
} elseif ($age_to) {
    $where[] = "age <= ?";
    $params[] = $age_to;
}

if ($gender) {
    $where[] = "gender = ?";
    $params[] = $gender;
}
if (!empty($selectedDistricts)) {
    $placeholders = str_repeat('?,', count($selectedDistricts) - 1) . '?';
    $where[] = "district IN ($placeholders)";
    $params = array_merge($params, $selectedDistricts);
}
if ($city) {
    $where[] = "city LIKE ?";
    $params[] = "%$city%";
}

if ($marriage_type) {
    $where[] = "marriage_type = ?";
    $params[] = $marriage_type;

    }

    if (!empty($selectedCastes)) {
        $placeholders = str_repeat('?,', count($selectedCastes) - 1) . '?';
        $where[] = "caste IN ($placeholders)";
        $params = array_merge($params, $selectedCastes);
    }

// subcaste filter removed

if (!empty($selectedNakshatram)) {
    $placeholders = str_repeat('?,', count($selectedNakshatram) - 1) . '?';
    $where[] = "nakshatram IN ($placeholders)";
    $params = array_merge($params, $selectedNakshatram);
}

if (!empty($selectedEducation)) {
    // stored column name is education_type in the database
    $placeholders = str_repeat('?,', count($selectedEducation) - 1) . '?';
    $where[] = "education_type IN ($placeholders)";
    $params = array_merge($params, $selectedEducation);
}

if (!empty($selectedDosham)) {
    $placeholders = str_repeat('?,', count($selectedDosham) - 1) . '?';
    $where[] = "dosham IN ($placeholders)";
    $params = array_merge($params, $selectedDosham);
}

// Mobile / phone search (matches primary, secondary or tertiary phones)
if (!empty($phone)) {
    $where[] = "(phone_primary LIKE ? OR phone_secondary LIKE ? OR phone_tertiary LIKE ?)";
    $like = "%$phone%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($name) {
    $where[] = "name LIKE ?";
    $params[] = "%$name%";
}

// Build the query
// Sorting
$orderBy = '';
if ($sort_id === 'asc') {
    $orderBy = ' ORDER BY id ASC';
} elseif ($sort_id === 'desc') {
    $orderBy = ' ORDER BY id DESC';
}

$sql = "SELECT * FROM profiles";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= $orderBy;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Count total records for pagination
$countSql = "SELECT COUNT(*) FROM profiles" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Get paginated results
$sql .= " LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll();

// Tamil Nadu districts map: value => label (English => Tamil)
$districtsMap = [
    'Ariyalur' => 'அரியலூர்',
    'Chennai' => 'சென்னை',
    'Coimbatore' => 'கோயம்புத்தூர்',
    'Cuddalore' => 'கடலூர்',
    'Dharmapuri' => 'தர்மபுரி',
    'Dindigul' => 'திண்டுக்கல்',
    'Erode' => 'ஈரோடு',
    'Kallakurichi' => 'கள்ளக்குறிச்சி',
    'Kanchipuram' => 'காஞ்சிபுரம்',
    'Kanyakumari' => 'கன்னியாகுமரி',
    'Karur' => 'கரூர்',
    'Krishnagiri' => 'கிருஷ்ணகிரி',
    'Madurai' => 'மதுரை',
    'Nagapattinam' => 'நாகப்பட்டினம்',
    'Namakkal' => 'நாமக்கல்',
    'Nilgiris' => 'நீலகிரி',
    'Perambalur' => 'பெரம்பலூர்',
    'Pudukkottai' => 'புதுக்கோட்டை',
    'Ramanathapuram' => 'ராமநாதபுரம்',
    'Salem' => 'சேலம்',
    'Sivaganga' => 'சிவகங்கை',
    'Thanjavur' => 'தஞ்சாவூர்',
    'Theni' => 'தேனி',
    'Thoothukudi' => 'தூத்துக்குடி',
    'Tiruchirappalli' => 'திருச்சிராப்பள்ளி',
    'Tirunelveli' => 'திருநெல்வேலி',
    'Tiruppur' => 'திருப்பூர்',
    'Tiruvallur' => 'திருவல்லூர்',
    'Tiruvannamalai' => 'திருவண்ணாமலை',
    'Tiruvarur' => 'திருவாரூர்',
    'Vellore' => 'வேலூர்',
    'Viluppuram' => 'விழுப்புரம்',
    'Virudhunagar' => 'விருதுநகர்'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profiles - Marriage Profile System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
            padding: 0;
        }
        .dropdown-item {
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-item.active {
            background-color: #e9ecef;
            color: #000;
        }
        .dropdown-item input[type="checkbox"] {
            margin: 0;
        }
        .district-dropdown {
            width: 100%;
        }
        .district-dropdown button {
            width: 100%;
            text-align: left;
            position: relative;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 25px;
        }
        .district-dropdown button::after {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        .search-box {
            position: sticky;
            top: 0;
            background-color: white;
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            z-index: 1;
        }
        .search-box button {
            padding: 4px 8px;
            font-size: 0.875rem;
        }
        .search-box button:focus {
            box-shadow: none;
        }
        #selectedCount {
            font-size: 0.875rem;
            color: #6c757d;
            margin-left: 8px;
        }
        
        /* Table styles */
        .table {
            font-size: 14px;
        }
        .table th {
            background-color: #f8f9fa;
            vertical-align: middle;
        }
        .table td {
            vertical-align: middle;
        }
        .table .btn-sm {
             margin: 10px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Custom container width for larger screens */
        @media (min-width: 1400px) {
            .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl {
                max-width: 1520px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>சுயவிவரங்களை காண்</h2>
            <?php if (getUserRole() === 'support'): 
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT profiles_viewed FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            ?>
                <div class="alert alert-info mb-0">
                    பார்வையிட்ட சுயவிவரங்கள்: <strong><?php echo $user['profiles_viewed']; ?>/10</strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filter Form - Admin & Manager Only -->
        <?php if (getUserRole() !== 'support'): ?>
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <!-- Row 1: திருமண வகை / பாலினம்/ வயது -->
                    <div class="col-md-4">
                        <label class="form-label">திருமண வகை</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="marriage_type" id="first_marriage" value="First" <?php echo (empty($marriage_type) || $marriage_type === 'First') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="first_marriage">முதல்மணம்</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="marriage_type" id="second_marriage" value="Second" <?php echo $marriage_type === 'Second' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="second_marriage">இரண்டாம் திருமணம்</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">பாலினம்</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" id="female" value="Female" <?php echo ($gender === 'Female' || $gender === '') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="female">பெண்</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="gender" id="male" value="Male" <?php echo $gender === 'Male' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="male">ஆண்</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">வயது</label>
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select" id="age_from" name="age_from">
                                <option value="">முதல்</option>
                                <?php for($i = 18; $i <= 55; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo isset($_GET['age_from']) && $_GET['age_from'] == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <span>வரை</span>
                            <select class="form-select" id="age_to" name="age_to">
                                <option value=""></option>
                                <?php for($i = 18; $i <= 55; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo isset($_GET['age_to']) && $_GET['age_to'] == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: சாதி பெயர் (Caste)/ படிப்பு (Education)/ மாவட்டங்கள் -->
                    <div class="col-md-4">
                        <label class="form-label">சாதி பெயர் (Caste)</label>
                        <div class="district-dropdown dropdown">
                            <button class="btn btn-light border dropdown-toggle" type="button" id="casteDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                சாதி தேர்வு
                                <span id="selectedCasteCount"></span>
                            </button>
                            <div class="dropdown-menu w-100" aria-labelledby="casteDropdown">
                                <div class="search-box">
                                    <input type="text" class="form-control form-control-sm" id="casteSearch" placeholder="Search caste...">
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllCastes">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllCastes">Clear All</button>
                                    </div>
                                </div>
                                <?php 
                                $casteOptions = [
                                    '24 மனை தெலுங்கு (8 வீடு)',
                                    '24 மனை தெலுங்கு (16 வீடு)',
                                    'கவுண்டர் (கொங்கு வெள்ளாள கவுண்டர்)',
                                    'கவுண்டர் (வேட்டுவ கவுண்டர்)',
                                    'கவுண்டர் (குறும்ப கவுண்டர்)',
                                    'நாயுடு (கம்மவார் நாயுடு)',
                                    'நாயுடு (கவரா நாயுடு)',
                                    'நாயுடு (பலிஜா நாயுடு)',
                                    'செட்டியார் (கன்னட தேவாங்க செட்டியார்)',
                                    'செட்டியார் (தெலுங்கு தேவாங்க செட்டியார்)',
                                    'செட்டியார் (வாணிய செட்டியார்)',
                                    'செட்டியார் (கொங்கு செட்டியார்)',
                                    'செட்டியார் (சைவ செட்டியார்)',
                                    'செட்டியார் (நாட்டுக்கோட்டை செட்டியார்)',
                                    'செட்டியார் (ஆரிய வைசியர்)',
                                    'தேவர் (அகமுடையார்)',
                                    'தேவர் (மறவர்)',
                                    'தேவர் (கள்ளர்)',
                                    'விஸ்வகர்மா (தமிழ்)',
                                    'விஸ்வகர்மா (தெலுங்கு)',
                                    'விஸ்வகர்மா (மலையாளம்)',
                                    'பிராமின் (ஐயங்கார்)',
                                    'பிராமின் (அய்யர்)',
                                    'பிராமின் (மத்வா - கன்னட பிராமின்)',
                                    'பிராமின் (தெலுங்கு பிராமின்)',
                                    'பிராமின் (குருக்கள்)',
                                    'கிறிஸ்டியன் (RC)',
                                    'கிறிஸ்டியன் (CSI)',
                                    'கிறிஸ்டியன் (Pentecost)',
                                    'முஸ்லிம் (தமிழ் முஸ்லிம்)',
                                    'முஸ்லிம் (உருது முஸ்லிம்)',
                                    'வன்னியர்',
                                    'மருத்துவர்',
                                    'நாடார்',
                                    'முதலியார்',
                                    'பிள்ளை',
                                    'முத்திரையர் / முத்துராஜா / அம்பலக்காரர்',
                                    'உடையார் / குலாலர்',
                                    'ரெட்டியார்',
                                    'ஒக்கலிக கவுடர்',
                                    'சௌராஷ்டிரா',
                                    'மூப்பனார்',
                                    'நாயர்',
                                    'ஈழவா',
                                    'ஜங்கம் / பண்டாரம் / வீர சைவம்',
                                    'போயர்',
                                    'தேவேந்திர குல வெள்ளாளர்',
                                    'அருந்ததியர்',
                                    'ஆதி திராவிடர்',
                                    'நாயக்கர்',
                                    'யாதவா / கோணார்',
                                    'வண்ணார்',
                                    'சேனைத் தலைவர்',
                                    'வள்ளுவர்',
                                    'குறவர்',
                                    'மீனவர்'
                                ];
                                $selectedCastes = isset($_GET['castes']) ? (array)$_GET['castes'] : [];
                                foreach($casteOptions as $casteOpt): ?>
                                <label class="dropdown-item">
                                    <input type="checkbox" name="castes[]" value="<?php echo htmlspecialchars($casteOpt); ?>" <?php echo in_array($casteOpt, $selectedCastes) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($casteOpt); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">படிப்பு (Education)</label>
                        <div class="district-dropdown dropdown">
                            <button class="btn btn-light border dropdown-toggle" type="button" id="educationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                படிப்பு தேர்வு
                                <span id="selectedEducationCount"></span>
                            </button>
                            <div class="dropdown-menu w-100" aria-labelledby="educationDropdown">
                                <div class="search-box">
                                    <input type="text" class="form-control form-control-sm" id="educationSearch" placeholder="Search education...">
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllEducation">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllEducation">Clear All</button>
                                    </div>
                                </div>
                                <?php 
                                $selectedEducation = isset($_GET['education']) ? (array)$_GET['education'] : [];
                                foreach($educationOptions as $option): ?>
                                <label class="dropdown-item">
                                    <input type="checkbox" name="education[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $selectedEducation) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">மாவட்டங்கள்</label>
                        <div class="district-dropdown dropdown">
                            <button class="btn btn-light border dropdown-toggle" type="button" id="districtDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                மாவட்டம் தேர்வு
                                <span id="selectedCount"></span>
                            </button>
                            <div class="dropdown-menu w-100" aria-labelledby="districtDropdown">
                                <div class="search-box">
                                    <input type="text" class="form-control form-control-sm" id="districtSearch" placeholder="Search districts...">
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllDistricts">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllDistricts">Clear All</button>
                                    </div>
                                </div>
                                <?php foreach($districtsMap as $en => $ta): ?>
                                <label class="dropdown-item">
                                    <input type="checkbox" name="districts[]" value="<?php echo htmlspecialchars($en); ?>" 
                                           <?php echo (isset($selectedDistricts) && in_array($en, (array)$selectedDistricts)) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($ta); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: தோசம் (Dosham)/ நட்சத்திரம் (Nakshatram)/ வசிக்கும் ஊர் -->
                    <div class="col-md-4">
                        <label class="form-label">தோசம் (Dosham)</label>
                        <div class="district-dropdown dropdown">
                            <button class="btn btn-light border dropdown-toggle" type="button" id="doshamDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                தோசம் தேர்வு
                                <span id="selectedDoshamCount"></span>
                            </button>
                            <div class="dropdown-menu w-100" aria-labelledby="doshamDropdown">
                                <div class="search-box">
                                    <input type="text" class="form-control form-control-sm" id="doshamSearch" placeholder="Search dosham...">
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllDosham">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllDosham">Clear All</button>
                                    </div>
                                </div>
                                <?php 
                                $selectedDosham = isset($_GET['dosham']) ? (array)$_GET['dosham'] : [];
                                foreach($doshamOptions as $option): ?>
                                <label class="dropdown-item">
                                    <input type="checkbox" name="dosham[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $selectedDosham) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">நட்சத்திரம் (Nakshatram)</label>
                        <div class="district-dropdown dropdown">
                            <button class="btn btn-light border dropdown-toggle" type="button" id="nakshatramDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                நட்சத்திரம் தேர்வு
                                <span id="selectedNakshatramCount"></span>
                            </button>
                            <div class="dropdown-menu w-100" aria-labelledby="nakshatramDropdown">
                                <div class="search-box">
                                    <input type="text" class="form-control form-control-sm" id="nakshatramSearch" placeholder="Search nakshatram...">
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllNakshatram">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllNakshatram">Clear All</button>
                                    </div>
                                </div>
                                <?php 
                                $selectedNakshatram = isset($_GET['nakshatram']) ? (array)$_GET['nakshatram'] : [];
                                foreach($nakshatramOptions as $option): ?>
                                <label class="dropdown-item">
                                    <input type="checkbox" name="nakshatram[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $selectedNakshatram) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="city" class="form-label">வசிக்கும் ஊர்</label>
                        <input type="text" class="form-control" id="city" name="city" 
                               value="<?php echo htmlspecialchars($city); ?>" 
                               placeholder="உங்கள் ஊரின் பெயரை உள்ளிடவும்">
                    </div>

                    <!-- Row 4: பெயர்/ ID முதல் ID வரை / போன் -->
                    <div class="col-md-4">
                        <label for="name" class="form-label">பெயர்</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($name); ?>" 
                               placeholder="பெயர் மூலம் தேடல்">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ID முதல் வரை</label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="number" class="form-control" id="id_from" name="id_from" 
                                   value="<?php echo htmlspecialchars($id_from); ?>" 
                                   placeholder="முதல்">
                            <span>to</span>
                            <input type="number" class="form-control" id="id_to" name="id_to" 
                                   value="<?php echo htmlspecialchars($id_to); ?>" 
                                   placeholder="வரை">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="phone" class="form-label">போன்</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($phone); ?>" 
                               placeholder="Search mobile...">
                    </div>

                    <!-- Submit Button Row -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">தேடல்</button>
                        <a href="profiles.php" class="btn btn-secondary">அனைத்தும் அழி</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>  <!-- End of admin & manager only filter form -->
        <div class="table-responsive">
            <?php
                // Calculate displayed range for the results
                $resultsOnPage = count($profiles);
                $startResult = $totalRecords > 0 ? ($offset + 1) : 0;
                $endResult = $totalRecords > 0 ? ($offset + $resultsOnPage) : 0;
            ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>Results:</strong>
                    <?php if ($totalRecords > 0): ?>
                        <?php echo htmlspecialchars($startResult); ?> - <?php echo htmlspecialchars($endResult); ?> of <?php echo htmlspecialchars($totalRecords); ?>
                    <?php else: ?>
                        0
                    <?php endif; ?>
                </div>
                <div>
                    <small class="text-muted">Page <?php echo htmlspecialchars($page); ?> of <?php echo htmlspecialchars(max(1, $totalPages)); ?></small>
                </div>
            </div>
            <!-- Bulk delete form -->
            <form method="POST" action="delete.php" id="bulkDeleteForm">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <?php if (getUserRole() !== 'support'): ?>
                                <input type="checkbox" id="selectAllProfiles" title="Select all">
                            <?php endif; ?>
                        </th>
                        <th>
                            ID
                            <a href="?sort_id=desc<?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['sort_id' => ''])) : ''; ?>" title="Newest first" style="text-decoration:none; vertical-align:middle;">
                                <span style="font-size:2.3em; font-weight:bold; color:#007bff;">&#8595;</span>
                            </a>
                            <a href="?sort_id=asc<?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['sort_id' => ''])) : ''; ?>" title="Oldest first" style="text-decoration:none; vertical-align:middle;">
                                <span style="font-size:1.3em; font-weight:bold; color:#dc3545;">&#8593;</span>
                            </a>
                        </th>
                        <th>படம்</th>
                        <th>பெயர்</th>
                        <th>வயது</th>
                        <th>பாலினம்</th>
                        <th>மாவட்டம்</th>
                        <th>சாதி</th>
                        <th>நட்சத்திரம் </th>
                        <th>படிப்பு வகை</th>
                        <th>ஊர்</th>
                        <th>வேலை</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($profiles as $profile): ?>
                    <tr>
                        <td>
                            <?php if (getUserRole() !== 'support'): ?>
                                <input type="checkbox" class="profileCheckbox" name="ids[]" value="<?php echo $profile['id']; ?>">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($profile['id']); ?></td>
                        <td>
                            <?php if (!empty($profile['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($profile['profile_photo']); ?>" alt="Profile Photo" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                    <span>👤</span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($profile['name']); ?></td>
                        <td><?php echo htmlspecialchars($profile['age']); ?></td>
                        <td><?php echo $profile['gender'] === 'Male' ? 'ஆண்' : 'பெண்'; ?></td>
                        <td><?php echo htmlspecialchars($districtsMap[$profile['district']] ?? $profile['district']); ?></td>
                        <td><?php echo htmlspecialchars(($profile['caste'] ?? '') . (!empty($profile['subcaste']) ? ' / ' . $profile['subcaste'] : '')); ?></td>
                        <td><?php echo htmlspecialchars($profile['nakshatram'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($profile['education_type'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($profile['city']); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-info">பார்</a>
                            <?php if (getUserRole() !== 'support'): ?>
                                <a href="edit.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-warning">திருத்து</a>
                                <a href="print.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-secondary">பிரிண்ட்</a>
                                <a href="print2.php?id=<?php echo $profile['id']; ?>" class="btn btn-sm btn-secondary">No Phone PDF</a>
                                <!-- Delete form: uses POST and a JS confirmation to avoid accidental deletes -->
                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this profile? This cannot be undone.');">
                                    <input type="hidden" name="id" value="<?php echo $profile['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">அழி</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
                </form>
                <?php if (getUserRole() !== 'support'): ?>
                    <div class="mb-3">
                        <button id="deleteSelectedBtn" class="btn btn-danger" disabled>Delete selected</button>
                    </div>
                <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Bulk select / delete behavior
        $(function(){
            var selectAll = $('#selectAllProfiles');
            var checkboxes = $('.profileCheckbox');
            var deleteBtn = $('#deleteSelectedBtn');

            selectAll.on('change', function(){
                checkboxes.prop('checked', this.checked);
                deleteBtn.prop('disabled', checkboxes.filter(':checked').length === 0);
            });

            $(document).on('change', '.profileCheckbox', function(){
                var total = checkboxes.length;
                var checked = checkboxes.filter(':checked').length;
                selectAll.prop('checked', total > 0 && checked === total);
                deleteBtn.prop('disabled', checked === 0);
            });

            $('#deleteSelectedBtn').on('click', function(e){
                e.preventDefault();
                if (!confirm('Are you sure you want to delete selected profiles? This cannot be undone.')) return;
                // submit the form
                $('#bulkDeleteForm').submit();
            });
        });
    </script>
    <script>
        // Subcaste mapping removed (no dynamic subcaste population)
        $(document).ready(function() {
            // Select All / Clear All logic for all multi-select dropdowns
            function setupSelectAllClearAll(selectAllId, clearAllId, inputName) {
                // Scope the action to the dropdown menu containing the clicked button when possible.
                $(document).on('click', selectAllId, function(e) {
                    var $menu = $(this).closest('.dropdown-menu');
                    if ($menu.length) {
                        $menu.find('input[name="' + inputName + '"]').prop('checked', true);
                    } else {
                        $('input[name="' + inputName + '"]').prop('checked', true);
                    }
                });
                $(document).on('click', clearAllId, function(e) {
                    var $menu = $(this).closest('.dropdown-menu');
                    if ($menu.length) {
                        $menu.find('input[name="' + inputName + '"]').prop('checked', false);
                    } else {
                        $('input[name="' + inputName + '"]').prop('checked', false);
                    }
                });
            }

            setupSelectAllClearAll('#selectAllDistricts', '#clearAllDistricts', 'districts[]');
            setupSelectAllClearAll('#selectAllCastes', '#clearAllCastes', 'castes[]');
            setupSelectAllClearAll('#selectAllNakshatram', '#clearAllNakshatram', 'nakshatram[]');
            setupSelectAllClearAll('#selectAllReligion', '#clearAllReligion', 'religion[]');
            setupSelectAllClearAll('#selectAllEducation', '#clearAllEducation', 'education[]');
            setupSelectAllClearAll('#selectAllDosham', '#clearAllDosham', 'dosham[]');

            // ...existing code...
            // Remove double-click/double-enter protection and related styles if not needed
        });
    </script>

    <style>
        .submit-message {
            font-size: 0.9rem;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</body>
</html>