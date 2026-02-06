<?php
require_once 'config.php';
require_once 'lang.php';
require_once 'auth-helpers.php';

// Check if user is logged in and has admin privileges
if (!hasAdminPrivileges()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            getAllCandidates();
            break;
        case 'get_one':
            getCandidate();
            break;
        case 'create':
            createCandidate();
            break;
        case 'update':
            updateCandidate();
            break;
        case 'delete':
            deleteCandidate();
            break;
        case 'get_positions':
            getPositions();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getAllCandidates() {
    global $pdo;
    
    $lang = $_SESSION['current_lang'];

    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            p.id AS position_id,
            p.fr_name AS position_fr_name,
            p.en_name AS position_en_name,
            p.ar_name AS position_ar_name,
            p.id_election AS position_election_id
        FROM candidates c 
        LEFT JOIN position p ON c.id_position = p.id 
        ORDER BY c.id DESC
    ");
    $stmt->execute();
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($candidates as &$c) {
        // Derive a display name for position without altering candidate fields
        $map = [
            'ar' => 'position_ar_name',
            'en' => 'position_en_name',
            'fr' => 'position_fr_name',
        ];
        $key = isset($map[$lang]) ? $map[$lang] : 'position_en_name';
        $c['position_name'] = isset($c[$key]) && $c[$key] ? $c[$key] : null;
    }
    unset($c); 


    echo json_encode(['success' => true, 'candidates' => $candidates]);
}

function getCandidate() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    $candidate = $stmt->fetch();
    
    if ($candidate) {
        echo json_encode(['success' => true, 'candidate' => $candidate]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
    }
}

function getPositions() {
    global $pdo;
    
    $lang = $_SESSION['current_lang'];    

    $stmt = $pdo->prepare("SELECT * FROM position WHERE status = 1 ORDER BY id");
    $stmt->execute();
    $positions = $stmt->fetchAll();

    // Localize position names when Arabic is active
    foreach ($positions as &$p) {
        if (!empty($p['name'])) {
            $p['name'] = $p[$lang+'_name'];
        }
    }
    unset($p);
    
    echo json_encode(['success' => true, 'positions' => $positions]);
}

function createCandidate() {
    global $pdo;
    
    // Get form data
    $name = $_POST['name'] ?? '';
    $ar_name = $_POST['ar_name'] ?? '';
    $en_description = $_POST['en_description'] ?? '';
    $fr_description = $_POST['fr_description'] ?? '';
    $ar_description = $_POST['ar_description'] ?? '';
    $supporting_party = $_POST['supporting_party'] ?? '';
    $id_position = !empty($_POST['id_position']) ? $_POST['id_position'] : null;
    
    // Validate required fields
    if (empty($name) || empty($ar_name) || empty($en_description) || 
        empty($fr_description) || empty($ar_description) || empty($supporting_party)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate position and check authorization
    if ($id_position) {
        $electionId = getElectionIdFromPosition($pdo, $id_position);
        if (!$electionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid position ID']);
            return;
        }
        
        if (!canEditElection($pdo, $electionId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to manage candidates for this election']);
            return;
        }
    }
    
    // Handle photo upload
    $photo_path = 'assets/images/candidates/profile/candidate-placeholder.png';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploaded = handleFileUpload($_FILES['photo'], 'profile');
        if ($uploaded) {
            $photo_path = $uploaded;
        }
    }
    
    // Handle party logo upload
    $party_logo_path = 'assets/images/candidates/party/party-placeholder.jpg';
    if (isset($_FILES['party_logo']) && $_FILES['party_logo']['error'] === UPLOAD_ERR_OK) {
        $uploaded = handleFileUpload($_FILES['party_logo'], 'party');
        if ($uploaded) {
            $party_logo_path = $uploaded;
        }
    }
    
    // Get created_by from current user
    $created_by = getCurrentUserDbId($pdo);
    
    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO candidates 
        (name, ar_name, photo_path, en_description, fr_description, ar_description, 
         Supporting_party, path_supporting_party_logo, id_position, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $name, $ar_name, $photo_path, $en_description, $fr_description, 
        $ar_description, $supporting_party, $party_logo_path, $id_position, $created_by
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Candidate created successfully',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create candidate']);
    }
}

