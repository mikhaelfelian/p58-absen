<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityPatrolModel extends Model
{
    protected $table = 'activity_patrol';
    protected $primaryKey = 'id_activity_patrol';
    protected $allowedFields = [
        'id_activity',
        'id_patrol',
        'barcode_scanned',
        'scan_time',
        'latitude',
        'longitude',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Validate barcode and get patrol info
     */
    public function validateBarcode($barcode)
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT cp.*, c.nama_company 
            FROM company_patrol cp 
            JOIN company c ON cp.id_company = c.id_company 
            WHERE cp.barcode = ?
        ", [$barcode]);
        
        return $query->getRow();
    }

    /**
     * Save patrol scan record
     */
    public function savePatrolScan($id_activity, $id_patrol, $barcode, $latitude = null, $longitude = null)
    {
        $data = [
            'id_activity' => $id_activity,
            'id_patrol' => $id_patrol,
            'barcode_scanned' => $barcode,
            'scan_time' => date('Y-m-d H:i:s'),
            'latitude' => $latitude,
            'longitude' => $longitude
        ];

        return $this->insert($data);
    }

    /**
     * Get patrol scans for an activity
     */
    public function getPatrolScansByActivity($id_activity)
    {
        $db = \Config\Database::connect();
        
        $query = $db->query("
            SELECT ap.*, cp.nama_patrol, c.nama_company 
            FROM activity_patrol ap 
            JOIN company_patrol cp ON ap.id_patrol = cp.id_patrol 
            JOIN company c ON cp.id_company = c.id_company 
            WHERE ap.id_activity = ?
            ORDER BY ap.scan_time ASC
        ", [$id_activity]);
        
        return $query->getResult();
    }

    /**
     * Check if barcode already scanned for this activity
     */
    public function isBarcodeAlreadyScanned($id_activity, $barcode)
    {
        return $this->where('id_activity', $id_activity)
                   ->where('barcode_scanned', $barcode)
                   ->countAllResults() > 0;
    }
    
    /**
     * Get last scanned patrol for user/company today
     * Returns the patrol with highest urutan scanned today (or highest id_patrol if urutan doesn't exist)
     */
    public function getLastScannedPatrolToday($id_user, $id_company)
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        
        // Check if urutan column exists
        $fields = $db->getFieldData('company_patrol');
        $hasUrutan = false;
        foreach ($fields as $field) {
            if ($field->name === 'urutan') {
                $hasUrutan = true;
                break;
            }
        }
        
        if ($hasUrutan) {
            $sql = "
                SELECT ap.*, cp.urutan, cp.nama_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                JOIN company_patrol cp ON ap.id_patrol = cp.id_patrol
                WHERE a.id_user = ? 
                AND a.id_company = ?
                AND DATE(a.tanggal) = ?
                ORDER BY cp.urutan DESC, ap.scan_time DESC
                LIMIT 1
            ";
        } else {
            // Use id_patrol for ordering if urutan doesn't exist
            $sql = "
                SELECT ap.*, cp.id_patrol, cp.nama_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                JOIN company_patrol cp ON ap.id_patrol = cp.id_patrol
                WHERE a.id_user = ? 
                AND a.id_company = ?
                AND DATE(a.tanggal) = ?
                ORDER BY cp.id_patrol DESC, ap.scan_time DESC
                LIMIT 1
            ";
        }
        
        $query = $db->query($sql, [$id_user, $id_company, $today]);
        return $query->getRow();
    }
    
    /**
     * Check if all patrols are completed for user/company
     * If tgl_masuk is provided, only checks patrols scanned AFTER that time (within current shift)
     * Otherwise, checks all patrols scanned today
     * Returns true if all active patrols have been scanned
     */
    public function areAllPatrolsCompleted($id_user, $id_company, $tgl_masuk = null)
    {
        $db = \Config\Database::connect();
        
        // Get total active patrols for this company
        $patrolModel = new CompanyPatrolModel;
        $allPatrols = $patrolModel->getPatrolByCompany($id_company);
        $totalPatrols = count($allPatrols);
        
        if ($totalPatrols == 0) {
            // No patrols required, so considered completed
            return true;
        }
        
        // Build query based on whether tgl_masuk is provided
        if ($tgl_masuk) {
            // Check patrols scanned AFTER shift start time
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                WHERE a.id_user = ? 
                AND a.id_company = ?
                AND DATE(a.tanggal) >= DATE(?)
                AND a.tanggal >= ?
            ";
            $params = [$id_user, $id_company, $tgl_masuk, $tgl_masuk];
        } else {
            // Fallback: Check all patrols scanned today (backward compatibility)
            $today = date('Y-m-d');
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                WHERE a.id_user = ? 
                AND a.id_company = ?
                AND DATE(a.tanggal) = ?
            ";
            $params = [$id_user, $id_company, $today];
        }
        
        $scannedPatrols = $db->query($sql, $params)->getResult();
        $scannedCount = count($scannedPatrols);
        
        // All patrols must be scanned
        return $scannedCount >= $totalPatrols;
    }
    
    /**
     * Get all scanned patrols for user/company today
     * Returns array of patrol IDs with their scan times
     */
    public function getScannedPatrolsToday($id_user, $id_company)
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        
        $sql = "
            SELECT ap.id_patrol, MAX(ap.scan_time) as scan_time
            FROM activity_patrol ap
            JOIN activity a ON ap.id_activity = a.id_activity
            WHERE a.id_user = ? 
            AND a.id_company = ?
            AND DATE(a.tanggal) = ?
            GROUP BY ap.id_patrol
        ";
        
        $query = $db->query($sql, [$id_user, $id_company, $today]);
        $results = $query->getResult();
        
        // Convert to array format: [id_patrol => scan_time]
        $scannedPatrols = [];
        foreach ($results as $row) {
            $patrolId = is_array($row) ? ($row['id_patrol'] ?? null) : ($row->id_patrol ?? null);
            $scanTime = is_array($row) ? ($row['scan_time'] ?? null) : ($row->scan_time ?? null);
            if ($patrolId) {
                $scannedPatrols[$patrolId] = $scanTime;
            }
        }
        
        return $scannedPatrols;
    }
    
    /**
     * Get list of uncompleted patrols for user/company
     * If tgl_masuk is provided, only checks patrols scanned AFTER that time (within current shift)
     * Otherwise, checks all patrols scanned today
     * Returns array of patrol objects that haven't been scanned
     */
    public function getUncompletedPatrols($id_user, $id_company, $tgl_masuk = null)
    {
        $db = \Config\Database::connect();
        
        // Get all active patrols for this company
        $patrolModel = new CompanyPatrolModel;
        $allPatrols = $patrolModel->getPatrolByCompany($id_company);
        
        if (empty($allPatrols)) {
            return [];
        }
        
        // Build query based on whether tgl_masuk is provided
        if ($tgl_masuk) {
            // Check patrols scanned AFTER shift start time
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                WHERE a.id_user = ? 
                AND a.id_company = ?
                AND DATE(a.tanggal) >= DATE(?)
                AND a.tanggal >= ?
            ";
            $params = [$id_user, $id_company, $tgl_masuk, $tgl_masuk];
        } else {
            // Fallback: Check all patrols scanned today (backward compatibility)
            $today = date('Y-m-d');
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                WHERE a.id_user = ? 
                AND a.id_company = ?
                AND DATE(a.tanggal) = ?
            ";
            $params = [$id_user, $id_company, $today];
        }
        
        $scannedPatrols = $db->query($sql, $params)->getResult();
        $scannedIds = array_column($scannedPatrols, 'id_patrol');
        
        // Find uncompleted patrols
        $uncompleted = [];
        foreach ($allPatrols as $patrol) {
            // Handle both object and array formats
            $patrolId = is_array($patrol) ? ($patrol['id_patrol'] ?? null) : ($patrol->id_patrol ?? null);
            
            if ($patrolId && !in_array($patrolId, $scannedIds)) {
                $uncompleted[] = $patrol;
            }
        }
        
        return $uncompleted;
    }
}
