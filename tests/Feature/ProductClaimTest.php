<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductClaim;
use App\Models\User;
use App\Notifications\ProductClaimApproved;
use App\Notifications\ProductClaimRejected;
use App\Notifications\ProductClaimSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_submit_a_product_claim(): void
    {
        Notification::fake();

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole($adminRole);

        $currentOwner = User::factory()->create(['email' => 'team@example.com']);
        $claimant = User::factory()->create(['email' => 'founder@acme.com']);

        $product = Product::factory()->create([
            'user_id' => $currentOwner->id,
            'link' => 'https://acme.com',
            'approved' => true,
            'is_published' => true,
        ]);

        $response = $this->actingAs($claimant)->post(route('products.claim.store', $product), [
            'proof_type' => 'email_domain',
            'proof_value' => 'Verified company email address',
            'message_to_admin' => 'I am the founder and can manage the listing.',
        ]);

        $response->assertRedirect(route('products.claim.create', $product));

        $this->assertDatabaseHas('product_claims', [
            'product_id' => $product->id,
            'user_id' => $claimant->id,
            'status' => ProductClaim::STATUS_PENDING,
            'proof_type' => 'email_domain',
            'auto_email_domain_match' => true,
        ]);

        Notification::assertSentTo($admin, ProductClaimSubmitted::class);
    }

    public function test_unverified_user_cannot_submit_a_product_claim(): void
    {
        $currentOwner = User::factory()->create();
        $claimant = User::factory()->unverified()->create(['email' => 'founder@acme.com']);

        $product = Product::factory()->create([
            'user_id' => $currentOwner->id,
            'link' => 'https://acme.com',
            'approved' => true,
            'is_published' => true,
        ]);

        $response = $this->actingAs($claimant)->post(route('products.claim.store', $product), [
            'proof_type' => 'email_domain',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('product_claims', 0);
    }

    public function test_admin_can_approve_claim_and_reassign_product(): void
    {
        Notification::fake();

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole($adminRole);

        $currentOwner = User::factory()->create();
        $approvedClaimant = User::factory()->create(['email' => 'owner@acme.com']);
        $otherClaimant = User::factory()->create(['email' => 'another@acme.com']);

        $product = Product::factory()->create([
            'user_id' => $currentOwner->id,
            'link' => 'https://acme.com',
            'approved' => true,
            'is_published' => true,
        ]);

        $approvedClaim = ProductClaim::create([
            'product_id' => $product->id,
            'user_id' => $approvedClaimant->id,
            'status' => ProductClaim::STATUS_PENDING,
            'proof_type' => 'email_domain',
            'auto_email_domain_match' => true,
        ]);

        $otherClaim = ProductClaim::create([
            'product_id' => $product->id,
            'user_id' => $otherClaimant->id,
            'status' => ProductClaim::STATUS_PENDING,
            'proof_type' => 'other',
            'auto_email_domain_match' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.product-claims.approve', $approvedClaim));

        $response->assertRedirect();

        $this->assertSame($approvedClaimant->id, $product->fresh()->user_id);

        $this->assertDatabaseHas('product_claims', [
            'id' => $approvedClaim->id,
            'status' => ProductClaim::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('product_claims', [
            'id' => $otherClaim->id,
            'status' => ProductClaim::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
        ]);

        Notification::assertSentTo($approvedClaimant, ProductClaimApproved::class);
        Notification::assertSentTo($otherClaimant, ProductClaimRejected::class);
    }
}
