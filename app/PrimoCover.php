<?php

namespace App;

use BCLib\PrimoServices\PrimoServices;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PrimoCover extends PrimoSearch
{
    protected $defaultCover;
    protected $http;

    public function __construct(PrimoServices $primo, HttpClient $http, MessageFactory $messageFactory)
    {
        parent::__construct($primo, $http, $messageFactory);
        $this->defaultCover = url('assets/no_cover.jpg');
    }

    protected function coverFromGoogleBooks($isbn)
    {
        $url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query([
                'q' => 'isbn:' . $isbn,
                'country' => 'NO',
            ]);
        $request = $this->messageFactory->createRequest('GET', $url);
        $response = $this->http->sendRequest($request)->getBody();
        $response = json_decode((string) $response, true);

        $thumb_url = Arr::get($response, 'items.0.volumeInfo.imageLinks.smallThumbnail');
        if ($thumb_url) {
            $thumb_url = str_replace('&edge=curl', '', $thumb_url);
            $thumb_url = str_replace('http://', 'https://', $thumb_url);
        }

        return $thumb_url;
    }

    public function coverFromIsbn($isbn)
    {
        return $this->coverFromGoogleBooks($isbn);
    }

    public function coverFromRecord($record)
    {
        $bsCover = Arr::get($record, 'thumbnails.bibsys');
        if ($bsCover) {
            $bsCover = str_replace('mini', 'stor', $bsCover);
            return $bsCover;
        }
        $isbns = Arr::get($record, 'isbns', []);
        if (!count($isbns)) {
            return $this->defaultCover;
        }

        $isbn = str_replace('-', '', $isbns[0]);

        return $this->coverFromIsbn($isbn) ?: $this->defaultCover;
    }

    public function getCover($id, Request $request)
    {
        $record = $this->getRecord($id, $request)['result'];

        $url = $this->coverFromRecord($record);

        return redirect($url);
    }
}
