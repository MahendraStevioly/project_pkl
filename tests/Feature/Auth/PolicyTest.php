<?php

namespace Tests\Feature\Auth;

use App\Models\Application;
use App\Models\InternPlacement;
use App\Models\IssuedDocument;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Models\PostgresFeatureTestCase;

class PolicyTest extends PostgresFeatureTestCase
{
    public function test_application_policy_allows_owners_and_admin_roles_only_for_other_applications(): void
    {
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'magang'])->save();
        $otherApplicant = User::factory()->create();
        $otherApplicant->forceFill(['role' => 'karyawan'])->save();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $operator = User::factory()->create();
        $operator->forceFill(['role' => 'operator'])->save();
        $jobOpening = JobOpening::create([
            'title' => 'Policy test',
            'type' => 'magang',
            'description' => 'Policy test opening',
        ]);
        $application = Application::create([
            'applicant_id' => $owner->id,
            'job_opening_id' => $jobOpening->id,
            'status' => 'submitted',
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $application));
        $this->assertFalse(Gate::forUser($otherApplicant)->allows('view', $application));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $application));
        $this->assertTrue(Gate::forUser($operator)->allows('view', $application));
    }

    public function test_issued_document_policy_limits_interns_to_their_own_documents(): void
    {
        $owner = User::factory()->create();
        $owner->forceFill(['role' => 'magang'])->save();
        $otherIntern = User::factory()->create();
        $otherIntern->forceFill(['role' => 'magang'])->save();
        $employee = User::factory()->create();
        $employee->forceFill(['role' => 'karyawan'])->save();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $operator = User::factory()->create();
        $operator->forceFill(['role' => 'operator'])->save();
        $jobOpening = JobOpening::create([
            'title' => 'Placement policy test',
            'type' => 'magang',
            'description' => 'Policy test opening',
        ]);
        $application = Application::create([
            'applicant_id' => $owner->id,
            'job_opening_id' => $jobOpening->id,
            'status' => 'accepted',
        ]);
        $placement = InternPlacement::create([
            'application_id' => $application->id,
        ]);
        $issuedDocument = IssuedDocument::create([
            'placement_id' => $placement->id,
            'type' => 'loa',
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $issuedDocument));
        $this->assertFalse(Gate::forUser($otherIntern)->allows('view', $issuedDocument));
        $this->assertFalse(Gate::forUser($employee)->allows('view', $issuedDocument));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $issuedDocument));
        $this->assertTrue(Gate::forUser($operator)->allows('view', $issuedDocument));
    }
}
