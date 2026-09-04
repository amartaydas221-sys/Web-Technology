<?php
namespace App\Models;

use App\Core\Model;

class CalculatorLog extends Model
{
    public function create(?int $userId, array $data, float $total): void
    {
        $stmt = $this->db->prepare('INSERT INTO calculator_logs (user_id,destination,travelers,days,transport,accommodation,food,other,total) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$userId,$data['destination'],$data['travelers'],$data['days'],$data['transport'],$data['accommodation'],$data['food'],$data['other'],$total]);
    }

    public function recentForUser(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare('SELECT * FROM calculator_logs WHERE user_id=? ORDER BY calculated_at DESC LIMIT ' . max(1,$limit));
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
