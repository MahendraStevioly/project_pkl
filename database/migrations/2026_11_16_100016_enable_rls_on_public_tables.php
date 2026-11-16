<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Supabase membuka schema `public` lewat Data API (PostgREST) memakai anon key.
 * Tabel TANPA RLS bisa dibaca/ditulis lewat API itu. Karena akses data hanya lewat Laravel,
 * RLS diaktifkan tanpa policy => Data API tertutup total.
 *
 * Laravel tetap jalan jika role koneksi memiliki BYPASSRLS/superuser (role `postgres` di Supabase).
 * Jika tidak, migration ini dilewati agar aplikasi tidak tiba-tiba "kosong".
 * Cek manual: select rolbypassrls, rolsuper from pg_roles where rolname = current_user;
 *
 * Catatan: tabel dari migration BARU di masa depan tidak otomatis ter-cover.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $role = DB::selectOne('select rolbypassrls, rolsuper from pg_roles where rolname = current_user');
        if (! $role || ! ($role->rolbypassrls || $role->rolsuper)) {
            return;
        }

        foreach (DB::select("select tablename from pg_tables where schemaname = 'public'") as $row) {
            DB::statement('ALTER TABLE "public"."'.$row->tablename.'" ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (DB::select("select tablename from pg_tables where schemaname = 'public'") as $row) {
            DB::statement('ALTER TABLE "public"."'.$row->tablename.'" DISABLE ROW LEVEL SECURITY');
        }
    }
};
