-- Runs only the first time the local MySQL container starts (empty data volume).
-- One schema per service, same as RDS in the cloud. A service never reads another service's schema.
-- metabase_db keeps Metabase dashboards so they are not lost when Metabase restarts.

CREATE DATABASE IF NOT EXISTS attraction_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS pricing_db    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS booking_db    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS payment_db    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS event_db      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS metabase_db   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
