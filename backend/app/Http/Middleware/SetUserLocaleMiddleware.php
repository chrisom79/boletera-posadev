<?php

namespace HiEvents\Http\Middleware;

use Closure;
use HiEvents\DomainObjects\UserDomainObject;
use HiEvents\Services\Application\Locale\LocaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetUserLocaleMiddleware
{
    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->setLocale($request);
        App::setFallbackLocale(config('app.locale'));

        return $next($request);
    }

    protected function setLocale(Request $request): void
    {
        if ($this->setLocaleFromCookie($request)) {
            return;
        }

        // Falling back to Accept-Language here used to also silently override
        // config('app.locale') for the rest of the request (App::setLocale()
        // writes back into the config repository), which defeated every
        // "always use config('app.locale')" fix elsewhere (e.g. order/waitlist
        // locale) for any real browser, since browsers always send this
        // header. This deployment forces a single language for everyone
        // unless they explicitly picked one (cookie) or have an account
        // preference (below), so no Accept-Language-based fallback here.
        $this->setLocaleFromUser();
    }

    protected function setLocaleFromCookie(Request $request): bool
    {
        if ($locale = $request->cookie('locale')) {
            App::setLocale($this->localeService->getLocaleOrDefault($locale));
            return true;
        }

        return false;
    }

    protected function setLocaleFromUser(): bool
    {
        if (Auth::check()) {
            /** @var UserDomainObject $user */
            $user = UserDomainObject::hydrateFromModel(Auth::user());
            App::setLocale($user->getLocale());
            return true;
        }

        return false;
    }
}
