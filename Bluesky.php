<?php

namespace ProcessWire;
/** @var WireHTTP $client */

class BlueskyAPI
{
    protected WireHttp $client;

    public function __construct(?WireHttp $client = null)
    {
        $this->client = $client ?? new WireHttp();
    }

    /**
     * Fetch a single post from Bluesky
     *
     * @param string $atUri atProto URI
     * @return BlueskyPost Post
     */
    public function fetchPost(string $atUri): BlueskyPost|null
    {
        $response = $this->client->getJSON("https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts", true, ['uris' => $atUri]);

        if ($this->client->getHttpCode() === 200) {
            $post = $response['posts'][0];
            return new BlueskyPost($post);
        } else {
            WireLog()->save('bluesky', "Failed to fetch post: " . $response['message']);
            return null;
        }
    }

    /**
     * Fetch a feed for a given author
     *
     * @param string $handle Authors Bluesky handle
     * @param integer $limit Number of posts to retrieve
     * @param boolean|null $includeReposts Include reposts in response
     * @return array<BlueskyPost> Array of posts
     */
    public function fetchFeed(string $handle, int $limit, ?bool $includeReposts = false): array
    {
        $this->client->setData([
            'actor' => $handle,
            'limit' => $limit,
            'filter' => 'posts_no_replies',
        ]);

        $response = $this->client->getJSON("https://public.api.bsky.app/xrpc/app.bsky.feed.getAuthorFeed");

        if ($this->client->getHttpCode() === 200) {
            return $this->processPosts($response['feed'] ?? [], $includeReposts);
        } else {
            WireLog()->save('bluesky', "Failed to fetch feed: " . $response['message']);
            return [];
        }
    }

    public function resolveHandle(string $handle): string|false
    {
        $response = $this->client->getJSON("https://public.api.bsky.app/xrpc/com.atproto.identity.resolveHandle", true, ['handle' => urlencode($handle)]);
    
        if ($this->client->getHttpCode() === 200) {
            return $response['did'];
        } else {
            WireLog()->save('bluesky', "Failed to resolve the handle: " . $response['message']);
            return false;
        }
    }

    /**
     * Processes a feed of posts and filters out reposts if specified.
     *
     * @param array $feed The array of posts to process.
     * @param bool|null $includeReposts Whether to include reposts in the processed feed. Defaults to false.
     * @return array<BlueskyPost> The processed array of posts.
     */
    protected function processPosts(array $feed, ?bool $includeReposts = false): array {
        /** @var array<BlueskyPost> $posts */
        $posts = [];

        foreach ($feed as $item) {
            // Filter out reposts if setting is disabled
            if (!$includeReposts && !empty($item['reason']) && $item['reason']['$type'] === 'app.bsky.feed.defs#reasonRepost') {
                continue;
            }
            $posts[] = new BlueskyPost($item['post']);
        }

        return $posts;
    }
}


/**
 * Class BlueskyPost
 *
 * Represents a post for the Bluesky module.
 * This class is responsible for handling the data and functionality
 * related to a Bluesky post within the system.
 *
 */
class BlueskyPost {

    public string $text;
    public string $createdAt;
    public string $uri;
    public string $url;
    public BlueskyMedia|null $media;
    public array $author;
    public int $replies;
    public int $reposts;
    public int $likes;

    function __construct(array $post)
    {
        $this->author = $post['author'];
        $this->createdAt = $post['record']['createdAt'];
        $this->media = BlueskyMediaFactory::create($post['embed'] ?? []);
        $this->text = $post['record']['text'];
        $this->uri = $post['uri'];
        $this->url = $this->buildPostUrl();
        $this->replies = $post['replyCount'];
        $this->reposts = $post['repostCount'];
        $this->likes = $post['likeCount'];
    }

    private function buildPostUrl(): string {
        $uriParts = explode('/', $this->uri);
        $rkey = end($uriParts);
        return "https://bsky.app/profile/{$this->author['handle']}/post/{$rkey}";
    }
}

class BlueskyMediaFactory
{
    public static function create(array $embed): BlueskyMedia|null
    {
        if (empty($embed)) {
            return null;
        }

        switch ($embed['$type']) {
            case 'app.bsky.embed.images#view':
                return new BlueskyMediaImages($embed['images']);

            case 'app.bsky.embed.video#view':
                return new BlueskyMediaVideo($embed['playlist']);

            case 'app.bsky.embed.external#view':
                return new BlueskyMediaExternal($embed['external']);

            default:
                return null; // If it's an unknown type
        }
    }
}

abstract class BlueskyMedia
{
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }
}

class BlueskyMediaImage {
    public string $alt;
    public string $fullsize;
    public string $thumb;

    public function __construct(array $data)
    {
        $this->alt = $data['alt'] ?? '';
        $this->fullsize = $data['fullsize'] ?? '';
        $this->thumb = $data['thumb'] ?? '';
    }
}

/**
 * BlueskyMediaImages
 * 
 * @property array<BlueskyMediaImage> $images
 */
class BlueskyMediaImages extends BlueskyMedia
{
    /** @var BlueskyMediaImage[] */
    public array $images;

    public function __construct(array $images)
    {
        parent::__construct('images');
        foreach ($images as $imageData) {
            $this->images[] = new BlueskyMediaImage($imageData);
        }
    }
}

class BlueskyMediaVideo extends BlueskyMedia
{
    public string $uri;

    public function __construct(string $uri)
    {
        parent::__construct('video');
        $this->uri = $uri;
    }
}

class BlueskyMediaExternal extends BlueskyMedia
{
    public string $uri;
    public string $title;
    public string $description;
    public string $thumb;

    public function __construct(array $external)
    {
        parent::__construct('external');
        $this->uri = $external['uri'];
        $this->title = $external['title'];
        $this->description = $external['description'];
        $this->thumb = $external['thumb'];
    }
}