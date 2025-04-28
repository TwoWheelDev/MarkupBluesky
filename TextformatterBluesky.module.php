<?php

namespace ProcessWire;

require_once 'Bluesky.php';

class TextformatterBluesky extends Textformatter implements Module
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
            'title'    => 'Textformatter Bluesky',
            'version'  => 1,
            'summary'  => 'Formats a Bluesky link to show the post',
            'author'   => 'TwoWheelDev',
            'singular' => true,
        ];
    }

    public function format(&$str)
    {
        $pattern = '#<p.+>https?://bsky\.app/profile/([^/]+)/post/([^/<]+)#i';

        $str = preg_replace_callback($pattern, function ($matches) {
            $handle = $matches[1];   // User handle
            $postId = $matches[2];   // Post ID

            $did = $this->resolveDid($handle);

            if (!$did) {
                // fallback: couldn't resolve, return original URL
                return $matches[0];
            }

            $atUri = "at://$did/app.bsky.feed.post/$postId";
            // Get the post from Bluesky
            $post = $this->api->fetchPost($atUri);
            /** @var MarkupBluesky $bskyMarkup */
            $bskyMarkup = $this->modules->get('MarkupBluesky');
            $markup = "<div class='w-full md:w-1/2 mx-auto not-prose'>";
            $markup .= $bskyMarkup->renderPost($post);
            $markup .= '</div>';

            return $markup;
        }, $str);
    }

    public function formatValue(Page $page, Field $field, &$value)
    {
        $this->format($value);
    }

    protected function resolveDid(string $handle): ?string
    {
        // First, try to get from database
        $query = $this->database->prepare('SELECT did FROM textformatter_bsky_handles WHERE handle = ?');
        $query->execute([$handle]);
        $did = $query->fetchColumn();

        if ($did) {
            return $did;
        }

        // Not found — try resolving via Bluesky Identity API

        $did = $this->api->resolveHandle($handle);

        if ($did) {
            $insert = $this->database->prepare('INSERT INTO textformatter_bsky_handles (handle, did, last_checked) VALUES (?, ?, NOW())');
            $insert->execute([$handle, $did]);

            return $did;
        }

        return null;
    }

    public function ___install()
    {
        // Create the table
        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS textformatter_bsky_handles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            handle VARCHAR(255) UNIQUE NOT NULL,
            did VARCHAR(255) NOT NULL,
            last_checked DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL;

        $this->database->exec($sql);
    }

    public function ___uninstall()
    {
        // Drop the table
        $sql = 'DROP TABLE IF EXISTS textformatter_bsky_handles;';
        $this->database->exec($sql);
    }
}