function updateCandidate() {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    // Get existing candidate
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
        return;
    }
    
    // Check authorization - validate position
    if ($existing['id_position']) {
        $electionId = getElectionIdFromPosition($pdo, $existing['id_position']);
        if ($electionId && !canEditElection($pdo, $electionId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to manage this candidate']);
            return;
        }
    }
    
    // Get form data
    $name = $_POST['name'] ?? $existing['name'];
    $ar_name = $_POST['ar_name'] ?? $existing['ar_name'];
    $en_description = $_POST['en_description'] ?? $existing['en_description'];
    $fr_description = $_POST['fr_description'] ?? $existing['fr_description'];
    $ar_description = $_POST['ar_description'] ?? $existing['ar_description'];
    $supporting_party = $_POST['supporting_party'] ?? $existing['Supporting_party'];
    $id_position = !empty($_POST['id_position']) ? $_POST['id_position'] : $existing['id_position'];
    
    // Validate new position if changed and check authorization
    if ($id_position && $id_position != $existing['id_position']) {
        $newElectionId = getElectionIdFromPosition($pdo, $id_position);
        if (!$newElectionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid position ID']);
            return;
        }
        
        if (!canEditElection($pdo, $newElectionId)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to assign candidates to this election']);
            return;
        }
    }
    
    $photo_path = $existing['photo_path'];
    $party_logo_path = $existing['path_supporting_party_logo'];
    
    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $new_photo = handleFileUpload($_FILES['photo'], 'profile');
        if ($new_photo) {
            // Delete old photo if exists and not placeholder
            if ($photo_path && file_exists('../' . $photo_path) && 
                strpos($photo_path, 'candidate-placeholder.png') === false) {
                unlink('../' . $photo_path);
            }
            $photo_path = $new_photo;
        }
    }
    
    // Handle party logo upload
    if (isset($_FILES['party_logo']) && $_FILES['party_logo']['error'] === UPLOAD_ERR_OK) {
        $new_logo = handleFileUpload($_FILES['party_logo'], 'party');
        if ($new_logo) {
            // Delete old logo if exists and not placeholder
            if ($party_logo_path && file_exists('../' . $party_logo_path) && 
                strpos($party_logo_path, 'candidate-placeholder.png') === false &&
                strpos($party_logo_path, 'party-placeholder.jpg') === false) {
                unlink('../' . $party_logo_path);
            }
            $party_logo_path = $new_logo;
        }
    }
    
    // Update database
    $stmt = $pdo->prepare("
        UPDATE candidates 
        SET name = ?, ar_name = ?, photo_path = ?, en_description = ?, 
            fr_description = ?, ar_description = ?, Supporting_party = ?, 
            path_supporting_party_logo = ?, id_position = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $name, $ar_name, $photo_path, $en_description, $fr_description, 
        $ar_description, $supporting_party, $party_logo_path, $id_position, $id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Candidate updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update candidate']);
    }
}

function deleteCandidate() {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    // Get candidate to check authorization and delete files
    $stmt = $pdo->prepare("SELECT c.*, p.id_election FROM candidates c LEFT JOIN position p ON c.id_position = p.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $candidate = $stmt->fetch();
    
    if (!$candidate) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Candidate not found']);
        return;
    }
    
    // Check authorization
    if ($candidate['id_election'] && !canEditElection($pdo, $candidate['id_election'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this candidate']);
        return;
    }
    
    // Delete candidate
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        // Delete files (but not placeholders)
        if ($candidate['photo_path'] && file_exists('../' . $candidate['photo_path']) && 
            strpos($candidate['photo_path'], 'candidate-placeholder.png') === false) {
            unlink('../' . $candidate['photo_path']);
        }
        if ($candidate['path_supporting_party_logo'] && file_exists('../' . $candidate['path_supporting_party_logo']) && 
            strpos($candidate['path_supporting_party_logo'], 'candidate-placeholder.png') === false &&
            strpos($candidate['path_supporting_party_logo'], 'party-placeholder.jpg') === false) {
            unlink('../' . $candidate['path_supporting_party_logo']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete candidate']);
    }
}

function handleFileUpload($file, $type) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = $type === 'profile' 
        ? '../assets/images/candidates/profile/' 
        : '../assets/images/candidates/party/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return relative path
        return 'assets/images/candidates/' . $type . '/' . $filename;
    }
    
    return false;
}