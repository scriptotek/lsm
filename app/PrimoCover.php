<?php

namespace App;

use BCLib\PrimoServices\Availability\AlmaClient;
use BCLib\PrimoServices\PrimoServices;
use Illuminate\Http\Request;


class PrimoCover extends PrimoSearch
{
    protected $defaultCover;

    public function __construct(PrimoServices $primo, AlmaClient $alma)
    {
        parent::__construct($primo, $alma);
        $this->defaultCover = url('assets/no_cover.jpg');
    }

    protected function coverFromGoogleBooks($isbn)
    {
        $google_url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn;
        $response = json_decode(file_get_contents($google_url), true);
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
