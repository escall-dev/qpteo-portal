# Database Migrations Rule

Whenever any database changes are made (adding/modifying columns, new tables, indexes, or new default configuration/settings data):

1. **Keep `database/schema.sql` up to date**: Always reflect the full, clean baseline database state for new installations.
2. **ALWAYS create a separate migration SQL file**: Create a dedicated incremental script in `database/` (e.g. `database/update_<feature_name>.sql`).
3. **Hostinger phpMyAdmin Compatibility**:
   - Write self-contained, safe SQL queries using `ALTER TABLE ... ADD COLUMN ...`, `INSERT INTO ... ON DUPLICATE KEY UPDATE ...`, and `INSERT ... SELECT ... WHERE NOT EXISTS (...)`.
   - The user must always be able to directly copy-paste or import the file into phpMyAdmin on Hostinger without deleting or corrupting existing live data.
