<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Support\Landing\BlogPostCatalog;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const BASE_URL = 'https://fiscaldock.com';
    private const CACHE_TTL = 3600;

    public function __invoke()
    {
        $xml = Cache::remember('sitemap_xml', self::CACHE_TTL, fn () => $this->buildXml());

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function buildXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->urls() as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . self::BASE_URL . $url['loc'] . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function urls(): array
    {
        $posts = BlogPostCatalog::all();
        $blogIndexLastmod = $this->blogIndexLastmod($posts);

        $urls = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $this->lastmodFor('views/landing_page/paginas/inicio.blade.php')],
            ['loc' => '/solucoes', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $this->lastmodFor('views/landing_page/solucoes/index.blade.php')],
            ['loc' => '/precos', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $this->lastmodFor('views/landing_page/paginas/precos.blade.php')],
            ['loc' => '/duvidas', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $this->lastmodFor('views/landing_page/paginas/duvidas.blade.php')],
            ['loc' => '/criar-conta', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $this->lastmodFor('views/landing_page/auth/criar-conta.blade.php')],
            ['loc' => '/termos', 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $this->lastmodFor('views/landing_page/paginas/termos.blade.php')],
            ['loc' => '/privacidade', 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $this->lastmodFor('views/landing_page/paginas/privacidade.blade.php')],
            ['loc' => '/blog', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $blogIndexLastmod],
            ['loc' => '/blog/efd', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $blogIndexLastmod],
        ];

        foreach (BlogPostCatalog::topics() as $topic) {
            if ($topic['slug'] === 'efd') {
                continue;
            }
            $urls[] = [
                'loc' => '/blog/tema/' . $topic['slug'],
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $blogIndexLastmod,
            ];
        }

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => '/blog/' . $post['slug'],
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $post['data'],
            ];
        }

        return $urls;
    }

    private function lastmodFor(string $viewPath): string
    {
        $fullPath = resource_path($viewPath);
        $mtime = @filemtime($fullPath);

        return date('Y-m-d', $mtime ?: time());
    }

    private function blogIndexLastmod(array $posts): string
    {
        $latest = null;
        foreach ($posts as $post) {
            if ($latest === null || $post['data'] > $latest) {
                $latest = $post['data'];
            }
        }

        return $latest ?? date('Y-m-d');
    }
}
