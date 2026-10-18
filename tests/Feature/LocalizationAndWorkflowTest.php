<?php

namespace Tests\Feature;

use App\Helpers\FormattingHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test language switcher persists in user record and session.
     */
    public function test_language_switcher_persists_in_user_profile_and_session(): void
    {
        $user = User::factory()->create([
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Switch to Bangla
        $response = $this->post(route('locale.set'), [
            'locale' => 'bn',
        ]);

        $response->assertSessionHas('locale', 'bn');
        $this->assertEquals('bn', $user->fresh()->locale);

        // Subsequent page visit should render in Bangla
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $this->assertEquals('bn', app()->getLocale());
    }

    /**
     * Test BDT currency formatting and DD-MM-YYYY date formatting.
     */
    public function test_bdt_currency_and_date_formatting(): void
    {
        // Currency formatting
        $formatted = FormattingHelper::currency(1250.50);
        $this->assertEquals('৳ 1,250.50', $formatted);

        $formattedZero = FormattingHelper::currency(0);
        $this->assertEquals('৳ 0.00', $formattedZero);

        // Date formatting DD-MM-YYYY
        $date = Carbon::create(2026, 10, 16, 14, 30, 0);
        $formattedDate = FormattingHelper::date($date);
        $this->assertEquals('16-10-2026', $formattedDate);
    }

    public function test_store_manager_dashboard_renders_dynamically_in_both_english_and_bangla(): void
    {
        $store = \App\Models\Store::create([
            'name' => 'Mirpur Branch',
            'code' => 'MIR01',
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'role' => \App\Enums\UserRole::STORE_MANAGER,
            'store_id' => $store->id,
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->actingAs($manager);

        // 1. Visit in English
        $responseEn = $this->get('/dashboard');
        $responseEn->assertOk();
        $responseEn->assertSee('Store Manager Dashboard');
        $responseEn->assertSee('Current Cash in Drawer');
        $responseEn->assertSee('Today&#039;s Total Sales', false);

        // 2. Switch to Bangla
        $this->post(route('locale.set'), ['locale' => 'bn']);
        $manager->refresh();
        $this->assertEquals('bn', $manager->locale);

        $responseBn = $this->get('/dashboard');
        $responseBn->assertOk();
        $responseBn->assertSee('স্টোর ম্যানেজার ড্যাশবোর্ড');
        $responseBn->assertSee('ক্যাশ বাক্সে বর্তমান টাকা');
        $responseBn->assertSee('আজকের মোট বিক্রি');
    }
}
