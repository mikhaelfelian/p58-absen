-- ============================================================
-- INJECT MULTI-COMPANY ATTENDANCE MODULES
-- Database: db_p58_absen
-- Created: 2025-10-18
-- ============================================================

USE `db_p58_absen`;

-- ============================================================
-- 1. INSERT MODULES
-- ============================================================

INSERT INTO `module` (`id_module`, `nama_module`, `judul_module`, `id_module_status`, `login`, `deskripsi`) VALUES
(106, 'company', 'Master Company', 1, 'Y', 'Manajemen data company/perusahaan klien'),
(107, 'user-company', 'Assign Company ke User', 1, 'Y', 'Assign company ke SDM/pegawai'),
(108, 'activity', 'Activity Report', 1, 'Y', 'Laporan aktivitas pekerjaan SDM'),
(109, 'mobile-activity', 'Activity Mobile', 1, 'Y', 'Input aktivitas dari mobile');

-- ============================================================
-- 2. INSERT MODULE PERMISSIONS
-- ============================================================

-- Company Module Permissions
INSERT INTO `module_permission` (`id_module_permission`, `id_module`, `nama_permission`, `judul_permission`, `keterangan`) VALUES
(401, 106, 'create', 'Create Data', 'Hak akses untuk menambah data company'),
(402, 106, 'read_all', 'Read All Data', 'Hak akses untuk membaca semua data company'),
(403, 106, 'update_all', 'Update All Data', 'Hak akses untuk mengupdate semua data company'),
(404, 106, 'delete_all', 'Delete All Data', 'Hak akses untuk menghapus semua data company');

-- User Company Module Permissions
INSERT INTO `module_permission` (`id_module_permission`, `id_module`, `nama_permission`, `judul_permission`, `keterangan`) VALUES
(405, 107, 'create', 'Create Data', 'Hak akses untuk assign company ke user'),
(406, 107, 'read_all', 'Read All Data', 'Hak akses untuk membaca semua assignment'),
(407, 107, 'update_all', 'Update All Data', 'Hak akses untuk mengupdate semua assignment'),
(408, 107, 'delete_all', 'Delete All Data', 'Hak akses untuk menghapus semua assignment');

-- Activity Module Permissions
INSERT INTO `module_permission` (`id_module_permission`, `id_module`, `nama_permission`, `judul_permission`, `keterangan`) VALUES
(409, 108, 'create', 'Create Data', 'Hak akses untuk menambah activity'),
(410, 108, 'read_all', 'Read All Data', 'Hak akses untuk membaca semua activity'),
(411, 108, 'read_own', 'Read Own Data', 'Hak akses untuk membaca activity sendiri'),
(412, 108, 'update_all', 'Update All Data', 'Hak akses untuk mengupdate semua activity'),
(413, 108, 'delete_all', 'Delete All Data', 'Hak akses untuk menghapus semua activity'),
(414, 108, 'approve', 'Approve Activity', 'Hak akses untuk approve/reject activity');

-- Mobile Activity Module Permissions
INSERT INTO `module_permission` (`id_module_permission`, `id_module`, `nama_permission`, `judul_permission`, `keterangan`) VALUES
(415, 109, 'create', 'Create Data', 'Hak akses untuk input activity dari mobile'),
(416, 109, 'read_own', 'Read Own Data', 'Hak akses untuk membaca activity sendiri');

-- ============================================================
-- 3. INSERT MENUS
-- ============================================================

INSERT INTO `menu` (`id_menu`, `nama_menu`, `id_menu_kategori`, `class`, `url`, `id_module`, `id_parent`, `aktif`, `new`, `urut`) VALUES
(107, 'Master Company', 6, 'fas fa-building', 'company', 106, NULL, 1, 0, 10),
(108, 'Assign Company', 6, 'fas fa-user-tie', 'user-company', 107, 1, 1, 0, 3),
(109, 'Activity Report', 6, 'fas fa-tasks', 'activity', 108, NULL, 1, 0, 7),
(110, 'Input Activity', 6, 'fas fa-clipboard-check', 'mobile-activity', 109, 60, 1, 0, 3);

-- ============================================================
-- 4. ASSIGN MENUS TO ROLES
-- ============================================================

-- Admin role (id_role = 1) gets all menus
INSERT INTO `menu_role` (`id_menu`, `id_role`) VALUES
(107, 1), -- Master Company
(108, 1), -- Assign Company
(109, 1), -- Activity Report
(110, 1), -- Input Activity (Mobile)
(110, 2); -- User role can also input activity

-- ============================================================
-- 5. ASSIGN PERMISSIONS TO ADMIN ROLE (id_role = 1)
-- ============================================================

INSERT INTO `role_module_permission` (`id_role`, `id_module_permission`) VALUES
-- Company permissions
(1, 401), (1, 402), (1, 403), (1, 404),
-- User Company permissions
(1, 405), (1, 406), (1, 407), (1, 408),
-- Activity permissions
(1, 409), (1, 410), (1, 411), (1, 412), (1, 413), (1, 414),
-- Mobile Activity permissions
(1, 415), (1, 416);

-- ============================================================
-- 6. ASSIGN PERMISSIONS TO USER ROLE (id_role = 2)
-- ============================================================

INSERT INTO `role_module_permission` (`id_role`, `id_module_permission`) VALUES
-- User can only view their own activities and create new ones
(2, 411), -- read_own activity
(2, 415), -- create activity from mobile
(2, 416); -- read_own from mobile

-- ============================================================
-- NOTES:
-- ============================================================
-- 1. Run migrations first: php spark migrate
-- 2. Then run this SQL file to inject modules/menus
-- 3. Module IDs start from 106 to avoid conflicts
-- 4. Permission IDs start from 401 to avoid conflicts
-- 5. Menu IDs start from 107 to avoid conflicts
-- ============================================================

