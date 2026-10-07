<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\LlmsTxt;
use App\Support\Robots;
use App\Support\Sitemap;
use Illuminate\Http\Response;

/** Files for crawlers: robots.txt, the XML sitemaps and llms.txt (Admin → Settings → SEO & sharing). */
class SiteFilesController extends Controller
{
    public function robots(Robots $robots): Response
    {
        return $this->text($robots->text());
    }

    public function sitemap(Sitemap $sitemap): Response
    {
        return $this->xml($sitemap->index());
    }

    public function sitemapFile(Sitemap $sitemap, string $name): Response
    {
        return $this->xml($sitemap->file($name) ?? abort(404));
    }

    public function llms(LlmsTxt $llms): Response
    {
        abort_unless($llms->enabled(), 404);

        return $this->text($llms->text());
    }

    protected function text(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    protected function xml(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
