-- Check what data exists in activity table
SELECT 
    id_activity,
    id_user,
    id_company,
    tanggal,
    DATE_FORMAT(tanggal, '%d/%m/%Y') as tanggal_formatted,
    waktu,
    judul_activity,
    status
FROM activity
ORDER BY tanggal DESC
LIMIT 20;

-- Check date range
SELECT 
    MIN(tanggal) as earliest,
    MAX(tanggal) as latest,
    COUNT(*) as total
FROM activity;

-- Check for company ID 1 (CV. Tigera Cyber Solution)
SELECT 
    id_activity,
    tanggal,
    judul_activity,
    status
FROM activity
WHERE id_company = 1
ORDER BY tanggal DESC;

-- Check what the query would return
SELECT activity.*, 
    company.nama_company, 
    user.nama, 
    user.nip
FROM activity
LEFT JOIN company USING(id_company)
LEFT JOIN user USING(id_user)
WHERE tanggal BETWEEN '2025-10-01' AND '2025-10-31'
  AND activity.id_company = 1
ORDER BY tanggal DESC, waktu DESC;

