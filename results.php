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

$stmt = $pdo->prepare(
    'SELECT e.* FROM election e 
     INNER JOIN position p ON e.id = p.id_election 
     INNER JOIN candidates c ON p.id = c.id_position 
     WHERE e.`results` = \'publish\' AND e.`status` = 1 
     GROUP BY e.id 
     HAVING COUNT(DISTINCT c.id) > 0 
     ORDER BY e.id'
);
$stmt->execute();
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

function get_Positions_by_election($pdo, $id_election) {
  $stmt = $pdo->prepare(
    'SELECT * FROM `position` WHERE id_election = ?'
  );
  $stmt->execute([$id_election]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_candidates_by_position($pdo, $id_position) {
  $stmt = $pdo->prepare(
    'SELECT * FROM `candidates` WHERE id_Position = ?'
  );
  $stmt->execute([$id_position]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


include 'includes/header.php';
?>
<div class="results-container">
    <?php if (count($elections) > 0): ?>
        <?php foreach($elections as $election): ?>
            <?php 
                $electionType = $election['election_type'] ?? 'Student';
                $electionTypeLabel = t($electionType, ucfirst($electionType));
                $electionLabel = t('election', 'Election');
            ?>
            <h1><?= $electionTypeLabel . ' ' . $electionLabel; ?></h1>
            <h2 class="year"><?= $election['year']; ?></h2>
            <h3 class="organizer">Organized by :  <span class="green"><?= $election[$current_lang.'_organizer']; ?></span></h3>
            <?php $positions = get_Positions_by_election($pdo, $election['id']); ?>
            <div class="intro-and-results">
                <?php foreach($positions as $position): ?>
                    <section class="election-results">
                        <?php $candidates = get_candidates_by_position($pdo, $position['id']);?>
                        <?php if(count($candidates) > 0): ?>
                            <h3>Election the <span class="green"><?= $position[$current_lang.'_name'] ?> </span>:</h3>
                            <ul class="candidate-list">
                                <?php foreach($candidates as $candidate): ?>
                                    <li class="candidate-item">
                                        <div class="candidate-info">
                                        <img src="<?= $candidate['photo_path'] ?>" class="photo-candidate">
                                        <span class="name"><?= ($current_lang === 'ar')? $candidate['ar_name'] : $candidate['name'] ?></span>
                                        </div>
                                        <span class="union snem"><?= $candidate['Supporting_party'] ?></span>
                                        <img src="<?= $candidate['path_supporting_party_logo'] ?>" class="union-logo">
                                        <span class="percentage" data-id="<?= $candidate['id'] ?>"></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endforeach ?>
    <?php else: ?>
        <span class="any-election"><?php echo t('any_position', 'Sécurité et transparence dans votre vote...') ?></span>
    <?php endif; ?>
</div>
<script src="assets/js/utilities/utils.js" defer></script>
<script src="assets/js/pages/results.js" defer></script>

<?php include 'includes/footer.php'; ?>
