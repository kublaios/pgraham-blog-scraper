<?php
include __DIR__ . '/vendor/autoload.php';
use Goutte\Client;

const DOMAIN = 'http://www.paulgraham.com/';
const FILE_PATH = __DIR__ . '/articles.html';

$client = new Client();
$listCrawler = $client->request('GET', DOMAIN . 'articles.html');
$articleAnchors = $listCrawler
    ->filter('html > body > table > tr > td > table')
    ->reduce(function ($node, $i) {
        return $i == 1;
    })
    ->filter('tr > td > font > a')
    ->reduce(function ($node, $i) {
        return !str_contains($node->attr('href'), 'https://');
    });
$articles = $articleAnchors->each(function ($node) {
    $anchor = str_replace('.html', '', $node->attr('href'));
    return new Article($node->text(), DOMAIN . $node->attr('href'), $anchor);
});

remove_file(FILE_PATH);
write_or_append_to_file(FILE_PATH, '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">');
write_or_append_to_file(FILE_PATH, '<title>Paul Graham\'s Articles</title>');

write_or_append_to_file(FILE_PATH, '<h2>Table of Contents</h2>');
write_or_append_to_file(FILE_PATH, '<ul>');
for ($i = 0; $i < count($articles); $i++) {
    $articleCrawler = $client->request('GET', $articles[$i]->url);
    write_or_append_to_file(FILE_PATH, "<li><a href='#{$articles[$i]->anchor}' style='color: inherit; text-decoration: none;'>{$articles[$i]->title}</a></li>");
    $articles[$i]->content = $articleCrawler->filter('font')->first()->html();
}
write_or_append_to_file(FILE_PATH, '</ul>');

foreach ($articles as $article) {
    $article = "<h2><a id='$article->anchor' style='color: inherit; text-decoration: none;'>$article->title</a></h2>$article->content";
    write_or_append_to_file(FILE_PATH, $article);
}

write_or_append_to_file(FILE_PATH, '</body></html>');

/* DUMPYARD */

class Article {
    public $title;
    public $url;
    public $anchor;
    public $content;

    public function __construct($title, $url, $anchor, $content = '') {
        $this->title = $title;
        $this->url = $url;
        $this->anchor = $anchor;
        $this->content = $content;
    }
}

function remove_file($file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

function write_or_append_to_file($file, $content) {
    if (file_exists($file)) {
        file_put_contents($file, $content, FILE_APPEND);
    } else {
        file_put_contents($file, $content);
    }
}
