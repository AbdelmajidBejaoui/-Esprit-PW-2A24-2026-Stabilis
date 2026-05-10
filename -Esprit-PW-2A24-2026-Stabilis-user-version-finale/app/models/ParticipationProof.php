<?php

class ParticipationProof
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function create(int $participationId, string $filePath): bool
    {
        $sql = "INSERT INTO participation_proofs (participation_id, file_path, review_state)
                VALUES (?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $participationId, $filePath);
        return $stmt->execute();
    }

    public function getByParticipationId(int $participationId): array
    {
        $sql = "SELECT pr.*,
                       air.ai_decision,
                       air.ai_confidence,
                       air.ai_progress_increment,
                       air.ai_reason,
                       air.ai_reviewed_at
                FROM participation_proofs pr
                LEFT JOIN proof_ai_reviews air ON air.proof_id = pr.id
                WHERE pr.participation_id = ?
                ORDER BY pr.created_at DESC, pr.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $participationId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function saveAiReview(int $proofId, array $review): bool
    {
        $decision = $review['decision'] ?? 'uncertain';
        if (!in_array($decision, ['approved', 'rejected', 'uncertain', 'error'], true)) {
            $decision = 'uncertain';
        }

        $confidence = max(0, min(100, (int)($review['confidence'] ?? 0)));
        $progressIncrement = max(0, min(100, (int)($review['progress_increment'] ?? 0)));
        $reason = mb_substr(trim((string)($review['reason'] ?? '')), 0, 1000);
        $rawResponse = mb_substr(json_encode($review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 5000);

        $sql = "INSERT INTO proof_ai_reviews
                    (proof_id, ai_decision, ai_confidence, ai_progress_increment, ai_reason, ai_raw_response)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    ai_decision = VALUES(ai_decision),
                    ai_confidence = VALUES(ai_confidence),
                    ai_progress_increment = VALUES(ai_progress_increment),
                    ai_reason = VALUES(ai_reason),
                    ai_raw_response = VALUES(ai_raw_response),
                    ai_reviewed_at = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('isiiss', $proofId, $decision, $confidence, $progressIncrement, $reason, $rawResponse);
        return $stmt->execute();
    }

    public function updateReviewState(int $proofId, string $reviewState): bool
    {
        $sql = "UPDATE participation_proofs
                SET review_state = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $reviewState, $proofId);
        return $stmt->execute();
    }

    public function getByIdForParticipation(int $proofId, int $participationId): ?array
    {
        $sql = "SELECT pr.*,
                       air.ai_decision,
                       air.ai_confidence,
                       air.ai_progress_increment,
                       air.ai_reason,
                       air.ai_reviewed_at
                FROM participation_proofs pr
                LEFT JOIN proof_ai_reviews air ON air.proof_id = pr.id
                WHERE pr.id = ? AND pr.participation_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $proofId, $participationId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    public function updateReviewStateForParticipation(int $proofId, int $participationId, string $reviewState): bool
    {
        $sql = "UPDATE participation_proofs
                SET review_state = ?
                WHERE id = ? AND participation_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $reviewState, $proofId, $participationId);
        return $stmt->execute();
    }
}
