<?php

namespace ProcessWire;

/**
 * MarkupBluesky.
 *
 * Returns the status of a Twitch Channel.
 *
 * @author Daniel
 *
 * @version 1.0.0
 *
 * @summary Render a blueky post or feed
 *
 * @href https://github.com/twowheeldev/MarkupBluesky
 */
require_once __DIR__.'/Bluesky.php';

class MarkupBluesky extends WireData implements Module
{
    protected BlueskyAPI $api;

    public function __construct()
    {
        parent::__construct();
        $this->api = new BlueskyAPI();
    }

    public static function getModuleInfo()
    {
        return [
            'title'    => 'Markup Bluesky Posts',
            'version'  => 1,
            'summary'  => 'Allows rendering Bluesky posts and feeds',
            'author'   => 'TwoWheelDev',
            'singular' => true,
            'icon'     => 'plug',
            'installs' => ['FieldtypeBluesky', 'InputfieldBluesky'],
        ];
    }

    public function renderPost(BlueskyPost $post)
    {
        $markup = "<div class='bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-4 shadow-sm space-y-4'>";
        $markup .= "<div class='flex items-center space-x-3 justify-between text-sm text-zinc-600 dark:text-zinc-400'>
						<div class='flex items-center space-x-3'>
							<img
								src='".$post->author['avatar']."'
								alt='".$post->author['displayName']."'s avatar'
								class='w-8 h-8 rounded-full border border-zinc-300 dark:border-zinc-700'
								loading='lazy'>
							<div>
								<a href='https://bsky.app/profile/".$post->author['handle']."' target='_blank' class='text-zinc-900 dark:text-zinc-100 font-medium hover:underline'>
									".$post->author['displayName']."
								</a>
								<div class='text-xs text-zinc-500 dark:text-zinc-400'>
									@".$post->author['handle'].'
									<br />
									'.date('M j, Y · g:i a', strtotime($post->createdAt))."
								</div>
							</div>
						</div>
						<div>
							<a href='".$post->url."' target='_blank'>
								<svg class='w-5 h-5 inline-block text-bluesky' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 568 501'>
									<title>Bluesky butterfly logo</title>
									<path fill='currentColor' d='M123.121 33.664C188.241 82.553 258.281 181.68 284 234.873c25.719-53.192 95.759-152.32 160.879-201.21C491.866-1.611 568-28.906 568 57.947c0 17.346-9.945 145.713-15.778 166.555-20.275 72.453-94.155 90.933-159.875 79.748C507.222 323.8 536.444 388.56 473.333 453.32c-119.86 122.992-172.272-30.859-185.702-70.281-2.462-7.227-3.614-10.608-3.631-7.733-.017-2.875-1.169.506-3.631 7.733-13.43 39.422-65.842 193.273-185.702 70.281-63.111-64.76-33.89-129.52 80.986-149.071-65.72 11.185-139.6-7.295-159.875-79.748C9.945 203.659 0 75.291 0 57.946 0-28.906 76.135-1.612 123.121 33.664Z'></path>
								</svg>
							</a>
						</div>
					</div>

					<!-- Post Text -->
					<p class='text-base text-zinc-900 dark:text-zinc-100 leading-relaxed'>
						".nl2br($this->sanitizer->entities($post->text)).'
					</p>';

        if ($post->media) {
            switch ($post->media->type) {
                case 'images':
                    $markup .= $this->renderImages($post->media, $post->uri);
                    break;
                case 'video':
                    $markup .= $this->renderVideo($post->media);
                    break;
            }
        }

        $markup .= "<div class='flex justify-start gap-5 text-zinc-900 dark:text-zinc-100'>
            <p>
                <i class='fa-solid fa-reply '></i> {$post->replies}
            </p>
            <p>
                <i class='fa-solid fa-repeat '></i> {$post->reposts}
            </p>
            <p>
                <i class='fa-solid fa-heart text-red-500'></i> {$post->likes}
            </p>
        </div>";

        $markup .= '</div>';

        return $markup;
    }

    public function renderImages(BlueskyMediaImages $postMedia, $postUri)
    {
        $markup = "<div class='grid grid-cols-1 sm:grid-cols-2 gap-2'>";

        foreach ($postMedia->images as $img) {
            $markup .= "<a href='{$img->fullsize}' data-fancybox='{$postUri}' data-caption='{$img->alt}'>
                            <img
                                src='{$img->thumb}'
                                alt='{$img->alt}'
                                class='rounded-lg border border-zinc-300 dark:border-zinc-600 object-cover'
                                loading='lazy'>
                        </a>";
        }
        $markup .= '</div>';

        return $markup;
    }

    public function renderVideo(BlueskyMediaVideo $postMedia)
    {
        $uri = $postMedia->uri;
        $videoHash = md5($uri);
        $markup = "<div class='overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-600'>
                        <video
                            id='video-$videoHash'
                            class='w-full'
                            controls
                            preload='metadata'
                        </video>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const video = document.getElementById('video-$videoHash');
                                if (Hls.isSupported()) {
                                    const hls = new Hls();
                                    hls.loadSource('$uri');
                                    hls.attachMedia(video);
                                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                    video.src = '$uri';
                                }
                            });
                        </script>
                    </div>";

        return $markup;
    }

    public function renderFeed($page, $fieldName)
    {
        $data = $page->get($fieldName);
        $posts = $this->api->fetchFeed(ltrim($data->bluesky_handle, '@'), $data->bluesky_post_count, $data->bluesky_include_reposts);

        $out = "<div class='space-y-6'>";

        foreach ($posts as $post) {
            $out .= $this->renderPost($post);
        }

        $out .= '</div>';

        return $out;
    }
}
