-- Check activity table structure
DESCRIBE activity;

-- Check activity data
SELECT 
    id_activity,
    id_user,
    id_company,
    tanggal,
    waktu,
    judul_activity,
    status,
    latitude,
    longitude,
    created_at
FROM activity
ORDER BY created_at DESC
LIMIT 10;

-- Count total activities
SELECT COUNT(*) as total_activities FROM activity;

-- Count by status
SELECT status, COUNT(*) as count 
FROM activity 
GROUP BY status;

-- Check if there are activities in the date range
SELECT 
    MIN(tanggal) as earliest_date,
    MAX(tanggal) as latest_date,
    COUNT(*) as total
FROM activity;

