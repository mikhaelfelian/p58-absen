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
}
