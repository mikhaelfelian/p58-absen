<?php

namespace App\Controllers;

use App\Models\UserPresensiModel;

class PresensiController extends BaseController
{
    protected $presensi;

    public function __construct()
    {
        parent::__construct();
        $this->presensi = new UserPresensiModel();
    }

    // ABSEN MASUK
    public function masuk()
    {
        $idUser = $this->session->get('user')['id_user'];
        $assignment = $this->getActiveAssignment($idUser);

        if (!$assignment) {
            return redirect()->back()->with('error', 'Anda tidak memiliki assignment aktif.');
        }

        // check if already inside shift
        $last = $this->presensi->getLastPresensi($idUser);
        if ($last && empty($last['waktu_pulang'])) {
            return redirect()->back()->with('error', 'You already clocked in.');
        }

        $latitude  = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $foto      = $this->request->getPost('foto');

        $this->presensi->insert([
            'id_user'       => $idUser,
            'id_company'    => $assignment['id_company'],
            'tanggal'       => date('Y-m-d'),
            'jenis_presensi'=> 'masuk',
            'waktu'         => date('H:i:s'),
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'foto'          => $foto,
        ]);

        return redirect()->to('/dashboard')->with('success', 'Clock-in recorded.');
    }

    // ABSEN PULANG
    public function pulang()
    {
        $idUser = $this->session->get('user')['id_user'];
        $assignment = $this->getActiveAssignment($idUser);

        if (!$assignment) {
            return redirect()->back()->with('error', 'Anda tidak memiliki assignment aktif.');
        }

        $last = $this->presensi->getLastPresensi($idUser);
        if (!$last || !empty($last['waktu_pulang'])) {
            return redirect()->back()->with('error', 'No active shift.');
        }

        $waktuMasuk  = strtotime($last['tanggal'].' '.$last['waktu']);
        $waktuPulang = time();
        $durasi = ($waktuPulang - $waktuMasuk) / 3600;

        $isValid = ($durasi >= intval($assignment['jam_kerja_target'])) ? 1 : 0;

        $this->presensi->update($last['id_user_presensi'], [
            'waktu_pulang' => date('H:i:s'),
            'durasi'       => $durasi,
            'is_valid'     => $isValid,
        ]);

        return redirect()->to('/dashboard')->with('success', 'Clock-out recorded.');
    }

    // HISTORY
    public function history()
    {
        $idUser = $this->session->get('user')['id_user'];
        $data['list'] = $this->presensi->getHistory($idUser);

        $this->data['title'] = 'Riwayat Presensi';
        $this->data = array_merge($this->data, $data);
        $this->view('presensi/history.php', $this->data);
    }

    // UTILITY - GET ACTIVE ASSIGNMENT
    private function getActiveAssignment($idUser)
    {
        $db = db_connect();
        $result = $db->table('user_company')
              ->where('id_user', $idUser)
              ->where('status', 'active')
              ->orderBy('id_user_company', 'DESC')
              ->get()
              ->getRowArray();
        
        return $result;
    }
}
