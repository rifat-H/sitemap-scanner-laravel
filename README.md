# sitemap-scanner-laravel
laravel sitemap scanner, takes url and returl sitemap xml as string

# Installation
In your laravel project, run ``` composer require rifat-h/site-map-scanner  ```

# Usage
To run the scanner first add ``` use RifatH\SiteMapScanner\Scanner\Scanner; ``` top of your php files.

Then use it like this. 
```
use RifatH\SiteMapScanner\Scanner\Scanner;

public function index()
{

    $Scanner = new Scanner("https://www.mentiongenie.com/", 6); // param1: url of the site, param2: max_depth
    $SiteMapXml = $Scanner->scan();

    dd($SiteMapXml); // This will return the XML of the sitemap as string, just do your thing with it.

}
```

