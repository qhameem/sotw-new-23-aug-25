<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CodeSnippet;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CodeSnippetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('admin.advertising.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'location' => 'required|string|in:head,body,sidebar',
            'code' => 'required|string',
            'excluded_ips' => 'nullable|string',
            'excluded_countries' => 'nullable|array',
            'excluded_countries.*' => 'string|size:2',
        ]);

        CodeSnippet::create($this->preparePayload($validated));

        return redirect()->route('admin.advertising.index')
            ->with('success', 'Code snippet created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'location' => 'required|string|in:head,body,sidebar',
            'code' => 'required|string',
            'excluded_ips' => 'nullable|string',
            'excluded_countries' => 'nullable|array',
            'excluded_countries.*' => 'string|size:2',
        ]);

        $snippet = CodeSnippet::findOrFail($id);
        $snippet->update($this->preparePayload($validated));

        return redirect()->route('admin.advertising.index')
            ->with('success', 'Code snippet updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CodeSnippet $codeSnippet)
    {
        $codeSnippet->delete();

        return redirect()->route('admin.advertising.index')
            ->with('success', 'Code snippet deleted successfully.');
    }

    protected function preparePayload(array $validated): array
    {
        $validated['excluded_ips'] = $this->normalizeExcludedIps($validated['excluded_ips'] ?? null);
        $validated['excluded_countries'] = $this->normalizeExcludedCountries($validated['excluded_countries'] ?? []);

        return $validated;
    }

    protected function normalizeExcludedIps(?string $rawIps): array
    {
        if ($rawIps === null || trim($rawIps) === '') {
            return [];
        }

        $ips = preg_split('/[\s,]+/', $rawIps, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $normalized = [];
        $invalidIps = [];

        foreach ($ips as $ip) {
            $candidate = trim($ip);

            if (! filter_var($candidate, FILTER_VALIDATE_IP)) {
                $invalidIps[] = $candidate;
                continue;
            }

            $normalized[] = $candidate;
        }

        if ($invalidIps !== []) {
            throw ValidationException::withMessages([
                'excluded_ips' => 'Each excluded IP must be a valid IPv4 or IPv6 address.',
            ]);
        }

        return array_values(array_unique($normalized));
    }

    protected function normalizeExcludedCountries(array $countries): array
    {
        $normalized = array_map(
            static fn (string $country): string => strtoupper(trim($country)),
            $countries
        );

        $normalized = array_filter(
            $normalized,
            static fn (string $country): bool => preg_match('/^[A-Z]{2}$/', $country) === 1
        );

        return array_values(array_unique($normalized));
    }
}
