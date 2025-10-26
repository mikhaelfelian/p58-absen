<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyPatrolModel extends Model
{
    protected $table = 'company_patrol';
    protected $primaryKey = 'id_patrol';
    protected $allowedFields = [
        'id_company',
        'nama_patrol',
        'foto',
        'barcode',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPatrolByCompany($id_company)
    {
        return $this->where('id_company', $id_company)
                    ->orderBy('id_patrol', 'ASC')
                    ->findAll();
    }

    /**
     * Save multiple patrol points for a company
     */
    public function savePatrolPoints($id_company, $patrol_data)
    {
        // Delete existing patrol points for this company
        $this->where('id_company', $id_company)->delete();

        if (empty($patrol_data)) {
            return true;
        }

        $patrol_points = [];
        foreach ($patrol_data as $index => $patrol) {
            if (!empty($patrol['nama_patrol'])) {
                $patrol_points[] = [
                    'id_company' => $id_company,
                    'nama_patrol' => $patrol['nama_patrol'],
                    'foto' => $patrol['foto'] ?? '',
                    'barcode' => $this->generateBarcode($id_company, $index + 1),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        if (!empty($patrol_points)) {
            return $this->insertBatch($patrol_points);
        }

        return true;
    }

    /**
     * Generate barcode for patrol point
     */
    private function generateBarcode($id_company, $sequence)
    {
        // Generate barcode: COMPANY_ID + SEQUENCE + TIMESTAMP
        $timestamp = date('YmdHis');
        $barcode = sprintf('PATROL_%03d_%03d_%s', $id_company, $sequence, $timestamp);
        return $barcode;
    }
    
    /**
     * Validate barcode and get patrol info
     */
    public function validateBarcode($barcode, $id_company = null)
    {
        $db = \Config\Database::connect();
        
        $sql = "
            SELECT cp.*, c.nama_company 
            FROM company_patrol cp 
            JOIN company c ON cp.id_company = c.id_company 
            WHERE cp.barcode = ?
        ";
        
        $params = [$barcode];
        
        // Add company filter if provided
        if ($id_company) {
            $sql .= " AND cp.id_company = ?";
            $params[] = $id_company;
        }
        
        $query = $db->query($sql, $params);
        
        return $query->getRow();
    }
}
