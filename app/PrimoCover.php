<?php

namespace App;

use BCLib\PrimoServices\Availability\AlmaClient;
use BCLib\PrimoServices\PrimoServices;
use GuzzleHttp\Client as Http;
use Illuminate\Http\Request;


class PrimoCover extends PrimoSearch
{
    protected $defaultCover;
    protected $http;

    public function __construct(PrimoServices $primo, AlmaClient $alma, Http $http)
    {
        parent::__construct($primo, $alma);
        $this->defaultCover = url('assets/no_cover.jpg');
        $this->http = $http;
    }

    protected function coverFromGoogleBooks($isbn)
    {
        $res = $this->http->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
            'query' => [
                'q' => $isbn,
                'country' => 'NO',
            ]
        ]);

        $response = json_decode($res->getBody(), true);
        $thumb_url = array_get($response, 'items.0.volumeInfo.imageLinks.smallThumbnail');
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
        $bsCover = array_get($record, 'thumbnails.bibsys');
        if ($bsCover) {
            $bsCover = str_replace('mini', 'stor', $bsCover);
            return $bsCover;
        }
        $isbns = array_get($record, 'isbns', []);
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
