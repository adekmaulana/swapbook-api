<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Repositories\BookRepository;
use Illuminate\Http\Request;

class BookController extends Controller
{
    protected $bookRepository;

    public function __construct(BookRepository $bookRepository)
    {
        $this->bookRepository = $bookRepository;
    }

    public function createBook(Request $request)
    {
        return $this->bookRepository->createBook($request);
    }

    public function getBook(Post $post)
    {
        return $this->bookRepository->getBook($post);
    }

    public function getBooks(Request $request)
    {
        return $this->bookRepository->getBooks($request);
    }

    public function bookmark(Request $request)
    {
        return $this->bookRepository->bookmark($request);
    }
}
