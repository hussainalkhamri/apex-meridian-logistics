<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Port;
use App\Models\PortCountry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Support\PortCatalog;

class PortController extends Controller
{
    public function index(Request $request)
    {
        $selectedCountry = $request->query('country');

        $query = Port::query();

        if ($selectedCountry) {
            $query->where('country', $selectedCountry);
        }

        $ports = $query->orderBy('country')->orderBy('sort_order')->orderBy('name')->get();

        $managedCountries = Schema::hasTable('port_countries')
            ? PortCountry::query()->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $managedCountryOptions = collect($managedCountries)
            ->map(fn (PortCountry $country) => PortCatalog::countryOptionFromManagedCountry($country))
            ->filter()
            ->values();

        $catalogCountries = PortCatalog::countries()
            ->map(fn (array $country) => [
                'name' => $country['name'],
                'name_ar' => $country['name_ar'],
                'code' => $country['code'],
                'flag' => $country['flag'],
            ]);

        $dbCountries = Port::query()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->orderBy('country')
            ->get()
            ->toBase()
            ->map(fn (Port $port) => PortCatalog::countryOptionFromPort($port))
            ->filter()
            ->unique('name')
            ->values();

        $dbCountries = $dbCountries
            ->merge($managedCountryOptions->map(fn (array $country) => [
                'name' => $country['name'],
                'code' => $country['code'],
                'flag' => $country['flag'],
            ]))
            ->unique('name')
            ->sortBy('name')
            ->values();

        $countryOptions = $catalogCountries
            ->merge($dbCountries->map(fn (array $country) => [
                'name' => $country['name'],
                'name_ar' => $country['name'],
                'code' => $country['code'],
                'flag' => $country['flag'],
            ]))
            ->unique('name')
            ->sortBy('name')
            ->values();

        return view('pages.ports', compact('ports', 'dbCountries', 'countryOptions', 'managedCountries', 'selectedCountry'));
    }

    public function storeCountry(Request $request)
    {
        $payload = $this->countryPayloadFromRequest($request);

        if ($this->configuredCountryExists($payload)) {
            return back()
                ->withInput()
                ->with('error', 'هذه الدولة موجودة مسبقاً في كتالوج الدول.');
        }

        $country = PortCountry::create($payload + [
            'sort_order' => PortCountry::count(),
        ]);

        AuditLog::logAction('CREATE', 'port_countries', (string) $country->id, [], $country->toArray());

        return redirect()->route('ports.index')->with('success', 'تمت إضافة الدولة بنجاح');
    }

    public function updateCountry(Request $request, PortCountry $country)
    {
        $payload = $this->countryPayloadFromRequest($request, $country);

        if ($this->configuredCountryExists($payload)) {
            return back()
                ->withInput()
                ->with('error', 'هذه الدولة موجودة مسبقاً في كتالوج الدول.');
        }

        $old = $country->only(['name', 'name_ar', 'code', 'flag', 'is_active']);

        DB::transaction(function () use ($country, $old, $payload) {
            $country->update($payload);

            Port::query()
                ->where(function ($query) use ($old) {
                    $query->where('country', $old['name']);

                    if (! blank($old['code'])) {
                        $query->orWhere('country_code', $old['code']);
                    }
                })
                ->update([
                    'country' => $payload['name'],
                    'country_code' => $payload['code'],
                    'country_flag' => $payload['flag'],
                ]);
        });

        $updatedCountry = $country->fresh();

        AuditLog::logAction(
            'UPDATE',
            'port_countries',
            (string) $country->id,
            $old,
            $updatedCountry ? $updatedCountry->toArray() : $payload
        );

        return redirect()->route('ports.index')->with('success', 'تم تحديث الدولة بنجاح');
    }

    public function destroyCountry(PortCountry $country)
    {
        AuditLog::logAction('DELETE', 'port_countries', (string) $country->id, $country->toArray());
        $country->delete();

        return redirect()->route('ports.index')->with('success', 'تم حذف الدولة من القائمة اليدوية');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:2'],
            'country_flag' => ['nullable', 'string', 'max:16'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $country = PortCatalog::resolveCountry(
            $validated['country'] ?? null,
            $validated['country_code'] ?? null,
            $validated['country_flag'] ?? null
        );

        $port = Port::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'country' => $country['country'],
            'country_code' => $country['country_code'],
            'country_flag' => $country['country_flag'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => Port::count(),
        ]);

        AuditLog::logAction('CREATE', 'ports', (string) $port->id, [], $port->toArray());

        return redirect()->route('ports.index')->with('success', 'تمت إضافة الميناء بنجاح');
    }

    public function update(Request $request, Port $port)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:2'],
            'country_flag' => ['nullable', 'string', 'max:16'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $country = PortCatalog::resolveCountry(
            $validated['country'] ?? null,
            $validated['country_code'] ?? null,
            $validated['country_flag'] ?? null
        );

        $old = $port->only(['name', 'code', 'country', 'country_code', 'country_flag', 'is_active']);

        $port->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'country' => $country['country'],
            'country_code' => $country['country_code'],
            'country_flag' => $country['country_flag'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::logAction('UPDATE', 'ports', (string) $port->id, $old, $port->fresh()->toArray());

        return redirect()->route('ports.index')->with('success', 'تم تحديث الميناء بنجاح');
    }

    public function destroy(Port $port)
    {
        AuditLog::logAction('DELETE', 'ports', (string) $port->id, $port->toArray());
        $port->delete();
        return redirect()->route('ports.index')->with('success', 'تم حذف الميناء بنجاح');
    }

    private function countryPayloadFromRequest(Request $request, ?PortCountry $country = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
            'name' => trim((string) $request->input('name', '')),
            'name_ar' => trim((string) $request->input('name_ar', '')),
            'flag' => trim((string) $request->input('flag', '')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('port_countries', 'name')->ignore($country?->id)],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'size:2', Rule::unique('port_countries', 'code')->ignore($country?->id)],
            'flag' => ['nullable', 'string', 'max:16'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $resolved = PortCatalog::resolveCountry(
            $validated['name'] ?? null,
            $validated['code'] ?? null,
            $validated['flag'] ?? null
        );

        return [
            'name' => $validated['name'],
            'name_ar' => $validated['name_ar'] ?: $validated['name'],
            'code' => ($validated['code'] ?? '') !== '' ? $validated['code'] : $resolved['country_code'],
            'flag' => ($validated['flag'] ?? '') !== '' ? $validated['flag'] : $resolved['country_flag'],
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function configuredCountryExists(array $payload): bool
    {
        return PortCatalog::countries(false)->contains(function (array $country) use ($payload) {
            $code = strtoupper((string) ($payload['code'] ?? ''));
            $name = (string) ($payload['name'] ?? '');
            $nameAr = (string) ($payload['name_ar'] ?? '');

            return ($code !== '' && strtoupper((string) ($country['code'] ?? '')) === $code)
                || strcasecmp((string) ($country['name'] ?? ''), $name) === 0
                || ($nameAr !== '' && (string) ($country['name_ar'] ?? '') === $nameAr);
        });
    }
}
