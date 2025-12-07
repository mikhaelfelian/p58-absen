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
     * Get all scanned patrols for user/company during active shift or today
     * Returns array of patrol IDs with their scan times
     * If $tgl_masuk is provided, returns patrols scanned since shift start
     * Otherwise, returns patrols scanned today (backward compatibility)
     */
    public function getScannedPatrolsToday($id_user, $id_company, $tgl_masuk = null)
    {
        $db = \Config\Database::connect();
        
        if ($tgl_masuk) {
            // Active shift: Get patrols scanned since shift start
            $sql = "
                SELECT ap.id_patrol, MAX(ap.scan_time) as scan_time
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                WHERE a.id_user = ? 
                AND a.id_company = ?
                AND DATE(a.tanggal) >= DATE(?)
                AND TIMESTAMP(a.tanggal, a.waktu) >= ?
                GROUP BY ap.id_patrol
            ";
            $params = [$id_user, $id_company, $tgl_masuk, $tgl_masuk];
        } else {
            // Fallback: Get patrols scanned today (backward compatibility)
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
            $params = [$id_user, $id_company, $today];
        }
        
        $query = $db->query($sql, $params);
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
    
    /**
     * Get patrol progress by id_user_presensi
     * Returns total, completed count, and percentage
     * For active shifts (tgl_masuk provided), also counts patrols from activities with NULL id_user_presensi created after shift start
     */
    public function getPatrolProgressByPresensi($id_user_presensi, $id_company, $tgl_masuk = null, $id_user = null)
    {
        $db = \Config\Database::connect();
        
        // Get total required patrols for this company
        $patrolModel = new CompanyPatrolModel;
        $allPatrols = $patrolModel->getPatrolByCompany($id_company);
        $totalPatrols = count($allPatrols);
        
        if ($totalPatrols == 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 100
            ];
        }
        
        // Get user_id from presensi if not provided
        if (!$id_user && $id_user_presensi) {
            $presensi = $db->table('user_presensi')->where('id', $id_user_presensi)->get()->getRowArray();
            $id_user = $presensi['id_user'] ?? null;
        }
        
        // Build query based on whether tgl_masuk is provided (active shift)
        if ($tgl_masuk && $id_user) {
            // Active shift: Count patrols where id_user_presensi matches OR (id_user_presensi IS NULL AND created after shift start)
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                LEFT JOIN user_presensi up ON a.id_user_presensi = up.id
                WHERE a.id_company = ?
                AND a.id_user = ?
                AND (
                    (a.id_user_presensi = ? AND up.id IS NOT NULL AND up.tgl_keluar IS NULL)
                    OR
                    (a.id_user_presensi IS NULL AND DATE(a.tanggal) >= DATE(?) AND TIMESTAMP(a.tanggal, a.waktu) >= ?)
                )
            ";
            $params = [$id_company, $id_user, $id_user_presensi, $tgl_masuk, $tgl_masuk];
        } else {
            // Completed shift: Only count patrols with matching id_user_presensi
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                JOIN user_presensi up ON a.id_user_presensi = up.id
                WHERE a.id_user_presensi = ?
                AND a.id_user_presensi IS NOT NULL
                AND a.id_company = ?
                AND up.tgl_keluar IS NULL
            ";
            $params = [$id_user_presensi, $id_company];
        }
        
        $scannedPatrols = $db->query($sql, $params)->getResult();
        $completedCount = count($scannedPatrols);
        
        $percentage = $totalPatrols > 0 ? round(($completedCount / $totalPatrols) * 100) : 0;
        
        return [
            'total' => $totalPatrols,
            'completed' => $completedCount,
            'percentage' => $percentage
        ];
    }
    
    /**
     * Get last scanned patrol data by id_user_presensi
     * Returns patrol data with nama_patrol, urutan, scan_time
     * For active shifts (tgl_masuk provided), also includes patrols from activities with NULL id_user_presensi created after shift start
     */
    public function getLastPatrolDataByPresensi($id_user_presensi, $tgl_masuk = null, $id_user = null, $id_company = null)
    {
        $db = \Config\Database::connect();
        
        // Get user_id and id_company from presensi if not provided
        if ((!$id_user || !$id_company) && $id_user_presensi) {
            $presensi = $db->table('user_presensi')->where('id', $id_user_presensi)->get()->getRowArray();
            if (!$id_user) $id_user = $presensi['id_user'] ?? null;
            if (!$id_company) $id_company = $presensi['id_company'] ?? null;
        }
        
        // Check if urutan column exists
        $fields = $db->getFieldData('company_patrol');
        $hasUrutan = false;
        foreach ($fields as $field) {
            if ($field->name === 'urutan') {
                $hasUrutan = true;
                break;
            }
        }
        
        // Build query based on whether tgl_masuk is provided (active shift)
        if ($tgl_masuk && $id_user && $id_company) {
            // Active shift: Include patrols where id_user_presensi matches OR (id_user_presensi IS NULL AND created after shift start)
            if ($hasUrutan) {
                $sql = "
                    SELECT ap.*, cp.urutan, cp.nama_patrol, cp.id_patrol
                    FROM activity_patrol ap
                    JOIN activity a ON ap.id_activity = a.id_activity
                    JOIN company_patrol cp ON ap.id_patrol = cp.id_patrol
                    LEFT JOIN user_presensi up ON a.id_user_presensi = up.id
                    WHERE a.id_company = ?
                    AND a.id_user = ?
                    AND (
                        (a.id_user_presensi = ? AND up.id IS NOT NULL AND up.tgl_keluar IS NULL)
                        OR
                        (a.id_user_presensi IS NULL AND DATE(a.tanggal) >= DATE(?) AND TIMESTAMP(a.tanggal, a.waktu) >= ?)
                    )
                    ORDER BY cp.urutan DESC, ap.scan_time DESC
                    LIMIT 1
                ";
            } else {
                $sql = "
                    SELECT ap.*, cp.id_patrol, cp.nama_patrol
                    FROM activity_patrol ap
                    JOIN activity a ON ap.id_activity = a.id_activity
                    JOIN company_patrol cp ON ap.id_patrol = cp.id_patrol
                    LEFT JOIN user_presensi up ON a.id_user_presensi = up.id
                    WHERE a.id_company = ?
                    AND a.id_user = ?
                    AND (
                        (a.id_user_presensi = ? AND up.id IS NOT NULL AND up.tgl_keluar IS NULL)
                        OR
                        (a.id_user_presensi IS NULL AND DATE(a.tanggal) >= DATE(?) AND TIMESTAMP(a.tanggal, a.waktu) >= ?)
                    )
                    ORDER BY cp.id_patrol DESC, ap.scan_time DESC
                    LIMIT 1
                ";
            }
            $params = [$id_company, $id_user, $id_user_presensi, $tgl_masuk, $tgl_masuk];
        } else {
            // Completed shift: Only count patrols with matching id_user_presensi
            if ($hasUrutan) {
                $sql = "
                    SELECT ap.*, cp.urutan, cp.nama_patrol, cp.id_patrol
                    FROM activity_patrol ap
                    JOIN activity a ON ap.id_activity = a.id_activity
                    JOIN company_patrol cp ON ap.id_patrol = cp.id_patrol
                    JOIN user_presensi up ON a.id_user_presensi = up.id
                    WHERE a.id_user_presensi = ?
                    AND a.id_user_presensi IS NOT NULL
                    AND up.tgl_keluar IS NULL
                    ORDER BY cp.urutan DESC, ap.scan_time DESC
                    LIMIT 1
                ";
            } else {
                $sql = "
                    SELECT ap.*, cp.id_patrol, cp.nama_patrol
                    FROM activity_patrol ap
                    JOIN activity a ON ap.id_activity = a.id_activity
                    JOIN company_patrol cp ON ap.id_patrol = cp.id_patrol
                    JOIN user_presensi up ON a.id_user_presensi = up.id
                    WHERE a.id_user_presensi = ?
                    AND a.id_user_presensi IS NOT NULL
                    AND up.tgl_keluar IS NULL
                    ORDER BY cp.id_patrol DESC, ap.scan_time DESC
                    LIMIT 1
                ";
            }
            $params = [$id_user_presensi];
        }
        
        $query = $db->query($sql, $params);
        $result = $query->getRow();
        
        // Convert to array if object
        if ($result && is_object($result)) {
            return (array) $result;
        }
        
        return $result;
    }
    
    /**
     * Get next patrol after last scanned patrol for a presensi
     * Returns the next patrol in sequence that hasn't been scanned
     * For active shifts (tgl_masuk provided), also includes patrols from activities with NULL id_user_presensi created after shift start
     */
    public function getNextPatrolByPresensi($id_user_presensi, $id_company, $tgl_masuk = null, $id_user = null)
    {
        $db = \Config\Database::connect();
        
        // Get user_id from presensi if not provided
        if (!$id_user && $id_user_presensi) {
            $presensi = $db->table('user_presensi')->where('id', $id_user_presensi)->get()->getRowArray();
            $id_user = $presensi['id_user'] ?? null;
        }
        
        // Get last scanned patrol for this presensi
        $lastPatrol = $this->getLastPatrolDataByPresensi($id_user_presensi, $tgl_masuk, $id_user, $id_company);
        
        // Get all patrols for this company
        $patrolModel = new CompanyPatrolModel;
        $allPatrols = $patrolModel->getPatrolByCompany($id_company);
        
        if (empty($allPatrols)) {
            return null;
        }
        
        // Get scanned patrol IDs for this presensi
        // Build query based on whether tgl_masuk is provided (active shift)
        if ($tgl_masuk && $id_user) {
            // Active shift: Include patrols where id_user_presensi matches OR (id_user_presensi IS NULL AND created after shift start)
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                LEFT JOIN user_presensi up ON a.id_user_presensi = up.id
                WHERE a.id_company = ?
                AND a.id_user = ?
                AND (
                    (a.id_user_presensi = ? AND up.id IS NOT NULL AND up.tgl_keluar IS NULL)
                    OR
                    (a.id_user_presensi IS NULL AND DATE(a.tanggal) >= DATE(?) AND TIMESTAMP(a.tanggal, a.waktu) >= ?)
                )
            ";
            $params = [$id_company, $id_user, $id_user_presensi, $tgl_masuk, $tgl_masuk];
        } else {
            // Completed shift: Only count patrols with matching id_user_presensi
            $sql = "
                SELECT DISTINCT ap.id_patrol
                FROM activity_patrol ap
                JOIN activity a ON ap.id_activity = a.id_activity
                JOIN user_presensi up ON a.id_user_presensi = up.id
                WHERE a.id_user_presensi = ?
                AND a.id_user_presensi IS NOT NULL
                AND up.tgl_keluar IS NULL
            ";
            $params = [$id_user_presensi];
        }
        $scannedPatrols = $db->query($sql, $params)->getResult();
        $scannedIds = array_column($scannedPatrols, 'id_patrol');
        
        // Find first uncompleted patrol
        foreach ($allPatrols as $patrol) {
            $patrolId = is_array($patrol) ? ($patrol['id_patrol'] ?? null) : ($patrol->id_patrol ?? null);
            if ($patrolId && !in_array($patrolId, $scannedIds)) {
                // Convert to array if object
                if (is_object($patrol)) {
                    return (array) $patrol;
                }
                return $patrol;
            }
        }
        
        // All patrols completed
        return null;
    }
}
