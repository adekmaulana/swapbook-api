<?php

namespace App\Interfaces;

use App\Models\Post;
use Illuminate\Http\Request;

interface BookRepositoryInterface
{
    public function getBook(Post $post);
    public function getBooks(Request $request);
    public function createBook(Request $request);
    public function updateBook(Request $request, Post $post);
    public function deleteBook(Post $post);
    public function searchBooks(Request $request);
    public function getBookmarks(Request $request);
    public function bookmark(Request $request);
}
