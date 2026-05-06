@php
    $customFaviconPath = config('theme.favicon_url');
    $customFaviconDirectory = $customFaviconPath ? dirname($customFaviconPath) : null;
    $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');

    $versionedStorageUrl = function (string $path) use ($publicDisk) {
        return \Illuminate\Support\Facades\Storage::url($path) . '?v=' . $publicDisk->lastModified($path);
    };

    $hasCustomFavicon = $customFaviconPath && $publicDisk->exists($customFaviconPath);

    $mainFaviconUrl = $hasCustomFavicon
        ? $versionedStorageUrl($customFaviconPath)
        : asset('favicon/favicon.ico');

    $resolveGeneratedFaviconUrl = function (string $filename) use ($customFaviconDirectory, $publicDisk, $versionedStorageUrl) {
        if (!$customFaviconDirectory) {
            return null;
        }

        $candidatePath = $customFaviconDirectory . '/' . $filename;

        return $publicDisk->exists($candidatePath)
            ? $versionedStorageUrl($candidatePath)
            : null;
    };

    $appleTouchIconUrl = $resolveGeneratedFaviconUrl('apple-touch-icon.png')
        ?? ($hasCustomFavicon ? $mainFaviconUrl : asset('favicon/apple-touch-icon.png'));
    $favicon32Url = $resolveGeneratedFaviconUrl('favicon-32x32.png')
        ?? ($hasCustomFavicon ? $mainFaviconUrl : asset('favicon/favicon-32x32.png'));
    $favicon16Url = $resolveGeneratedFaviconUrl('favicon-16x16.png')
        ?? ($hasCustomFavicon ? $mainFaviconUrl : asset('favicon/favicon-16x16.png'));
    $customManifestPath = config('theme.favicon_manifest_url');
    $manifestUrl = $customManifestPath && $publicDisk->exists($customManifestPath)
        ? $versionedStorageUrl($customManifestPath)
        : route('site.manifest');
@endphp

<link rel="icon" href="{{ $mainFaviconUrl }}">
<link rel="shortcut icon" href="{{ $mainFaviconUrl }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $appleTouchIconUrl }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon32Url }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon16Url }}">
<link rel="manifest" href="{{ $manifestUrl }}">
