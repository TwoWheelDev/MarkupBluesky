<?php

namespace ProcessWire;

function fetchPosts(string $handle, int $limit, ?bool $includeReposts = false): array
{
    /** @var WireHTTP $client */
    $client = new WireHttp();

    $client->setData([
        'actor' => $handle,
        'limit' => $limit,
        'filter' => 'posts_no_replies',
    ]);

    $response = $client->getJSON("https://public.api.bsky.app/xrpc/app.bsky.feed.getAuthorFeed");

    if ($client->getHttpCode() === 200) {
        return processPosts($response['feed'] ?? [], $includeReposts);
    } else {
        WireLog()->save('bluesky', "Failed to fetch feed: " . $response['message']);
        return [];
    }
}

function processPosts(array $feed, ?bool $includeReposts = false): array
{
    $posts = [];

    foreach ($feed as $item) {
        // Filter out reposts if setting is disabled
        if (!$includeReposts && !empty($item['reason']) && $item['reason']['$type'] === 'app.bsky.feed.defs#reasonRepost') {
            continue;
        }

        $post = $item['post'];
        $media = null;

        if (!empty($post['embed'])) {
            $embed = $post['embed'];

            switch ($embed['$type']) {
                case 'app.bsky.embed.images#view':
                    $media = [
                        'type' => 'images',
                        'images' => $embed['images']
                    ];
                    break;

                case 'app.bsky.embed.video#view':
                    $media = [
                        'type' => 'video',
                        'uri' => $embed['playlist']
                    ];
                    break;

                case 'app.bsky.embed.external':
                    $media = [
                        'type' => 'external',
                        'uri' => $embed['external']['uri'],
                        'title' => $embed['external']['title'],
                        'description' => $embed['external']['description'],
                        'thumb' => $embed['external']['thumb'],
                    ];
                    break;
            }
        }

        $uriParts = explode('/', $post['uri']);
        $rkey = end($uriParts);

        $posts[] = [
            'text' => $post['record']['text'] ?? '',
            'createdAt' => $post['record']['createdAt'] ?? '',
            'uri' => $post['uri'],
            'url' => "https://bsky.app/profile/{$post['author']['handle']}/post/{$rkey}",
            'media' => $media,
            'author' => [
                'handle' => $post['author']['handle'],
                'displayName' => $post['author']['displayName'],
                'avatar' => $post['author']['avatar'],
            ]
        ];
    }

    return $posts;
}
