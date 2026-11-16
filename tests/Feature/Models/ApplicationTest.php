<?php

namespace Tests\Feature\Models;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DocumentTemplate;
use App\Models\InternPlacement;
use App\Models\IssuedDocument;
use App\Models\JobOpening;
use Illuminate\Auth\Access\AuthorizationException;

class ApplicationTest extends PostgresFeatureTestCase
{
    public function test_application_relations_follow_their_foreign_keys(): void
    {
        $applicant = $this->userWithRole('magang');
        $admin = $this->userWithRole('admin');
        $jobOpening = JobOpening::create([
            'created_by' => $admin->id,
            'title' => 'Internship',
            'type' => 'magang',
            'description' => 'Test opening',
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'job_opening_id' => $jobOpening->id,
            'reviewed_by' => $admin->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $document = ApplicationDocument::create([
            'application_id' => $application->id,
            'doc_type' => 'cv',
            'file_path' => 'private/test-cv.pdf',
        ]);
        $placement = InternPlacement::create([
            'application_id' => $application->id,
            'status' => 'active',
        ]);
        $template = DocumentTemplate::create([
            'type' => 'loa',
            'name' => 'Test LoA',
            'html_template' => '<p>{{nama}}</p>',
        ]);
        $issuedDocument = IssuedDocument::create([
            'placement_id' => $placement->id,
            'template_id' => $template->id,
            'issued_by' => $admin->id,
            'type' => 'loa',
            'status' => 'requested',
        ]);

        $this->assertTrue($application->applicant->is($applicant));
        $this->assertTrue($application->jobOpening->is($jobOpening));
        $this->assertTrue($application->reviewer->is($admin));
        $this->assertTrue($document->application->is($application));
        $this->assertTrue($application->documents->first()->is($document));
        $this->assertTrue($application->statusHistory->first()->application->is($application));
        $this->assertTrue($application->internPlacement->is($placement));
        $this->assertTrue($placement->issuedDocuments->first()->is($issuedDocument));
        $this->assertTrue($issuedDocument->template->is($template));
        $this->assertTrue($issuedDocument->placement->is($placement));
        $this->assertTrue($issuedDocument->issuer->is($admin));
        $this->assertTrue($template->issuedDocuments->first()->is($issuedDocument));
    }

    public function test_accepting_magang_application_updates_status_history_and_creates_placement(): void
    {
        $admin = $this->userWithRole('admin');
        $applicant = $this->userWithRole('magang');
        $jobOpening = JobOpening::create([
            'title' => 'Internship',
            'type' => 'magang',
            'description' => 'Test opening',
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'job_opening_id' => $jobOpening->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $application->accept($admin, 'Accepted for internship');
        $application->refresh();
        $history = $application->statusHistory()
            ->where('from_status', 'submitted')
            ->where('to_status', 'accepted')
            ->firstOrFail();
        $placement = $application->internPlacement()->firstOrFail();

        $this->assertSame('accepted', $application->status);
        $this->assertSame((int) $admin->id, (int) $application->reviewed_by);
        $this->assertSame('Accepted for internship', $application->admin_note);
        $this->assertSame((int) $admin->id, (int) $history->changed_by);
        $this->assertSame('Accepted for internship', $history->note);
        $this->assertSame('active', $placement->status);
    }

    public function test_non_admin_user_cannot_accept_an_application(): void
    {
        $applicant = $this->userWithRole('magang');
        $nonAdmin = $this->userWithRole('karyawan');
        $jobOpening = JobOpening::create([
            'title' => 'Internship',
            'type' => 'magang',
            'description' => 'Test opening',
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'job_opening_id' => $jobOpening->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        try {
            $application->accept($nonAdmin);
        } catch (AuthorizationException) {
            $this->assertSame('submitted', $application->fresh()->status);
            $this->assertSame(1, $application->statusHistory()->count());
            $this->assertSame(0, $application->internPlacement()->count());

            return;
        }

        $this->fail('A non-admin user was allowed to accept an application.');
    }
}
