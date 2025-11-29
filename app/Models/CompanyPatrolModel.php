<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyPatrolModel extends Model
{
    protected $table = 'company_patrol';
    protected $primaryKey = 'id_patrol';
    protected $returnType = 'object';
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
    
    // Cache for column checks
    private static $hasStatusColumn = null;
    private static $hasUrutanColumn = null;
    private static $tableFields = null;
    
    /**
     * Get table fields and cache them
     */
    private function getTableFields()
    {
        if (self::$tableFields === null) {
            try {
                self::$tableFields = $this->db->getFieldData($this->table);
            } catch (\Exception $e) {
                self::$tableFields = [];
            }
        }
        return self::$tableFields;
    }
    
    /**
     * Check if status column exists in the table
     */
    private function hasStatusColumn()
    {
        if (self::$hasStatusColumn === null) {
            $fields = $this->getTableFields();
            self::$hasStatusColumn = false;
            foreach ($fields as $field) {
                if ($field->name === 'status') {
                    self::$hasStatusColumn = true;
                    break;
                }
            }
        }
        return self::$hasStatusColumn;
    }
    
    /**
     * Check if urutan column exists in the table
     */
    private function hasUrutanColumn()
    {
        if (self::$hasUrutanColumn === null) {
            $fields = $this->getTableFields();
            self::$hasUrutanColumn = false;
            foreach ($fields as $field) {
                if ($field->name === 'urutan') {
                    self::$hasUrutanColumn = true;
                    break;
                }
            }
        }
        return self::$hasUrutanColumn;
    }

    public function getPatrolByCompany($id_company)
    {
        $query = $this->where('id_company', $id_company);
        
        // Only filter by status if the column exists
        if ($this->hasStatusColumn()) {
            $query->where('status', 'active');
        }
        
        // Order by urutan if it exists, otherwise by id_patrol
        if ($this->hasUrutanColumn()) {
            $query->orderBy('urutan', 'ASC');
        }
        $query->orderBy('id_patrol', 'ASC');
        
        return $query->findAll();
    }
    
    /**
     * Get next patrol in sequence for a user/company
     * Returns the first patrol if none scanned today, or the next one after last scanned
     */
    public function getNextPatrolInSequence($id_company, $id_user, $last_scanned_value = null)
    {
        $query = $this->where('id_company', $id_company);
        
        // Only filter by status if the column exists
        if ($this->hasStatusColumn()) {
            $query->where('status', 'active');
        }
        
        $hasUrutan = $this->hasUrutanColumn();
        
        // If last scanned value is provided, determine which column to compare
        if ($last_scanned_value !== null) {
            if ($hasUrutan) {
                $query->where('urutan >', $last_scanned_value);
                $query->orderBy('urutan', 'ASC');
            } else {
                $query->where('id_patrol >', $last_scanned_value);
            }
        } elseif ($hasUrutan) {
            $query->orderBy('urutan', 'ASC');
        }
        
        $query->orderBy('id_patrol', 'ASC');
        
        return $query->first();
    }
    
    /**
     * Get first patrol in sequence for a company
     */
    public function getFirstPatrol($id_company)
    {
        $query = $this->where('id_company', $id_company);
        
        // Only filter by status if the column exists
        if ($this->hasStatusColumn()) {
            $query->where('status', 'active');
        }
        
        // Order by urutan if it exists, otherwise by id_patrol
        if ($this->hasUrutanColumn()) {
            $query->orderBy('urutan', 'ASC');
        }
        $query->orderBy('id_patrol', 'ASC');
        
        return $query->first();
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
