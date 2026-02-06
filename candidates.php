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

include 'includes/header.php';
?>
<?php
    // Fetch active elections that have candidates
    $stmtEle = $pdo->prepare("
        SELECT DISTINCT e.* FROM election e
        INNER JOIN position p ON e.id = p.id_election
        INNER JOIN candidates c ON p.id = c.id_position
        WHERE e.status = 1
        GROUP BY e.id
        HAVING COUNT(DISTINCT c.id) > 0
        ORDER BY e.id
    ");
    $stmtEle->execute();
    $elections = $stmtEle->fetchAll(); 

?>
<div class="candidates-container">
    <?php if (count($elections) > 0): ?>
        <?php foreach($elections as $election): ?>
            <div class="header">
                <?php 
                    $electionType = $election['election_type'] ?? 'other';
                    $electionTypeLabel = t($electionType, ucfirst($electionType));
                    $electionLabel = t('election', 'Election');
                ?>
                <span class="title"><?= $electionTypeLabel . ' ' . $electionLabel; ?></span>
                <h2 class="year"><?= $election['year']; ?></h2>
                <h3 class="organizer">Organized by :  <span class="green"><?= $election[$current_lang.'_organizer']; ?></span></h3>
                <h1><?php echo t('list_of_candidates', 'List of candidates:'); ?><span>:</span></h1>
            </div>

            <?php
            // Fetch positions and candidates
            $stmtPos = $pdo->prepare("SELECT * FROM position WHERE status = 1 AND id_election = ? ORDER BY id");
            $stmtPos->execute([$election['id']]);
            $positions = $stmtPos->fetchAll();

            $stmtCand = $pdo->prepare("SELECT * FROM candidates WHERE id_position IS NOT NULL ORDER BY id_position, id");
            $stmtCand->execute();
            $candidates = $stmtCand->fetchAll();

            // Group candidates by position id
            $byPos = [];
            foreach ($candidates as $c) {
                $pid = $c['id_position'];
                if (!isset($byPos[$pid])) $byPos[$pid] = [];
                $byPos[$pid][] = $c;
            }

            foreach ($positions as $pos) {
                $pid = $pos['id'];
                if (empty($byPos[$pid])) continue; // skip positions with no candidates
                $posName = $pos[$current_lang.'_name'];
                if (current_lang() === 'ar') { $posName = t($posName, $posName); }
                ?>
                <div class="candidates-list">
                    <div class="position-header"><?php echo htmlspecialchars($posName); ?><span>:</span></div>

                    <?php foreach ($byPos[$pid] as $cand) {
                        $photo = $cand['photo_path'] ?: 'assets/images/candidates/profile/candidate-placeholder.png';
                        $logo = $cand['path_supporting_party_logo'] ?: 'assets/images/candidates/party/party-placeholder.jpg';
                        $party = $cand['Supporting_party'];
                        $lang = current_lang();
                        if ($lang === 'ar') {
                            $name = $cand['ar_name'] ?: $cand['name'];
                            $bio = $cand['ar_description'] ?: $cand['en_description'];
                        } elseif ($lang === 'fr') {
                            $name = $cand['name'];
                            $bio = $cand['fr_description'] ?: $cand['en_description'];
                        } else {
                            $name = $cand['name'];
                            $bio = $cand['en_description'];
                        }
                    ?>
                    <div class="candidate-card" onclick="toggleCandidate(this)">
                        <div class="candidate-header">
                            <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($name); ?>" class="candidate-photo">
                            <div class="candidate-info">
                                <div class="candidate-name"><?php echo htmlspecialchars($name); ?></div>
                            </div>
                            <div class="candidate-meta">
                                <div class="organization"><?php echo htmlspecialchars($party); ?></div>
                                <img src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($party); ?>" class="party-logo" style="width:28px;height:28px;object-fit:contain;border-radius:4px;border:1px solid #3a3a3a;" />
                            </div>
                        </div>
                        <div class="candidate-details">
                            <div class="candidate-details-inner">
                                <div class="candidate-bio"><?php echo htmlspecialchars($bio); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php
            }
            ?>
        <?php endforeach; ?>
    <?php else: ?>
        <span class="any-election"><?php echo t('any_position', 'Sécurité et transparence dans votre vote...') ?></span>
    <?php endif; ?>
</div>
<script src="assets/js/utilities/utils.js" defer></script>
<script src="assets/js/pages/candidates.js" defer></script>

<?php include 'includes/footer.php'; ?>
