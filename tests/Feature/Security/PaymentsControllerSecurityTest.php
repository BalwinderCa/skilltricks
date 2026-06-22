<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Backend\Payments\PaymentsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PaymentsControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_payment_method_returns_failure(): void
    {
        Session::put('payment_method', 'bogus_gateway');

        $response = (new PaymentsController())->initPayment();

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString(
            'subscriptions',
            $response->headers->get('Location') ?? $response->getTargetUrl()
        );
    }
}
