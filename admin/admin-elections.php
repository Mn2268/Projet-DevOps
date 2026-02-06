<?php
require_once '../core/lang.php';
require_once '../core/config.php';
require_once '../core/auth-helpers.php';

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

// Check if user is super_admin (redirect to super_admin pages)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    header('Location: ../super_admin/');
    exit();
}

// If role is not 'admin', log out
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../core/logout');
    exit();
}

// Get elections based on role
$elections = getAdminElections($pdo);

// Get all template positions for the dropdown (not linked to any election)
$positionStmt = $pdo->prepare("SELECT * FROM position WHERE id_election IS NULL OR id_election = 0 ORDER BY en_name");
$positionStmt->execute();
$positions = $positionStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin-header.php';
?>
<div class="admin-elections-container">
    <div class="header-elections">
        <h1><?php echo t('manage_elections', 'Gérer les élections'); ?></h1>
    </div>
    
    <?php if (empty($elections)): ?>
        <div class="no-elections-message">
            <p><?php echo t('no_elections_assigned', 'Aucune élection ne vous est assignée.'); ?></p>
        </div>
    <?php else: ?>
        <div class="elections-grid">
            <?php foreach($elections as $election): ?>
                <div class="election-card">
                    <div class="election-card-header">
                        <div class="election-year-badge"><?= $election['year']; ?></div>
                        <?php if( $election['status'] === 1 ): ?>
                            <span class="status-badge status-active"><?php echo t('active', 'Actif'); ?></span>
                        <?php else: ?>
                            <span class="status-badge status-inactive"><?php echo t('inactive', 'Inactif'); ?></span>
                        <?php endif ?>
                    </div>
                    
                    <div class="election-card-body">
                        <h3 class="election-organizer"><?= $election[$current_lang.'_organizer']; ?></h3>
                        <?php if (!empty($election['position_'.$current_lang.'_name'])): ?>
                            <p style="color: #888; font-size: 0.9rem; margin: 0.5rem 0;">
                                <strong><?php echo t('position', 'Position'); ?>:</strong> <?= $election['position_'.$current_lang.'_name']; ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="election-dates">
                            <div class="date-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span><?php echo t('start', 'Début'); ?>: <strong><?= $election['start_date']; ?></strong></span>
                            </div>
                            <div class="date-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span><?php echo t('end', 'Fin'); ?>: <strong><?= $election['end_date']; ?></strong></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="election-card-actions">
                        <div class="action-buttons-grid">
                            <button class="btn-action" onclick="editElection(<?= $election['id']; ?>, <?= '\''.$current_lang.'\'' ?>)" style="grid-column: span 2;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22H15C20 22 22 20 22 15V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M16.04 3.02001L8.16 10.9C7.86 11.2 7.56 11.79 7.5 12.22L7.07 15.23C6.91 16.32 7.68 17.08 8.77 16.93L11.78 16.5C12.2 16.44 12.79 16.14 13.1 15.84L20.98 7.96001C22.34 6.60001 22.98 5.02001 20.98 3.02001C18.98 1.02001 17.4 1.66001 16.04 3.02001Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14.91 4.1499C15.58 6.5399 17.45 8.4099 19.85 9.0899" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo t('edit_election','Modifier élection'); ?>
                            </button>
                            
                            <?php if( $election['status'] === 1 ): ?>
                                <button class="btn-action" onclick="addNewCandidate(<?= $election['id']; ?>, <?= '\''.$current_lang.'\'' ?>)">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.41003 22C3.41003 18.13 7.26003 15 12 15C12.96 15 13.89 15.13 14.76 15.37" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22 18C22 18.32 21.96 18.63 21.88 18.93C21.79 19.33 21.63 19.72 21.42 20.06C20.73 21.22 19.46 22 18 22C16.97 22 16.04 21.61 15.34 20.97C15.04 20.71 14.78 20.40 14.58 20.06C14.21 19.46 14 18.75 14 18C14 16.92 14.43 15.93 15.13 15.21C15.86 14.46 16.88 14 18 14C19.18 14 20.25 14.51 20.97 15.33C21.61 16.04 22 16.98 22 18Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M19.49 17.98H16.51" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M18 16.52V19.51" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php echo t('add_candidate','Candidat'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn-action disabled" disabled>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.41003 22C3.41003 18.13 7.26003 15 12 15C12.96 15 13.89 15.13 14.76 15.37" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php echo t('add_candidate','Candidat'); ?>
                                </button>
                            <?php endif ?>
                            
                            <button class="btn-action" onclick="getListOfCandidates(<?= $election['id']; ?>)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2.00098 21C2.00098 17.13 5.13098 14 9.00098 14C9.96098 14 10.8 14.15 11.6 14.43" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M15.5 19.5H20.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18 22V17" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php echo t('candidates','Candidats'); ?>
                            </button>
                            
                            <?php if( $election['results'] === 'nopublish' ): ?>
                                <button class="btn-action" onclick="publishResults(<?= $election['id']; ?>)">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M15.5 9.75C16.19 9.75 16.75 9.19 16.75 8.5C16.75 7.81 16.19 7.25 15.5 7.25C14.81 7.25 14.25 7.81 14.25 8.5C14.25 9.19 14.81 9.75 15.5 9.75Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M2 15.5L7.42 12.52C8.04 12.16 8.84 12.2 9.41 12.63L9.86 12.99C10.5 13.47 11.41 13.47 12.02 12.99L16.48 9.5C17.09 9.02 18 9.02 18.61 9.5L22 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php echo t('publish','Publier'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn-action" onclick="stopPublishingResults(<?= $election['id']; ?>)">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.77 2.23001C21.47 1.93001 20.98 1.93001 20.68 2.23001L2.23001 20.69C1.93001 20.99 1.93001 21.48 2.23001 21.78C2.38001 21.92 2.57001 22 2.77001 22C2.97001 22 3.16001 21.92 3.31001 21.77L21.77 3.31001C22.08 3.01001 22.08 2.53001 21.77 2.23001Z" fill="currentColor"/>
                                        <path d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php echo t('unpublish','Dépublier'); ?>
                                </button>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Modal for Candidate List -->
    <div class="modal" id="candidateListModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo t('candidates_list', 'Liste des candidats'); ?></h2>
                <button class="modal-close" id="closeCandidateListModal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="candidateListContent">
                <div class="loading-spinner">
                    <svg class="spinner-svg" viewBox="25 25 50 50">
                        <circle r="20" cy="50" cx="50"></circle>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal for Add Candidate -->
    <div class="modal" id="addCandidateModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo t('add_candidate', 'Ajouter un candidat'); ?></h2>
                <button class="modal-close" id="closeAddCandidateModal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            
            <form class="modal-body" id="addCandidateForm" enctype="multipart/form-data">
                <input type="hidden" id="add_election_id" name="id_election">
                <input type="hidden" id="add_id_position" name="id_position">
                
                <!-- Basic Information -->
                <div class="form-section">
                    <h3><?php echo t('basic_information', 'Informations de base'); ?></h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <h6><?php echo t('candidate_name_en', 'Nom (English)'); ?> <span class="required">*</span></h6>
                            <input type="text" id="add_name" name="name" required placeholder="John Doe">
                        </div>
                        
                        <div class="form-group">
                            <h6><?php echo t('candidate_name_ar', 'الاسم (العربية)'); ?> <span class="required">*</span></h6>
                            <input type="text" id="add_ar_name" name="ar_name" required placeholder="جون دو" dir="rtl">
                        </div>
                    </div>
                </div>

                <!-- Descriptions -->
                <div class="form-section">
                    <h3><?php echo t('descriptions', 'Descriptions'); ?></h3>
                    
                    <div class="form-group">
                        <h6><?php echo t('description_en', 'Description (English)'); ?> <span class="required">*</span></h6>
                        <textarea id="add_en_description" name="en_description" required rows="4" 
                                placeholder="Enter candidate description in English"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <h6><?php echo t('description_fr', 'Description (Français)'); ?> <span class="required">*</span></h6>
                        <textarea id="add_fr_description" name="fr_description" required rows="4" 
                                placeholder="Entrez la description du candidat en français"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <h6><?php echo t('description_ar', 'الوصف (العربية)'); ?> <span class="required">*</span></h6>
                        <textarea id="add_ar_description" name="ar_description" required rows="4" 
                                placeholder="أدخل وصف المرشح بالعربية" dir="rtl"></textarea>
                    </div>
                </div>

                <!-- Party Information -->
                <div class="form-section">
                    <h3><?php echo t('party_information', 'Informations sur le parti'); ?></h3>
                    
                    <div class="form-group">
                        <h6><?php echo t('supporting_party', 'Parti de soutien'); ?> <span class="required">*</span></h6>
                        <input type="text" id="add_supporting_party" name="supporting_party" required placeholder="UNEM">
                    </div>

                    <div class="form-group">
                        <h6><?php echo t('party_logo', 'Logo du parti'); ?></h6>
                        <div class="file-upload-container">
                            <input type="file" id="add_party_logo" name="party_logo" accept="image/*" class="file-input">
                            <label for="add_party_logo" class="file-label">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15M17 8L12 3M12 3L7 8M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span id="addPartyLogoLabel"><?php echo t('upload_image', 'Télécharger une image'); ?></span>
                            </label>
                            <div class="file-preview" id="addPartyLogoPreview"></div>
                        </div>
                    </div>
                </div>

                <!-- Candidate Photo -->
                <div class="form-section">
                    <h3><?php echo t('candidate_photo', 'Photo du candidat'); ?></h3>
                    
                    <div class="form-group">
                        <h6><?php echo t('photo', 'Photo'); ?></h6>
                        <div class="file-upload-container">
                            <input type="file" id="add_photo" name="photo" accept="image/*" class="file-input">
                            <label for="add_photo" class="file-label">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15M17 8L12 3M12 3L7 8M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span id="addPhotoLabel"><?php echo t('upload_image', 'Télécharger une image'); ?></span>
                            </label>
                            <div class="file-preview" id="addPhotoPreview"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelAddCandidateBtn">
                        <?php echo t('cancel', 'Annuler'); ?>
                    </button>
                    <button type="submit" class="btn-primary" id="saveAddCandidateBtn">
                        <span class="btn-text"><?php echo t('save', 'Enregistrer'); ?></span>
                        <svg class="spinner-svg" viewBox="25 25 50 50">
                            <circle r="20" cy="50" cx="50"></circle>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal for Edit Election -->
    <div class="modal" id="editElectionModal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo t('edit_election', 'Modifier élection'); ?></h2>
                <button class="modal-close" id="closeEditElectionModal">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <form class="modal-body" id="editElectionForm">
                <input type="hidden" id="edit_election_id" name="election_id">
                
                <!-- Election Type and Position Section -->
                <div class="form-section">
                    <h3><?php echo t('election_configuration', 'Configuration élection'); ?></h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <h6><?php echo t('election_type', 'Type élection'); ?> <span class="required">*</span></h6>
                            <select id="edit_election_type" name="election_type" required class="custom-select">
                                <option value=""><?php echo t('select_type', 'Sélectionner un type'); ?></option>
                                <option value="governmental"><?php echo t('governmental', 'Gouvernementale'); ?></option>
                                <option value="university"><?php echo t('university', 'Universitaire'); ?></option>
                                <option value="municipal"><?php echo t('municipal', 'Municipale'); ?></option>
                                <option value="corporate"><?php echo t('corporate', 'Entreprise'); ?></option>
                                <option value="association"><?php echo t('association', 'Association'); ?></option>
                                <option value="other"><?php echo t('other', 'Autre'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <h6><?php echo t('select_position', 'Sélectionner la position'); ?> <span class="required">*</span></h6>
                            <select id="edit_election_position" name="election_position" required class="custom-select">
                                <option value=""><?php echo t('select_position', 'Sélectionner une position'); ?></option>
                                <?php foreach($positions as $pos): ?>
                                    <option value="<?= htmlspecialchars($pos['en_name']); ?>" 
                                            data-ar="<?= htmlspecialchars($pos['ar_name']); ?>" 
                                            data-fr="<?= htmlspecialchars($pos['fr_name']); ?>">
                                        <?= htmlspecialchars($pos['en_name']); ?> / <?= htmlspecialchars($pos['fr_name']); ?> / <?= htmlspecialchars($pos['ar_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="edit_position_en_name" name="position_en_name">
                            <input type="hidden" id="edit_position_fr_name" name="position_fr_name">
                            <input type="hidden" id="edit_position_ar_name" name="position_ar_name">
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelEditElectionBtn">
                        <?php echo t('cancel', 'Annuler'); ?>
                    </button>
                    <button type="submit" class="btn-primary" id="saveEditElectionBtn">
                        <span class="btn-text"><?php echo t('save', 'Enregistrer'); ?></span>
                        <svg class="spinner-svg" viewBox="25 25 50 50">
                            <circle r="20" cy="50" cx="50"></circle>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/utilities/utils.js" defer></script>
<script src="../assets/js/pages/admin-elections.js" defer></script>

<?php include '../includes/admin-footer.php'; ?>
