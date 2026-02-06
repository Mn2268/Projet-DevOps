<?php
require_once '../core/lang.php';
require_once '../core/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../auth');
    exit();
}

// Check if user has user role (redirect to user page)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    header('Location: ../');
    exit();
}

// Check if user has admin role (redirect to user page)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: ../admin/dashboard');
    exit();
}

// If role is not set or is not 'user', 'admin' or 'super_admin', log out
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['user', 'admin', 'super_admin'])) {
    header('Location: ../core/logout');
    exit();
}

// Fetch only template positions (not linked to any election)
$stmt = $pdo->prepare('SELECT * FROM position WHERE id_election IS NULL OR id_election = 0 ORDER BY id DESC');
$stmt->execute();
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/super-admin-header.php';
?>
<div class="admin-settings-container">
    <div class="page-header">
        <h1><?php echo t('settings', 'Paramètres'); ?></h1>
    </div>

    <!-- Positions Management Section -->
    <div class="settings-section">
        <div class="section-header">
            <h2><?php echo t('manage_positions', 'Gérer les postes'); ?></h2>
            <button class="btn-primary" id="addPositionBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <?php echo t('add_position', 'Ajouter un poste'); ?>
            </button>
        </div>

        <div class="positions-grid" id="positionsGrid">
            <?php if(empty($positions)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3><?php echo t('no_positions', 'Aucun poste'); ?></h3>
                    <p><?php echo t('add_first_position', 'Commencez par ajouter votre premier poste'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach($positions as $position): ?>
                    <div class="position-card">
                        <div class="position-info">
                            <h3><?php echo htmlspecialchars($position[$current_lang.'_name']); ?></h3>
                            <div class="position-details">
                                <span class="detail-label"><?php echo t('template', 'Modèle'); ?></span>
                                <span class="detail-value"><?php echo t('available_for_elections', 'Disponible pour les élections'); ?></span>
                            </div>
                        </div>
                        <div class="position-actions">
                            <button class="icon-btn edit-btn" data-id="<?php echo $position['id']; ?>">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo t('edit', 'Modifier'); ?>
                            </button>
                            <button class="icon-btn delete-btn" data-id="<?php echo $position['id']; ?>">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 6H5H21M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo t('delete', 'Supprimer'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Add/Edit Position -->
    <div class="modal" id="positionModal">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2 id="positionModalTitle"><?php echo t('add_position', 'Ajouter un poste'); ?></h2>
                <button class="modal-close" id="closePositionModal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <form class="modal-body" id="positionForm">
                <input type="hidden" id="position_id" name="id">
                
                <div class="form-section">
                    <h3><?php echo t('position_information', 'Informations sur le poste'); ?></h3>
                    
                    <div class="form-group">
                        <h6><?php echo t('position_name_ar', 'اسم المنصب (العربية)'); ?> <span class="required">*</span></h6>
                        <input type="text" id="position_ar_name" name="ar_name" required dir="rtl">
                    </div>

                    <div class="form-group">
                        <h6><?php echo t('position_name_en', 'Position Name (English)'); ?> <span class="required">*</span></h6>
                        <input type="text" id="position_en_name" name="en_name" required>
                    </div>

                    <div class="form-group">
                        <h6><?php echo t('position_name_fr', 'Nom du poste (Français)'); ?> <span class="required">*</span></h6>
                        <input type="text" id="position_fr_name" name="fr_name" required>
                    </div>

                    <input type="hidden" id="position_election_id" name="id_election" value="0">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelPositionBtn">
                        <?php echo t('cancel', 'Annuler'); ?>
                    </button>
                    <button type="submit" class="btn-primary" id="savePositionBtn">
                        <span class="btn-text"><?php echo t('save', 'Enregistrer'); ?></span>
                        <svg class="spinner-svg" viewBox="25 25 50 50">
                            <circle r="20" cy="50" cx="50"></circle>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2><?php echo t('confirm_delete', 'Confirmer la suppression'); ?></h2>
                <button class="modal-close" id="closeDeleteModal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="color: #ccc; text-align: center; margin-bottom: 2rem;">
                    <?php echo t('delete_position_confirm', 'Êtes-vous sûr de vouloir supprimer ce poste ?'); ?>
                </p>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelDeleteBtn">
                        <?php echo t('cancel', 'Annuler'); ?>
                    </button>
                    <button type="button" class="btn-danger" id="confirmDeleteBtn">
                        <?php echo t('delete', 'Supprimer'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/utilities/utils.js" defer></script>
<script src="../assets/js/pages/super-admin-settings.js" defer></script>

<?php include '../includes/admin-footer.php'; ?>
