<?php

require __DIR__.'/../../Bluesky.php';
use ProcessWire\BlueskyAPI;
use ProcessWire\BlueskyMediaExternal;
use ProcessWire\BlueskyMediaFactory;
use ProcessWire\BlueskyMediaImage;
use ProcessWire\BlueskyMediaImages;
use ProcessWire\BlueskyMediaVideo;
use ProcessWire\BlueskyPost;

dataset('embeds', [
    'images' => [
        [
            '$type'  => 'app.bsky.embed.images#view',
            'images' => [
                [
                    'alt'         => 'An image',
                    'fullsize'    => 'https://example.com/full.jpg',
                    'thumb'       => 'https://example.com/thumb.jpg',
                    'aspectRatio' => [
                        'height' => 1080,
                        'width'  => 1920,
                    ],
                ],
            ],
        ],
        BlueskyMediaImages::class,
        'images',
    ],
    'video' => [
        [
            '$type'    => 'app.bsky.embed.video#view',
            'playlist' => 'https://example.com/video.m3u8',
        ],
        BlueskyMediaVideo::class,
        'video',
    ],
    'external' => [
        [
            '$type'    => 'app.bsky.embed.external#view',
            'external' => [
                'uri'         => 'https://example.com',
                'title'       => 'Example Title',
                'description' => 'Example Description',
                'thumb'       => 'https://example.com/thumb.jpg',
            ],
        ],
        BlueskyMediaExternal::class,
        'external',
    ],
]);

beforeEach(function () {
    $this->mockHttp = Mockery::mock('ProcessWire\WireHttp');
});

it('fetches a post successfully', function () {
    // @var WireHttp|Mockery\MockInterface $mockHttp
    $mockHttp = $this->mockHttp;
    $mockHttp->shouldReceive('getJSON')
        ->once()
        ->andReturn([
            'posts' => [
                [
                    'uri'    => 'at://user/post',
                    'author' => ['handle' => 'testuser'],
                    'record' => [
                        'text'      => 'Hello World!',
                        'createdAt' => '2025-04-27T10:00:00Z',
                    ],
                    'replyCount'  => 0,
                    'repostCount' => 0,
                    'likeCount'   => 0,
                ],
            ],
        ]);

    $mockHttp->shouldReceive('getHttpCode')
        ->once()
        ->andReturn(200);

    $api = new BlueskyAPI($mockHttp);
    $post = $api->fetchPost('at://user/post');

    expect($post)->toBeInstanceOf(BlueskyPost::class)
        ->and($post->text)->toBe('Hello World!');
});

it('fetches a feed successfully', function () {
    // @var WireHttp|Mockery\MockInterface $mockHttp
    $mockHttp = $this->mockHttp;
    $mockHttp->shouldReceive('getJSON')
        ->once()
        ->andReturn([
            'feed' => [
                [
                    'post' => [
                        'uri'    => 'at://user/post',
                        'author' => ['handle' => 'testuser'],
                        'record' => [
                            'text'      => 'Hello World!',
                            'createdAt' => '2025-04-27T10:00:00Z',
                        ],
                        'replyCount'  => 0,
                        'repostCount' => 0,
                        'likeCount'   => 0,
                    ],
                ],
            ],
        ]);

    $mockHttp->shouldReceive('setData')
        ->once();

    $mockHttp->shouldReceive('getHttpCode')
        ->once()
        ->andReturn(200);

    $api = new BlueskyAPI($mockHttp);
    $posts = $api->fetchFeed('testuser', 5);

    expect($posts)->toHaveCount(1)
        ->and($posts[0])->toBeInstanceOf(BlueskyPost::class)
        ->and($posts[0]->text)->toBe('Hello World!');
});

it('can create a BlueskyPost from API data', function () {
    $postData = [
        'author' => [
            'handle' => 'example.bsky.social',
        ],
        'record' => [
            'createdAt' => '2025-04-25T12:00:00Z',
            'text'      => 'Hello world!',
        ],
        'uri'         => 'at://did:plc:xyz123/app.bsky.feed.post/abc456',
        'embed'       => null,
        'replyCount'  => 2,
        'repostCount' => 3,
        'likeCount'   => 5,
    ];

    $post = new BlueskyPost($postData);

    expect($post->text)->toBe('Hello world!')
        ->and($post->author['handle'])->toBe('example.bsky.social')
        ->and($post->url)->toBe('https://bsky.app/profile/example.bsky.social/post/abc456')
        ->and($post->media)->toBe(null);
});

it('can create the correct BlueskyMedia object from embed data', function (array $embedData, string $expectedClass, string $expectedType) {
    $media = BlueskyMediaFactory::create($embedData);

    expect($media)
        ->toBeInstanceOf($expectedClass)
        ->and($media->type)->toBe($expectedType);

    if ($media instanceof BlueskyMediaImages) {
        expect($media->images)->toHaveCount(1)
            ->and($media->images[0])->toBeInstanceOf(BlueskyMediaImage::class)
            ->and($media->images[0]->alt)->toBe($embedData['images'][0]['alt'])
            ->and($media->images[0]->fullsize)->toBe($embedData['images'][0]['fullsize'])
            ->and($media->images[0]->thumb)->toBe($embedData['images'][0]['thumb']);
    }

    if ($media instanceof BlueskyMediaVideo) {
        expect($media->uri)->toBe($embedData['playlist']);
    }

    if ($media instanceof BlueskyMediaExternal) {
        expect($media->uri)->toBe($embedData['external']['uri'])
            ->and($media->title)->toBe($embedData['external']['title'])
            ->and($media->description)->toBe($embedData['external']['description'])
            ->and($media->thumb)->toBe($embedData['external']['thumb']);
    }
})->with('embeds');
