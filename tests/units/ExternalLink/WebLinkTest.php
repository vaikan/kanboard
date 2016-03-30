<?php

require_once __DIR__.'/../Base.php';

use Kanboard\ExternalLink\WebLink;

class WebLinkTest extends Base
{
    public function testGetTitleFromHtml()
    {
        $url = 'http://kanboard.net/something';
        $title = 'My title';
        $html = '<!DOCTYPE html><html><head><title>  '.$title.'  </title></head><body>Test</body></html>';

        $webLink = new WebLink($this->container);
        $webLink->setUrl($url);
        $this->assertEquals($url, $webLink->getUrl());

        $this->container['httpClient']
            ->expects($this->once())
            ->method('get')
            ->with($url)
            ->will($this->returnValue($html));

        $this->assertEquals($title, $webLink->getTitle());
    }

    public function testGetTitleFromUrl()
    {
        $url = 'http://kanboard.net/something';
        $html = '<!DOCTYPE html><html><head></head><body>Test</body></html>';

        $webLink = new WebLink($this->container);
        $webLink->setUrl($url);
        $this->assertEquals($url, $webLink->getUrl());

        $this->container['httpClient']
            ->expects($this->once())
            ->method('get')
            ->with($url)
            ->will($this->returnValue($html));

        $this->assertEquals('kanboard.net/something', $webLink->getTitle());
    }
}
