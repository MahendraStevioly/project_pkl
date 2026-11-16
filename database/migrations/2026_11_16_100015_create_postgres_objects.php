<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Objek PostgreSQL untuk matkul SQL Advanced:
 *  - 2 trigger function (riwayat status lamaran, log perubahan role)
 *  - 1 stored function transaksional (fn_accept_application)
 *  - 2 view (LEFT JOIN + window RANK) dan 1 materialized view (CTE + window LAG)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- Trigger 1: riwayat + log setiap perubahan status lamaran ----------------
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_log_application_status() RETURNS trigger AS $$
DECLARE
    v_from text := NULL;
    v_actor bigint;
BEGIN
    IF TG_OP = 'UPDATE' THEN
        IF OLD.status IS NOT DISTINCT FROM NEW.status THEN
            RETURN NEW;
        END IF;
        v_from := OLD.status;
    END IF;

    v_actor := COALESCE(NEW.reviewed_by, NEW.applicant_id);

    INSERT INTO application_status_history
        (application_id, changed_by, from_status, to_status, note, created_at, updated_at)
    VALUES
        (NEW.id, v_actor, v_from, NEW.status, NEW.admin_note, now(), now());

    INSERT INTO activity_logs
        (user_id, category, action, entity_type, entity_id, metadata, created_at)
    VALUES
        (v_actor, 'application',
         CASE WHEN TG_OP = 'INSERT' THEN 'application_created' ELSE 'application_status_changed' END,
         'applications', NEW.id,
         jsonb_build_object('from', v_from, 'to', NEW.status),
         now());

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_application_status_history
AFTER INSERT OR UPDATE OF status ON applications
FOR EACH ROW EXECUTE FUNCTION fn_log_application_status();
SQL);

        // ---- Trigger 2: log perubahan role user (aspek keamanan / otorisasi) ---------
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_log_role_change() RETURNS trigger AS $$
BEGIN
    IF OLD.role IS DISTINCT FROM NEW.role THEN
        INSERT INTO activity_logs
            (user_id, category, action, entity_type, entity_id, metadata, created_at)
        VALUES
            (NEW.id, 'security', 'role_changed', 'users', NEW.id,
             jsonb_build_object('from', OLD.role, 'to', NEW.role), now());
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_user_role_change
AFTER UPDATE OF role ON users
FOR EACH ROW EXECUTE FUNCTION fn_log_role_change();
SQL);

        // ---- Stored function: terima lamaran dalam 1 transaksi -----------------------
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_accept_application(
    p_application_id bigint,
    p_admin_id bigint,
    p_note text DEFAULT NULL
) RETURNS void AS $$
DECLARE
    v_status text;
    v_type   text;
BEGIN
    PERFORM 1 FROM users
    WHERE id = p_admin_id AND role IN ('admin', 'operator') AND status = 'active';
    IF NOT FOUND THEN
        RAISE EXCEPTION 'User % tidak berhak menerima lamaran', p_admin_id;
    END IF;

    SELECT a.status, j.type
      INTO v_status, v_type
      FROM applications a
      JOIN job_openings j ON j.id = a.job_opening_id
     WHERE a.id = p_application_id
       FOR UPDATE OF a;                       -- kunci baris: cegah dua admin memproses bersamaan

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Lamaran % tidak ditemukan', p_application_id;
    END IF;

    IF v_status NOT IN ('submitted', 'in_review', 'interview') THEN
        RAISE EXCEPTION 'Lamaran % berstatus % dan tidak bisa diterima', p_application_id, v_status;
    END IF;

    UPDATE applications
       SET status      = 'accepted',
           reviewed_by = p_admin_id,
           reviewed_at = now(),
           admin_note  = COALESCE(p_note, admin_note),
           updated_at  = now()
     WHERE id = p_application_id;             -- trigger otomatis mencatat riwayat & log

    IF v_type = 'magang' THEN
        INSERT INTO intern_placements (application_id, status, created_at, updated_at)
        VALUES (p_application_id, 'active', now(), now())
        ON CONFLICT (application_id) DO NOTHING;
    END IF;
END;
$$ LANGUAGE plpgsql;
SQL);

        // ---- View 1: sebaran status lamaran per tipe ---------------------------------
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_application_funnel AS
SELECT j.type, a.status, COUNT(*) AS total
FROM applications a
JOIN job_openings j ON j.id = a.job_opening_id
GROUP BY j.type, a.status;
SQL);

        // ---- View 2: LEFT JOIN (lowongan tanpa pelamar tetap tampil) + window RANK ---
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_job_applicant_counts AS
SELECT j.id AS job_opening_id,
       j.title,
       j.type,
       j.is_active,
       COUNT(a.id)                                    AS total_applicants,
       COUNT(a.id) FILTER (WHERE a.status = 'accepted') AS accepted,
       RANK() OVER (PARTITION BY j.type ORDER BY COUNT(a.id) DESC) AS rank_in_type
FROM job_openings j
LEFT JOIN applications a
       ON a.job_opening_id = j.id AND a.status <> 'draft'
GROUP BY j.id, j.title, j.type, j.is_active;
SQL);

        // ---- Materialized view: tren bulanan (CTE + window LAG) ----------------------
        DB::unprepared(<<<'SQL'
CREATE MATERIALIZED VIEW mv_monthly_applications AS
WITH monthly AS (
    SELECT date_trunc('month', a.submitted_at)::date AS month,
           j.type,
           COUNT(*) AS total,
           COUNT(*) FILTER (WHERE a.status = 'accepted') AS accepted
    FROM applications a
    JOIN job_openings j ON j.id = a.job_opening_id
    WHERE a.submitted_at IS NOT NULL
    GROUP BY 1, 2
),
with_prev AS (
    SELECT m.*, LAG(total) OVER (PARTITION BY type ORDER BY month) AS prev_total
    FROM monthly m
)
SELECT month, type, total, accepted, prev_total,
       ROUND(100.0 * (total - prev_total) / NULLIF(prev_total, 0), 1) AS growth_pct
FROM with_prev;
SQL);

        DB::unprepared('CREATE UNIQUE INDEX mv_monthly_applications_uq ON mv_monthly_applications (month, type);');
    }

    public function down(): void
    {
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS mv_monthly_applications;');
        DB::unprepared('DROP VIEW IF EXISTS v_job_applicant_counts;');
        DB::unprepared('DROP VIEW IF EXISTS v_application_funnel;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_accept_application(bigint, bigint, text);');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_user_role_change ON users;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_log_role_change();');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_application_status_history ON applications;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_log_application_status();');
    }
};
