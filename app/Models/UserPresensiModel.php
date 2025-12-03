<?php

namespace App\Models;

use CodeIgniter\Model;

class UserPresensiModel extends Model
{
    protected $table      = 'user_presensi';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'id_user','id_company','tgl_masuk','tgl_keluar',
        'durasi','is_valid',
        'latitude','longitude','foto','created_at','updated_at'
    ];

    protected $useTimestamps = true;

    // get last presensi
    public function getLastPresensi($idUser)
    {
        $result = $this->where('id_user', $idUser)
                    ->orderBy('id', 'DESC')
                    ->first();
        
        // Convert to array if object for consistent access
        if ($result && is_object($result)) {
            return (array) $result;
        }
        
        return $result;
    }

    // get last presensi with row lock (FOR UPDATE) - prevents race conditions
    public function getLastPresensiWithLock($idUser)
    {
        $db = \Config\Database::connect();
        $sql = "SELECT * FROM {$this->table} 
                WHERE id_user = ? 
                ORDER BY id DESC 
                LIMIT 1 
                FOR UPDATE";
        $result = $db->query($sql, [$idUser])->getRowArray();
        
        return $result;
    }

    // get history
    public function getHistory($idUser)
    {
        return $this->where('id_user', $idUser)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    // Insert presensi masuk (clock-in)
    public function insertMasuk($idUser, $idCompany)
    {
        $data = [
            'id_user' => $idUser,
            'id_company' => $idCompany,
            'tgl_masuk' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert($data, true); // Return insert ID
    }

    // Insert presensi pulang (clock-out) - updates latest masuk record
    public function insertPulang($idUser, $jamKerjaTarget = 12)
    {
        // Get latest record without tgl_keluar (active shift)
        $lastMasuk = $this->where('id_user', $idUser)
                          ->where('tgl_keluar IS NULL')
                          ->orderBy('id', 'DESC')
                          ->first();
        
        if (!$lastMasuk) {
            return false; // No active shift found
        }
        
        // Convert to array if object
        if (is_object($lastMasuk)) {
            $lastMasuk = (array) $lastMasuk;
        }
        
        // Calculate duration from tgl_masuk to current time
        $tglMasuk = strtotime($lastMasuk['tgl_masuk']);
        $tglKeluar = time();
        $durasi = ($tglKeluar - $tglMasuk) / 3600; // Duration in hours
        
        // Check if duration meets target
        $isValid = ($durasi >= intval($jamKerjaTarget)) ? 1 : 0;
        
        // Update the record
        $updateData = [
            'tgl_keluar' => date('Y-m-d H:i:s'),
            'durasi' => $durasi,
            'is_valid' => $isValid
        ];
        
        return $this->update($lastMasuk['id'], $updateData);
    }
}
