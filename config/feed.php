<?php

return [
    'feeds' => [
        'main' => [
            /*
             * Here you can specify which class and method will return
             * the items that should appear in the feed. For example:
             * [App\Model::class, 'getAllFeedItems']
             *
             * You can also pass an argument to that method. Note that their key must be the name of the parameter:
             * [App\Model::class, 'getAllFeedItems', 'parameterName' => 'argument']
             */
            'items' => [App\Models\BlogPost::class, 'getFeedItems'], // Updated

            /*
             * The feed will be available on this url.
             */
            'url' => '/blog/feed', // Updated to match our route

            'title' => config('app.name', 'Laravel') . ' Blog', // Use app name
            'description' => 'Latest articles from our blog.', // Updated
            'language' => 'en-US',

            /*
             * The image to display for the feed. For Atom feeds, this is displayed as
             * a banner/logo; for RSS and JSON feeds, it's displayed as an icon.
             * An empty value omits the image attribute from the feed.
             */
            'image' => '', // You can set a URL to a logo image here

            /*
             * The format of the feed. Acceptable values are 'rss', 'atom', or 'json'.
             */
            'format' => 'atom', // Atom is a good default

            /*
             * The view that will render the feed.
             */
            'view' => 'feed::atom', // Default view

            /*
             * The mime type to be used in the <link> tag. Set to an empty string to automatically
             * determine the correct value.
             */
            'type' => '', // Auto-detected

            /*
             * The content type for the feed response. Set to an empty string to automatically
             * determine the correct value.
             */
            'contentType' => '', // Auto-detected
        ],
    ],
];
