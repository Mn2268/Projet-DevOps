<?php
require_once 'core/lang.php';
require_once 'core/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: auth');
    exit();
}


// If role is not set or is not 'user', 'admin' or 'super_admin', log out
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['user', 'admin', 'super_admin'])) {
    header('Location: core/logout');
    exit();
}

// Fetch user data from database
$user_id = $_SESSION['user_id'] ?? null;
$user = null;

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching user data: ' . $e->getMessage());
    }
}

// If user not found, redirect to logout
if (!$user) {
    header('Location: core/logout');
    exit();
}

// Determine which name to display based on language
$display_name = ($current_lang === 'ar' && !empty($user['username_arabic'])) 
    ? $user['username_arabic'] 
    : $user['username'];

// Determine which surname to display based on language
$display_surname = ($current_lang === 'ar' && !empty($user['surname_arabic'])) 
    ? $user['surname_arabic'] 
    : $user['surname'];

// Format date of birth
$formatted_dob = '';
if (!empty($user['date_birth'])) {
    $formatted_dob = format_localized_date($user['date_birth']);
}

include 'includes/header.php';
?>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </div>
            <div>
                <h1 class="profile-name"><?php echo htmlspecialchars($display_name); ?></h1>
            </div>
        </div>

        <div class="profile-info">
            <div class="section-header">
                <h2 class="section-title"><?php echo t('personal_information'); ?></h2>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="contact-label"><?php echo t('surname'); ?></span>
                    <span class="info-value"><?php echo htmlspecialchars($display_surname ?? '-'); ?></span>
                </div>

                <div class="info-item">
                    <span class="contact-label"><?php echo t('sex'); ?></span>
                    <span class="info-value"><?php echo htmlspecialchars(t($user['sex'] ?? '',$user['sex'])); ?></span>
                </div>

                <div class="info-item">
                    <span class="contact-label"><?php echo t('date_of_birth'); ?></span>
                    <span class="info-value"><?php echo htmlspecialchars($formatted_dob ?: '-'); ?></span>
                </div>

                <div class="info-item">
                    <span class="contact-label"><?php echo t('identify_code'); ?></span>
                    <span class="info-value"><?php echo htmlspecialchars($user['user_id'] ?? '-'); ?></span>
                </div>

                <div class="info-item">
                    <span class="contact-label"><?php echo t('place_of_birth'); ?></span>
                    <span class="info-value"><?php echo htmlspecialchars(t($user['place_birth'] ?? '',$user['place_birth'])); ?></span>
                </div>

                <div class="info-item">
                    <span class="contact-label"><?php echo t('nationality'); ?></span>
                    <span class="info-value"><?php echo htmlspecialchars(t($user['nationality'] ?? '',$user['nationality'])); ?></span>
                </div>
            </div>
        </div>
    </div>
<script src="assets/js/utilities/utils.js" defer></script>
<script src="assets/js/pages/profile.js" defer></script>

<?php include 'includes/footer.php'; ?>
