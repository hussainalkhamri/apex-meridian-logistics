<?php

use App\Http\Controllers\GuestController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\BookingCarrierController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationFeedController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SystemInsightController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ShippingRateController;
use App\Http\Controllers\ScheduleManagementController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\CustomsClearanceController;
use App\Http\Controllers\DataBackupController;
use App\Http\Controllers\TrackingMapController;
use App\Http\Controllers\TargetController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;

if (! function_exists('isSafeLocalRedirect')) {
    function isSafeLocalRedirect(string $url, Request $request): bool
    {
        if ($url === '' || Str::startsWith($url, ['//', '\\\\'])) {
            return false;
        }

        if (Str::startsWith($url, ['/']) && ! Str::startsWith($url, ['/%2f', '/%5c'])) {
            return true;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return ! Str::contains($url, ':');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return strtolower((string) $parts['host']) === strtolower($request->getHost());
    }
}

// ─── Public / Guest Routes ────────────────────────────────────────────────────
Route::middleware(['web', 'set.locale'])->group(function () {
    Route::get('/', [GuestController::class, 'home'])->name('home');
    Route::post('/instant-booking', [GuestController::class, 'redirectInstantBooking'])->middleware('customer.auth')->name('instant-booking.store');
    Route::get('/tracking', [GuestController::class, 'tracking'])->name('tracking');
    Route::get('/tracking/{id}/map', [TrackingMapController::class, 'show'])->middleware('throttle:30,1')->name('tracking.map');
    Route::get('/schedules', [GuestController::class, 'schedules'])->name('schedules');
    Route::get('/fleet', [GuestController::class, 'fleet'])->name('fleet');
    Route::get('/our-ports', [GuestController::class, 'ports'])->name('public.ports');
    Route::get('/our-agents', [GuestController::class, 'agents'])->name('public.agents');
    Route::get('/contact', [GuestController::class, 'contact'])->name('public.contact');
});

// Language Switch - Available everywhere under web middleware
Route::middleware('web')->group(function () {
    Route::get('/language/{locale}', function (string $locale, Request $request) {
        if (! in_array($locale, ['en', 'ar'], true)) {
            abort(400);
        }

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        Cookie::queue(Cookie::make('locale', $locale, 43200, '/', null, false, false));

        config(['app.locale' => $locale]);
        app()->setLocale($locale);

        $redirectTo = $request->query('redirect_to')
            ?: $request->input('redirect_to')
            ?: $request->header('referer')
            ?: session('_previous.url', null)
            ?: route('home');

        if (is_string($redirectTo)) {
            $redirectTo = preg_replace('/([?&])locale=(?:en|ar)(&?)/', '$1', $redirectTo) ?: $redirectTo;
            $redirectTo = str_replace(['?&', '&&'], ['?', '&'], $redirectTo);
            $redirectTo = rtrim($redirectTo, '?&');
        }

        if (! is_string($redirectTo) || ! isSafeLocalRedirect($redirectTo, $request)) {
            $redirectTo = route('home');
        }

        return redirect()->to($redirectTo ?: route('home'));
    })->middleware('set.locale')->name('language.switch');
});


// Instant Quote (public)
Route::middleware(['web', 'set.locale'])->group(function () {
    Route::middleware('customer.auth')->group(function () {
        Route::get('/quote', [QuoteController::class, 'index'])->name('quote.index');
        Route::post('/quote/calculate', [QuoteController::class, 'calculate'])->name('quote.calculate');
        Route::match(['get', 'post'], '/quote/submit', [QuoteController::class, 'submit'])->name('quote.submit');
    });
    Route::get('/quote/{id}', [QuoteController::class, 'show'])->name('quote.show');
    Route::get('/quote/{id}/draft-mbl', [QuoteController::class, 'draftMbl'])->name('quote.draftMbl');
    Route::get('/bookings/{booking}/attachments/{path}', [BookingController::class, 'downloadAttachment'])
        ->where('path', '.*')
        ->name('bookings.attachments.show');
});

// ─── Authentication ───────────────────────────────────────────────────────────
Route::middleware(['web', 'set.locale'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ─── Customer Portal Authentication ───────────────────────────────────────────
Route::middleware(['web', 'set.locale'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:5,1')->name('register.submit');
    Route::get('/pending-approval', [CustomerAuthController::class, 'pending'])->name('pending');
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

    Route::middleware('customer.auth')->group(function () {
        Route::get('/dashboard', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/shipments', [CustomerPortalController::class, 'shipments'])->name('shipments.index');
        Route::get('/tracking', [CustomerPortalController::class, 'tracking'])->name('tracking');
        Route::get('/tracking/{id}/map', [TrackingMapController::class, 'show'])->middleware('throttle:30,1')->name('tracking.map');
        Route::get('/shipping-rates', [CustomerPortalController::class, 'shippingRates'])->name('shipping-rates');
        Route::get('/shipments/create', [CustomerPortalController::class, 'create'])->name('shipments.create');
        Route::post('/shipments', [CustomerPortalController::class, 'store'])->name('shipments.store');
        Route::get('/order-confirmation', [CustomerPortalController::class, 'orderConfirmation'])->name('order-confirmation');
        Route::get('/offers', [CustomerPortalController::class, 'offers'])->name('offers');
        Route::get('/documents', [CustomerPortalController::class, 'documents'])->name('documents');
        Route::get('/invoices', [CustomerPortalController::class, 'invoices'])->name('invoices');
        Route::get('/customs', [CustomerPortalController::class, 'customs'])->name('customs');
        Route::get('/notifications', [CustomerPortalController::class, 'notifications'])->name('notifications.index');
        Route::get('/notifications/feed', [NotificationFeedController::class, 'customer'])->name('notifications.feed');
        Route::get('/profile', [CustomerPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [CustomerPortalController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [CustomerPortalController::class, 'updatePassword'])->name('profile.password');
        Route::delete('/profile/avatar', [CustomerPortalController::class, 'deleteAvatar'])->name('profile.avatar.delete');
        Route::get('/shipments/{booking}', [CustomerPortalController::class, 'show'])->name('shipments.show');
        Route::post('/shipments/{booking}/accept-quote', [CustomerPortalController::class, 'acceptQuote'])->name('shipments.accept');
        Route::post('/shipments/{booking}/reject-quote', [CustomerPortalController::class, 'rejectQuote'])->name('shipments.reject');
        Route::post('/notifications/read', [CustomerPortalController::class, 'markNotificationsRead'])->name('notifications.read');
    });
});

// ─── Authenticated Routes ─────────────────────────────────────────────────────
Route::middleware(['web', 'auth', 'set.locale', 'auto.insights'])->group(function () {
    Route::get('/orders/{booking}/attachments/{path}', [BookingController::class, 'downloadAttachment'])
        ->where('path', '.*')
        ->name('orders.attachments.show');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:view_dashboard');
    Route::middleware('permission:view_targets')->group(function () {
        Route::get('/targets', [TargetController::class, 'index'])->name('targets.index');
        Route::post('/targets', [TargetController::class, 'update'])->name('targets.update');
    });

    // Booking Requests & Orders (manage_bookings)
    Route::middleware('permission:manage_bookings')->group(function () {
        Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
        Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('/orders', [BookingController::class, 'index'])->name('orders.index');
        Route::post('/orders/{parent}/children', [BookingController::class, 'storeChild'])->name('orders.children.store');
        Route::get('/orders/{id}', [BookingController::class, 'show'])->name('orders.show');
        Route::get('/orders/{id}/edit', [BookingController::class, 'edit'])->name('orders.edit');
        Route::put('/orders/{id}', [BookingController::class, 'update'])->name('orders.update');
        Route::post('/orders/{id}/archive', [BookingController::class, 'archive'])->name('orders.archive');
        Route::post('/orders/{id}/restore', [BookingController::class, 'restore'])->name('orders.restore');
        Route::delete('/orders/{id}', [BookingController::class, 'destroy'])->name('orders.destroy');
        Route::get('/orders/{id}/parent', [BookingController::class, 'showParent'])->name('orders.parent');
    });

    // 1. Pricing Desk (manage_pricing)
    Route::middleware('permission:manage_pricing')->group(function () {
        Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
        Route::post('/pricing/{id}/price', [PricingController::class, 'store'])->name('pricing.store');
    });

    Route::middleware('permission:view_shipping_rates_operations')->group(function () {
        Route::get('/shipping-rates/operations', [ShippingRateController::class, 'operations'])->name('shipping-rates.operations');
    });

    Route::middleware('permission:manage_shipping_rates')->group(function () {
        Route::get('/shipping-rates', [ShippingRateController::class, 'index'])->name('shipping-rates.index');
        Route::get('/shipping-rates/create', [ShippingRateController::class, 'create'])->name('shipping-rates.create');
        Route::post('/shipping-rates', [ShippingRateController::class, 'store'])->name('shipping-rates.store');
        Route::post('/shipping-rates/import', [ShippingRateController::class, 'import'])->name('shipping-rates.import');
        Route::get('/shipping-rates/export', [ShippingRateController::class, 'export'])->name('shipping-rates.export');
        Route::get('/shipping-rates/{shippingRate}', [ShippingRateController::class, 'show'])->name('shipping-rates.show');
        Route::get('/shipping-rates/{shippingRate}/edit', [ShippingRateController::class, 'edit'])->name('shipping-rates.edit');
        Route::put('/shipping-rates/{shippingRate}', [ShippingRateController::class, 'update'])->name('shipping-rates.update');
        Route::get('/shipping-rates/{shippingRate}/copy', [ShippingRateController::class, 'copy'])->name('shipping-rates.copy');
        Route::delete('/shipping-rates/{shippingRate}', [ShippingRateController::class, 'destroy'])->name('shipping-rates.destroy');
    });

    // 2. Sales Desk (manage_sales)
    Route::middleware('permission:manage_sales')->group(function () {
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
        Route::post('/sales/{id}/send', [SalesController::class, 'send'])->name('sales.send');
        Route::post('/sales/{id}/approve', [SalesController::class, 'approve'])->name('sales.approve');
        Route::post('/sales/{id}/reject', [SalesController::class, 'reject'])->name('sales.reject');
    });

    // 3. Carrier Booking (manage_carriers)
    Route::middleware('permission:manage_carriers')->group(function () {
        Route::get('/bookings', [BookingCarrierController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{id}', [BookingCarrierController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{id}/confirm', [BookingCarrierController::class, 'store'])->name('bookings.store');
        Route::get('/schedules/manage', [ScheduleManagementController::class, 'index'])->name('schedules.manage');
        Route::post('/schedules/manage', [ScheduleManagementController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/manage/{schedule}', [ScheduleManagementController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/manage/{schedule}', [ScheduleManagementController::class, 'destroy'])->name('schedules.destroy');
    });

    // Customs Clearance (manage_customs)
    Route::middleware('permission:manage_customs')->group(function () {
    Route::get('/customs-clearance', [CustomsClearanceController::class, 'index'])->name('customs.index');
    Route::post('/customs-clearance/calculate', [CustomsClearanceController::class, 'calculate'])->name('customs.calculate');
    Route::post('/customs-clearance/declaration-customer', [CustomsClearanceController::class, 'storeDeclarationCustomer'])->name('customs.declaration.customer');
    Route::post('/customs-clearance/declaration', [CustomsClearanceController::class, 'storeDeclaration'])->name('customs.declaration.store');
    Route::post('/customs-clearance/declaration-xml', [CustomsClearanceController::class, 'exportDeclarationXml'])->name('customs.declaration.xml');
    Route::post('/customs-clearance/declaration-excel', [CustomsClearanceController::class, 'exportDeclarationExcel'])->name('customs.declaration.excel');
    Route::put('/customs-clearance/{booking}', [CustomsClearanceController::class, 'update'])->name('customs.update');
    });

    // 4. Accounts Ledger (manage_accounts)
    Route::middleware('permission:manage_accounts')->group(function () {
        Route::get('/accounts', [AccountsController::class, 'index'])->name('accounts.index');
        Route::post('/accounts/{id}/status', [AccountsController::class, 'updateStatus'])->name('accounts.updateStatus');
        Route::get('/accounts/{id}/invoice', [AccountsController::class, 'showInvoice'])->name('accounts.invoice');
        Route::get('/accounts/statements/print', [AccountsController::class, 'showStatement'])->name('accounts.statement');
        Route::post('/accounts/invoices', [AccountsController::class, 'storeInvoice'])->name('accounts.storeInvoice');
        Route::delete('/accounts/invoices/{id}', [AccountsController::class, 'destroyInvoice'])->name('accounts.destroyInvoice');
        Route::post('/accounts/vouchers', [AccountsController::class, 'storeVoucher'])->name('accounts.storeVoucher');
        Route::get('/accounts/vouchers/{id}', [AccountsController::class, 'showVoucher'])->name('accounts.showVoucher');
        Route::delete('/accounts/vouchers/{id}', [AccountsController::class, 'destroyVoucher'])->name('accounts.destroyVoucher');
        Route::put('/accounts/vouchers/{id}', [AccountsController::class, 'updateVoucher'])->name('accounts.updateVoucher');
        Route::put('/accounts/invoices/{id}', [AccountsController::class, 'updateInvoice'])->name('accounts.updateInvoice');
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationFeedController::class, 'staff'])
        ->middleware('auth:web')->withoutMiddleware('auto.insights')->name('notifications.feed');
    Route::post('/notifications/page-read', [NotificationController::class, 'markPageRead'])->name('notifications.markPageRead');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.markRead');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    // ─── Ports Management ────────────────────────────────────────────────────
    Route::middleware('permission:manage_ports')->group(function () {
        Route::get('/ports', [PortController::class, 'index'])->name('ports.index');
        Route::post('/ports/countries', [PortController::class, 'storeCountry'])->name('ports.countries.store');
        Route::put('/ports/countries/{country}', [PortController::class, 'updateCountry'])->name('ports.countries.update');
        Route::delete('/ports/countries/{country}', [PortController::class, 'destroyCountry'])->name('ports.countries.destroy');
        Route::post('/ports', [PortController::class, 'store'])->name('ports.store');
        Route::put('/ports/{port}', [PortController::class, 'update'])->name('ports.update');
        Route::delete('/ports/{port}', [PortController::class, 'destroy'])->name('ports.destroy');
    });

    // ─── Agents Management ───────────────────────────────────────────────────
    Route::middleware('permission:manage_agents')->group(function () {
        Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
        Route::get('/agents/orders/export', [AgentController::class, 'exportOrders'])->name('agents.orders.export');
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
        Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
    });

    // AJAX endpoints for forwarding bookings to agents
    Route::get('/ajax/agents', [App\Http\Controllers\BookingAgentController::class, 'agents'])->name('ajax.agents');
    Route::post('/ajax/bookings/send-to-agent', [App\Http\Controllers\BookingAgentController::class, 'sendToAgent'])->name('bookings.sendToAgent');

    // ─── Customers Management ────────────────────────────────────────────────
    Route::middleware('permission:manage_customers')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/customers/{customer}/portal', [CustomerController::class, 'togglePortal'])->name('customers.portal');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    // ─── User Management ──────────────────────────────────────────────────────
    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
    });

    // ─── Roles Management ─────────────────────────────────────────────────────
    Route::middleware('permission:manage_roles')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // ─── Audit Logs ───────────────────────────────────────────────────────────
    Route::middleware('permission:view_audit_logs')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // ─── System Insights ─────────────────────────────────────────────────────
    Route::middleware('permission:manage_insights')->group(function () {
        Route::get('/insights', [SystemInsightController::class, 'index'])->name('insights.index');
        Route::post('/insights/generate', [SystemInsightController::class, 'generate'])->name('insights.generate');
        Route::post('/insights/{insight}/mark-read', [SystemInsightController::class, 'markRead'])->name('insights.mark-read');
        Route::post('/insights/mark-all-read', [SystemInsightController::class, 'markAllRead'])->name('insights.mark-all-read');
        Route::delete('/insights/{insight}', [SystemInsightController::class, 'destroy'])->name('insights.destroy');
    });

    // ─── Data Backups ───────────────────────────────────────────────────────
    Route::middleware('permission:manage_backups')->group(function () {
        Route::get('/backups', [DataBackupController::class, 'index'])->name('backups.index');
        Route::get('/backups/export', [DataBackupController::class, 'export'])->name('backups.export');
        Route::post('/backups/preview', [DataBackupController::class, 'preview'])->name('backups.preview');
        Route::post('/backups/import', [DataBackupController::class, 'import'])->name('backups.import');
    });
});

// ─── Diagnostic Dashboard (Secure PIN / Local Bypass) ─────────────────────────
Route::middleware(['web'])->group(function () {
    Route::get('/diagnose', [DiagnosticController::class, 'index'])->middleware('throttle:20,1')->name('diagnose.index');
    Route::post('/diagnose/fix', [DiagnosticController::class, 'fix'])->middleware('throttle:6,1')->name('diagnose.fix');
});
