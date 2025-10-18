-- ========================================
-- FIX COMPANY ASSIGNMENT ISSUE
-- ========================================
-- Run this SQL in phpMyAdmin or MySQL client

-- 1. CHECK YOUR USER ID
-- Replace 'IRFAN RIZKY' with your actual username
SELECT id_user, nama, email FROM user WHERE nama LIKE '%IRFAN%';
-- Note: Copy the id_user from the result

-- 2. CHECK ALL YOUR ASSIGNMENTS
-- Replace X with your id_user from step 1
SELECT 
    uc.id_user_company,
    uc.id_user,
    uc.id_company,
    c.nama_company,
    uc.status AS assignment_status,
    c.status AS company_status,
    uc.tanggal_mulai,
    uc.tanggal_selesai,
    CURDATE() AS today
FROM user_company uc
LEFT JOIN company c ON uc.id_company = c.id_company
WHERE uc.id_user = X;  -- Replace X with your id_user

-- 3. CHECK WHAT THE APP SEES (Active companies only)
-- Replace X with your id_user
SELECT 
    uc.id_user_company,
    c.nama_company,
    uc.status AS assignment_status,
    c.status AS company_status,
    uc.tanggal_mulai,
    uc.tanggal_selesai
FROM user_company uc
LEFT JOIN company c ON uc.id_company = c.id_company
WHERE uc.id_user = X  -- Replace X with your id_user
AND uc.status = 'active'
AND c.status = 'active'
AND (uc.tanggal_mulai IS NULL OR uc.tanggal_mulai <= CURDATE())
AND (uc.tanggal_selesai IS NULL OR uc.tanggal_selesai >= CURDATE());

-- ========================================
-- FIXES (Run only the ones you need)
-- ========================================

-- FIX 1: Set ALL companies to active
UPDATE company SET status = 'active';

-- FIX 2: Set ALL user_company assignments to active
UPDATE user_company SET status = 'active';

-- FIX 3: Remove date restrictions for specific user
-- Replace X with your id_user
UPDATE user_company 
SET tanggal_mulai = NULL, tanggal_selesai = NULL 
WHERE id_user = X;

-- FIX 4: Set proper date range for specific user
-- Replace X with your id_user
UPDATE user_company 
SET 
    tanggal_mulai = '2025-10-01',  -- Set to past or today
    tanggal_selesai = '2026-12-31', -- Set to future
    status = 'active'
WHERE id_user = X;

-- FIX 5: Quick fix - Activate everything for your user
-- Replace X with your id_user
UPDATE user_company uc
LEFT JOIN company c ON uc.id_company = c.id_company
SET 
    uc.status = 'active',
    uc.tanggal_mulai = NULL,
    uc.tanggal_selesai = NULL,
    c.status = 'active'
WHERE uc.id_user = X;

-- ========================================
-- VERIFICATION
-- ========================================

-- After running fixes, check again:
-- Replace X with your id_user
SELECT 
    c.nama_company,
    uc.status AS assignment_status,
    c.status AS company_status,
    uc.tanggal_mulai,
    uc.tanggal_selesai,
    CASE 
        WHEN uc.status = 'active' 
        AND c.status = 'active'
        AND (uc.tanggal_mulai IS NULL OR uc.tanggal_mulai <= CURDATE())
        AND (uc.tanggal_selesai IS NULL OR uc.tanggal_selesai >= CURDATE())
        THEN '✅ WILL SHOW'
        ELSE '❌ HIDDEN'
    END AS visibility
FROM user_company uc
LEFT JOIN company c ON uc.id_company = c.id_company
WHERE uc.id_user = X;

-- ========================================
-- EXPECTED RESULT
-- ========================================
-- After fixes, the last query should show:
-- nama_company | assignment_status | company_status | visibility
-- PT ABC       | active            | active         | ✅ WILL SHOW

