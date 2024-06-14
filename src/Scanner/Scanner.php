<?php

namespace RifatH\SiteMapScanner\Scanner;

use DOMXPath;
use DOMDocument;
use Illuminate\Support\Facades\Http;

class Scanner
{

    private string $url;
    private int $max_depth;
    private $alreadyFetched = [];


    public function __construct($url, $max_depth = 6)
    {
        $this->url = $url;
        $this->max_depth = $max_depth;
    }

    private function urljoin($base, $relative)
    {
        $base = rtrim($base, '/');

        if (parse_url($relative, PHP_URL_SCHEME) != '') {
            return $relative;
        }

        if ($relative[0] == '#' || $relative[0] == '?') {
            return $base . $relative;
        }

        $base_parts = parse_url($base);
        $path = $base_parts['path'] ?? '';
        $path = preg_replace('#/[^/]*$#', '', $path);

        if ($relative[0] == '/') {
            $path = '';
        }

        $abs = "$base_parts[scheme]://$base_parts[host]$path/$relative";
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
        }

        return $abs;
    }

    private function fetch_html_content($url)
    {
        try {
            // Make the GET request using Laravel's HTTP client
            $response = Http::get($url);

            // Check if the request was successful
            if ($response->successful()) {
                // Get the HTML content from the response
                $html = $response->body();
                return $html;
            }
        } catch (\Exception $e) {
            //  return $this->fetch_html_content($url);
        }
    }

    private function crawl_internal_links($url, $base_url, &$internal_links, $depth = 0)
    {
        if ($depth > $this->max_depth || isset($this->alreadyFetched[$url])) {
            return;
        }

        $this->alreadyFetched[$url] = true;

        $html = $this->fetch_html_content($url);

        if (!$html) {
            return;
        }

        if ($html !== false) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $links = $xpath->query("//a/@href");
            foreach ($links as $link) {
                $absolute_url = $this->urljoin($base_url, $link->nodeValue);
                if (parse_url($absolute_url, PHP_URL_HOST) === parse_url($base_url, PHP_URL_HOST)) {
                    if (!in_array($absolute_url, $internal_links)) {
                        $internal_links[] = $absolute_url;
                        $this->crawl_internal_links($absolute_url, $base_url, $internal_links, $depth + 1, $this->max_depth);
                    }
                }
            }
        } else {
            echo "Failed to fetch the website content from $url.";
        }
    }

    private function get_internal_links($url, $base_url)
    {
        $internal_links = [];
        $this->crawl_internal_links($url, $base_url, $internal_links);
        return $internal_links;
    }

    public function scan()
    {
        $url = $this->url;
        $internal_links = $this->get_internal_links($url, $url);

        if (empty($internal_links)) {
            return;
        }

        // Filter out duplicate links
        $internal_links = array_unique($internal_links);

        // Create the XML sitemap
        $xml = new DOMDocument('1.0', 'UTF-8');
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        $xml->appendChild($urlset);

        foreach ($internal_links as $link) {
            if (strpos($link, "#") !== false) {
                continue;
            }

            $count = substr_count($link, "/");
            $priority = 1 - (($count - 2) * 0.10);
            $priority = number_format((float)$priority, 2, '.', '');

            $url_element = $xml->createElement('url');
            $loc_element = $xml->createElement('loc', $link);
            $url_element->appendChild($loc_element);
            $priority_element = $xml->createElement('priority', $priority);
            $url_element->appendChild($priority_element);
            $urlset->appendChild($url_element);
        }

        // Format the XML output
        $xml->formatOutput = true;
        $xml_content = $xml->saveXML();

        return $xml_content;
    }
}
