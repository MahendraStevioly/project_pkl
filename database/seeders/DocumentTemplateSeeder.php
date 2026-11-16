<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Placeholder: {{nomor_surat}} {{nama}} {{universitas}} {{jurusan}} {{divisi}}
        //              {{tanggal_mulai}} {{tanggal_selesai}} {{tanggal_terbit}}
        $templates = [
            ['loa', 'Letter of Acceptance (LoA) Magang', <<<'HTML'
<div style="font-family: serif; line-height: 1.6;">
  <h2 style="text-align:center;">LETTER OF ACCEPTANCE</h2>
  <p style="text-align:center;">Nomor: {{nomor_surat}}</p>
  <p>Dengan ini PT Equityworld Futures Cabang Surabaya menyatakan bahwa:</p>
  <table>
    <tr><td>Nama</td><td>: {{nama}}</td></tr>
    <tr><td>Universitas</td><td>: {{universitas}}</td></tr>
    <tr><td>Program Studi</td><td>: {{jurusan}}</td></tr>
  </table>
  <p>diterima sebagai peserta magang pada divisi {{divisi}} untuk periode {{tanggal_mulai}} s.d. {{tanggal_selesai}}.</p>
  <p>Surabaya, {{tanggal_terbit}}</p>
  <p style="margin-top:80px;">[Nama & jabatan penandatangan]</p>
</div>
HTML],
            ['sertifikat', 'Sertifikat Magang', <<<'HTML'
<div style="font-family: serif; text-align:center; border: 6px double #F26B1D; padding: 40px;">
  <h1>SERTIFIKAT MAGANG</h1>
  <p>Nomor: {{nomor_surat}}</p>
  <p>Diberikan kepada</p>
  <h2>{{nama}}</h2>
  <p>{{universitas}} - {{jurusan}}</p>
  <p>atas partisipasinya dalam program magang pada divisi {{divisi}}<br>
     periode {{tanggal_mulai}} s.d. {{tanggal_selesai}}.</p>
  <p>Surabaya, {{tanggal_terbit}}</p>
  <p style="margin-top:60px;">[Nama & jabatan penandatangan]</p>
</div>
HTML],
            ['form_penerimaan', 'Form Penerimaan Magang', <<<'HTML'
<div style="font-family: sans-serif; line-height: 1.6;">
  <h2>FORM PENERIMAAN MAGANG</h2>
  <p>Nomor: {{nomor_surat}}</p>
  <table>
    <tr><td>Nama</td><td>: {{nama}}</td></tr>
    <tr><td>Universitas</td><td>: {{universitas}}</td></tr>
    <tr><td>Program Studi</td><td>: {{jurusan}}</td></tr>
    <tr><td>Divisi penempatan</td><td>: {{divisi}}</td></tr>
    <tr><td>Periode</td><td>: {{tanggal_mulai}} s.d. {{tanggal_selesai}}</td></tr>
  </table>
  <p>Tanggal: {{tanggal_terbit}}</p>
</div>
HTML],
        ];

        foreach ($templates as [$type, $name, $html]) {
            DB::table('document_templates')->insert([
                'type' => $type,
                'name' => $name,
                'html_template' => $html,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
