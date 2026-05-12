<?php
require_once __DIR__ . '/../config/database.php';

class ParticipationProof
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(int $participationId, string $filePath): bool
    {
        $query = $this->db->prepare("INSERT INTO participation_proofs (participation_id, file_path, review_state) VALUES (:participation_id, :file_path, 'pending')");
        return $query->execute(['participation_id' => $participationId, 'file_path' => $filePath]);
    }

    public function getByParticipationId(int $participationId): array
    {
        $query = $this->db->prepare("SELECT pr.*, air.ai_decision, air.ai_confidence, air.ai_progress_increment, air.ai_reason, air.ai_reviewed_at
                                     FROM participation_proofs pr
                                     LEFT JOIN proof_ai_reviews air ON air.proof_id = pr.id
                                     WHERE pr.participation_id = :participation_id
                                     ORDER BY pr.created_at DESC, pr.id DESC");
        $query->execute(['participation_id' => $participationId]);
        return $query->fetchAll();
    }

    public function getByIdForParticipation(int $proofId, int $participationId): ?array
    {
        $query = $this->db->prepare("SELECT pr.*, air.ai_decision, air.ai_confidence, air.ai_progress_increment, air.ai_reason, air.ai_reviewed_at
                                     FROM participation_proofs pr
                                     LEFT JOIN proof_ai_reviews air ON air.proof_id = pr.id
                                     WHERE pr.id = :id AND pr.participation_id = :participation_id");
        $query->execute(['id' => $proofId, 'participation_id' => $participationId]);
        $row = $query->fetch();
        return $row ?: null;
    }

    public function saveAiReview(int $proofId, array $review): bool
    {
        $decision = $review['decision'] ?? 'uncertain';
        if (!in_array($decision, ['approved', 'rejected', 'uncertain', 'error'], true)) {
            $decision = 'uncertain';
        }

        $query = $this->db->prepare("INSERT INTO proof_ai_reviews
                (proof_id, ai_decision, ai_confidence, ai_progress_increment, ai_reason, ai_raw_response)
            VALUES (:proof_id, :decision, :confidence, :progress_increment, :reason, :raw_response)
            ON DUPLICATE KEY UPDATE
                ai_decision = VALUES(ai_decision),
                ai_confidence = VALUES(ai_confidence),
                ai_progress_increment = VALUES(ai_progress_increment),
                ai_reason = VALUES(ai_reason),
                ai_raw_response = VALUES(ai_raw_response),
                ai_reviewed_at = CURRENT_TIMESTAMP");

        return $query->execute([
            'proof_id' => $proofId,
            'decision' => $decision,
            'confidence' => max(0, min(100, (int)($review['confidence'] ?? 0))),
            'progress_increment' => max(0, min(100, (int)($review['progress_increment'] ?? 0))),
            'reason' => mb_substr(trim((string)($review['reason'] ?? '')), 0, 1000),
            'raw_response' => mb_substr(json_encode($review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 5000),
        ]);
    }

    public function updateReviewStateForParticipation(int $proofId, int $participationId, string $reviewState): bool
    {
        $query = $this->db->prepare('UPDATE participation_proofs SET review_state = :state WHERE id = :id AND participation_id = :participation_id');
        return $query->execute([
            'state' => $reviewState,
            'id' => $proofId,
            'participation_id' => $participationId,
        ]);
    }
}
