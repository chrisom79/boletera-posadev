<?php

namespace Tests\Unit\Http\Middleware;

use HiEvents\Http\Middleware\SetUserLocaleMiddleware;
use HiEvents\Services\Application\Locale\LocaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SetUserLocaleMiddlewareTest extends TestCase
{
    private SetUserLocaleMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.locale', 'es');
        Auth::shouldReceive('check')->andReturn(false)->byDefault();

        $this->middleware = new SetUserLocaleMiddleware(app(LocaleService::class));
    }

    private function handle(Request $request): void
    {
        $this->middleware->handle($request, fn($req) => $req);
    }

    public function testAcceptLanguageHeaderIsIgnoredWhenNoCookieOrAuthenticatedUser(): void
    {
        $request = Request::create('/api/public/events/1/order', 'POST');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $this->handle($request);

        $this->assertSame('es', app()->getLocale());
        $this->assertSame('es', config('app.locale'));
    }

    public function testExplicitLocaleCookieIsRespectedOverAcceptLanguage(): void
    {
        $request = Request::create('/api/public/events/1/order', 'POST');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');
        $request->cookies->set('locale', 'fr');

        $this->handle($request);

        $this->assertSame('fr', app()->getLocale());
    }

    public function testNoHeadersOrCookiesLeavesDefaultLocaleUntouched(): void
    {
        $request = Request::create('/api/public/events/1/order', 'POST');

        $this->handle($request);

        $this->assertSame('es', app()->getLocale());
    }
}
