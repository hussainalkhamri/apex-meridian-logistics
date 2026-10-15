<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('created_at', 'desc')->get();
        return view('pages.customers', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCustomerData($request);
        $data['portal_enabled'] = true;

        $customer = Customer::create($data);

        AuditLog::logAction('CREATE', 'customers', (string) $customer->id, [], $customer->toArray());

        return redirect()->route('customers.index')->with('success', 'تم إضافة العميل بنجاح.');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validateCustomerData($request, $customer);

        $old = $customer->only(['company', 'contact_name', 'phone', 'email', 'notes']);
        $customer->update($data);

        AuditLog::logAction('UPDATE', 'customers', (string) $customer->id, $old, $data);

        return redirect()->route('customers.index')->with('success', 'تم تحديث العميل بنجاح.');
    }

    public function togglePortal(Customer $customer)
    {
        $old = $customer->only(['portal_enabled']);
        $customer->forceFill([
            'portal_enabled' => ! $customer->portal_enabled,
        ])->save();

        AuditLog::logAction('UPDATE', 'customers', (string) $customer->id, $old, [
            'portal_enabled' => $customer->portal_enabled,
            'approval_action' => $customer->portal_enabled ? 'approved' : 'disabled',
        ]);

        $message = $customer->portal_enabled
            ? 'تم اعتماد حساب العميل وتفعيل دخوله للبوابة.'
            : 'تم تعطيل دخول العميل للبوابة.';

        return redirect()->route('customers.index')->with('success', $message);
    }

    public function destroy(Customer $customer)
    {
        AuditLog::logAction('DELETE', 'customers', (string) $customer->id, $customer->toArray());
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'تم حذف العميل بنجاح.');
    }

    private function validateCustomerData(Request $request, ?Customer $ignoreCustomer = null): array
    {
        $request->merge([
            'company' => trim((string) $request->input('company')),
            'contact_name' => trim((string) $request->input('contact_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => $this->phoneWithCountryCode(
                $request->input('phone'),
                $request->input('phone_country_code')
            ),
        ]);

        $validator = Validator::make($request->all(), [
            'company' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ], [
            'company.required' => 'اسم الشركة مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
        ]);

        $validator->after(function ($validator) use ($request, $ignoreCustomer) {
            foreach ($this->duplicateCustomerFields($request, $ignoreCustomer) as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });

        $validated = $validator->validate();

        return [
            'company' => trim((string) $validated['company']),
            'contact_name' => blank($validated['contact_name'] ?? null) ? null : trim((string) $validated['contact_name']),
            'phone' => blank($validated['phone'] ?? null) ? null : trim((string) $validated['phone']),
            'email' => blank($validated['email'] ?? null) ? null : Str::lower(trim((string) $validated['email'])),
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function duplicateCustomerFields(Request $request, ?Customer $ignoreCustomer = null): array
    {
        $company = $this->comparableText($request->input('company'));
        $email = $this->comparableEmail($request->input('email'));
        $phone = $this->comparablePhone($request->input('phone'));
        $errors = [];

        if ($company === '' && $email === '' && $phone === '') {
            return $errors;
        }

        Customer::query()
            ->when($ignoreCustomer, fn ($query) => $query->whereKeyNot($ignoreCustomer->getKey()))
            ->select(['id', 'company', 'email', 'phone'])
            ->get()
            ->each(function (Customer $customer) use ($company, $email, $phone, &$errors) {
                if ($company !== '' && ! isset($errors['company']) && $this->comparableText($customer->company) === $company) {
                    $errors['company'] = 'يوجد عميل مسجل بنفس اسم الشركة.';
                }

                if ($email !== '' && ! isset($errors['email']) && $this->comparableEmail($customer->email) === $email) {
                    $errors['email'] = 'يوجد عميل مسجل بنفس البريد الإلكتروني.';
                }

                if ($phone !== '' && ! isset($errors['phone']) && $this->phonesMatch($phone, $this->comparablePhone($customer->phone))) {
                    $errors['phone'] = 'يوجد عميل مسجل بنفس رقم الهاتف.';
                }
            });

        return $errors;
    }

    private function phoneWithCountryCode(?string $phone, ?string $countryCode): ?string
    {
        $phone = trim((string) $phone);
        $countryCode = trim((string) $countryCode);

        if ($phone === '') {
            return null;
        }

        if (Str::startsWith($phone, '+')) {
            return $phone;
        }

        if (Str::startsWith($phone, '00')) {
            return '+' . ltrim(substr($phone, 2), '+');
        }

        if ($countryCode === '') {
            return $phone;
        }

        if (! Str::startsWith($countryCode, '+')) {
            $countryCode = '+' . $countryCode;
        }

        return trim($countryCode . ' ' . ltrim($phone, '0 '));
    }

    private function comparableText(?string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '');
    }

    private function comparableEmail(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    private function comparablePhone(?string $value): string
    {
        $value = strtr((string) $value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]);

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (Str::startsWith($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return ltrim($digits, '0');
    }

    private function phonesMatch(string $phone, string $existingPhone): bool
    {
        if ($existingPhone === '') {
            return false;
        }

        if ($phone === $existingPhone) {
            return true;
        }

        $shorterLength = min(strlen($phone), strlen($existingPhone));

        return $shorterLength >= 7
            && (Str::endsWith($phone, $existingPhone) || Str::endsWith($existingPhone, $phone));
    }
}
