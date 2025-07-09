<?php

namespace App\Livewire\Admin\Seo;

use Livewire\Component;
use App\Models\PageMetaTag;
use Illuminate\Support\Facades\Log; // Add Log facade
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Routing\Route;

class ManageMetaTags extends Component
{
    public $pages = [];
    public $selectedPath = '';
    public $description = '';
    public $successMessage = '';

    protected $rules = [
        'selectedPath' => 'required|string',
        'description' => 'nullable|string|max:500', // Max length for meta descriptions
    ];

    public function mount()
    {
        $this->loadPages();
    }

    public function loadPages()
    {
        $this->pages = collect(RouteFacade::getRoutes()->getRoutesByName())
            ->filter(function (Route $route) {
                // Filter for GET routes that don't have parameters or are common public pages
                return in_array('GET', $route->methods()) &&
                       (empty($route->parameterNames()) || in_array($route->getName(), [
                           // Add specific dynamic routes if a good display name can be generated
                       ])) &&
                       !str_starts_with($route->getName(), 'ignition.') &&
                       !str_starts_with($route->getName(), 'livewire.') &&
                       !str_starts_with($route->getName(), 'admin.') && // Exclude admin routes for now
                       !str_starts_with($route->getName(), 'sanctum.') &&
                       !str_starts_with($route->getName(), 'api.') && // Exclude api routes
                       !str_starts_with($route->getName(), 'auth.google') &&
                       !str_starts_with($route->getName(), 'logout') &&
                       !str_starts_with($route->getName(), 'password.') &&
                       !str_starts_with($route->getName(), 'verification.');
            })
            ->mapWithKeys(function (Route $route) {
                // Attempt to generate a user-friendly name
                $name = str_replace(['.', '_'], ' ', $route->getName());
                $name = ucwords($name);
                // Use a simple path for now, can be refined
                $path = $route->uri() === '/' ? '/' : '/' . ltrim($route->uri(), '/');
                if (str_contains($path, '{')) { // Skip routes with mandatory parameters for now
                    return [];
                }
                return [$path => $name . ' (' . $path . ')'];
            })
            ->sortKeys()
            ->toArray();
        
        // Manually add key pages that might not be easily discoverable or have specific needs
        $manualPages = [
            '/' => 'Homepage (/)',
            '/about' => 'About Us (/about)',
            '/legal' => 'Legal (/legal)',
            '/promote-your-software' => 'Promote Your Software (/promote-your-software)',
            '/faq' => 'FAQ (/faq)',
            '/topics' => 'Topics (/topics)',
            '/blog' => 'Blog Index (/blog)',
        ];

        // Merge and ensure uniqueness by path
        $this->pages = array_merge($manualPages, $this->pages);
        $this->pages = array_unique($this->pages); // Ensure display names are unique if paths were duplicated
        
        // Ensure paths are unique as keys
        $finalPages = [];
        foreach ($this->pages as $path => $displayName) {
            if (!array_key_exists($path, $finalPages)) {
                $finalPages[$path] = $displayName;
            }
        }
        $this->pages = $finalPages;
        ksort($this->pages);


        if (empty($this->selectedPath) && !empty($this->pages)) {
            $this->selectedPath = array_key_first($this->pages);
            $this->loadDescription();
        }
    }

    public function updatedSelectedPath($value)
    {
        $this->selectedPath = $value;
        $this->loadDescription();
        $this->successMessage = ''; // Clear success message when changing page
    }

    public function loadDescription()
    {
        if ($this->selectedPath) {
            $metaTag = PageMetaTag::where('path', $this->selectedPath)->first();
            $this->description = $metaTag ? $metaTag->description : '';
        } else {
            $this->description = '';
        }
    }

    public function saveDescription()
    {
        $this->validate();

        Log::info('Attempting to save meta tag.', [
            'path' => $this->selectedPath,
            'description' => $this->description
        ]);

        try {
            PageMetaTag::updateOrCreate(
                ['path' => $this->selectedPath],
                ['description' => $this->description]
            );

            $pageName = $this->pages[$this->selectedPath] ?? $this->selectedPath;
            $this->successMessage = "Meta description for '{$pageName}' updated successfully.";
            Log::info('Meta tag saved successfully.', ['path' => $this->selectedPath]);
            // Optionally, refresh description to ensure it shows exactly what was saved (e.g. if there are mutators)
            // $this->loadDescription();
        } catch (\Exception $e) {
            Log::error('Error saving meta tag: ' . $e->getMessage(), [
                'path' => $this->selectedPath,
                'description' => $this->description,
                'exception' => $e
            ]);
            $this->successMessage = ''; // Clear success message
            // Optionally, set an error message to display to the user
            $this->addError('general', 'Could not save meta description. Please try again. Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.seo.manage-meta-tags')
            ->layout('layouts.app'); // Assuming you have an admin layout
    }
}
