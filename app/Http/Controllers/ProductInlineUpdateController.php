<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Category;
use App\Models\TechStack;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductInlineUpdateController extends Controller
{
    public function update(Request $request, Product $product)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !($user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        $field = $request->input('field');
        $value = $request->input('value');

        // Validation based on field
        $rules = [
            'field' => 'required|string|in:name,tagline,product_page_tagline,description,link,x_account,sell_product,asking_price,maker_links,categories,tech_stacks,video_url',
        ];

        switch ($field) {
            case 'name':
            case 'tagline':
            case 'product_page_tagline':
            case 'link':
            case 'x_account':
                $rules['value'] = 'required|string|max:255';
                if ($field === 'link')
                    $rules['value'] .= '|url';
                break;
            case 'description':
                $rules['value'] = 'required|string|max:5000';
                break;
            case 'asking_price':
                $rules['value'] = 'nullable|numeric|min:0|max:99999.99';
                break;
            case 'sell_product':
                $rules['value'] = 'boolean';
                break;
            case 'maker_links':
                $rules['value'] = 'nullable|array';
                $rules['value.*'] = 'url|max:2048';
                break;
            case 'categories':
                $rules['value'] = 'required|array';
                $rules['value.*'] = 'exists:categories,id';
                break;
            case 'tech_stacks':
                $rules['value'] = 'nullable|array';
                $rules['value.*'] = 'exists:tech_stacks,id';
                break;
            case 'video_url':
                $rules['value'] = 'nullable|string|max:2048';
                break;
        }

        $validated = $request->validate($rules);

        // Category validation if updating categories
        if ($field === 'categories') {
            $response = $this->validateCategories($value);
            if ($response)
                return $response;
        }

        if ($field === 'description') {
            $value = $this->addNofollowToLinks($value);
        }

        $updateData = [];
        if ($field === 'categories' || $field === 'tech_stacks' || $field === 'maker_links') {
            $updateData = $value;
        } else {
            $updateData[$field] = $value;
        }

        if ($product->approved) {
            // Approved products store changes as "proposed"
            switch ($field) {
                case 'tagline':
                    $product->proposed_tagline = $value;
                    break;
                case 'description':
                    $product->proposed_description = $value;
                    break;
                case 'categories':
                    $product->proposedCategories()->sync($value);
                    break;
                default:
                    // For now, if it's not one of the specific proposed fields, we just update it
                    // The original ProductController update() only handles tagline/desc/logo/categories as proposed
                    // We'll follow that pattern for now
                    if (in_array($field, ['name', 'product_page_tagline', 'link', 'x_account', 'sell_product', 'asking_price', 'maker_links', 'tech_stacks'])) {
                        $product->update($updateData);
                    }
                    break;
            }
            $product->has_pending_edits = true;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Your proposed edit has been submitted for review.',
                'product' => $product->fresh(['categories', 'techStacks'])
            ]);
        } else {
            // Unapproved products update directly
            if ($field === 'categories') {
                $product->categories()->sync($value);
            } elseif ($field === 'tech_stacks') {
                $product->techStacks()->sync($value);
            } else {
                $product->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'product' => $product->fresh(['categories', 'techStacks'])
            ]);
        }
    }

    protected function validateCategories($selectedIds)
    {
        $selected = collect($selectedIds)->map(fn($id) => (int) $id);

        $pricingType = Type::where('name', 'Pricing')->with('categories')->first();
        $bestForType = Type::where('id', 3)->with('categories')->first();

        $pricingIds = $pricingType ? $pricingType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();

        $softwareTypeIds = Type::whereIn('name', ['Software', 'Category'])->pluck('id');
        $softwareIds = DB::table('category_types')
            ->whereIn('type_id', $softwareTypeIds)
            ->pluck('category_id')
            ->concat(Category::whereDoesntHave('types')->pluck('id'))
            ->unique()
            ->map(fn($id) => (int) $id);
        $bestForIds = $bestForType ? $bestForType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Please select at least one category from the Pricing group.'], 422);
        }
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Please select at least one category from the Software Categories group.'], 422);
        }
        if ($bestForIds->count() && $selected->intersect($bestForIds)->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Please select at least one category from the Best for group.'], 422);
        }

        return null;
    }

    public function updateLogo(Request $request, Product $product)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !($user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
        ]);

        $image = $request->file('logo');
        $extension = $image->getClientOriginalExtension();
        $filename = Str::uuid();
        $path = 'logos/';

        $finalPath = '';
        if ($extension === 'svg') {
            $filenameWithExtension = $filename . '.svg';
            $image->storePubliclyAs($path, $filenameWithExtension, 'public');
            $finalPath = $path . $filenameWithExtension;
        } else {
            // Similar to existing logic
            $image->storePubliclyAs($path, $image->getClientOriginalName(), 'public');
            $finalPath = $path . $image->getClientOriginalName();
        }

        if ($product->approved) {
            if ($product->proposed_logo_path && !Str::startsWith($product->proposed_logo_path, 'http')) {
                Storage::disk('public')->delete($product->proposed_logo_path);
            }
            $product->proposed_logo_path = $finalPath;
            $product->has_pending_edits = true;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Logo update submitted for review.',
                'logo_url' => asset('storage/' . $finalPath)
            ]);
        } else {
            if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                Storage::disk('public')->delete($product->logo);
            }
            $product->logo = $finalPath;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Logo updated successfully.',
                'logo_url' => asset('storage/' . $finalPath)
            ]);
        }
    }

    public function addMedia(Request $request, Product $product)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !($user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,avi|max:10240',
        ]);

        $file = $request->file('media');
        $path = $file->store('product_media', 'public');
        $type = Str::startsWith($file->getMimeType(), 'image') ? 'image' : 'video';

        $media = $product->media()->create([
            'path' => $path,
            'alt_text' => $product->name . ' media',
            'type' => $type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Media added successfully.',
            'media' => $media
        ]);
    }

    public function removeMedia(Product $product, ProductMedia $media)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !($user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        if ($media->product_id !== $product->id) {
            abort(403, 'Unauthorized action.');
        }

        if (!Str::startsWith($media->path, 'http')) {
            Storage::disk('public')->delete($media->path);
        }
        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media removed successfully.'
        ]);
    }

    private function addNofollowToLinks($html)
    {
        if (empty($html)) {
            return $html;
        }

        $dom = new \DOMDocument();
        // Suppress warnings for malformed HTML
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $link->setAttribute('rel', 'nofollow');
        }

        return $dom->saveHTML();
    }
}
