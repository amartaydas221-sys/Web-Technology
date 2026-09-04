<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Wishlist;

class PostController extends Controller
{
    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $post = (new Post())->findApproved($id);
        if (!$post) { http_response_code(404); exit('Destination post not found.'); }
        $comments = (new Comment())->forPost($id);
        $wishlisted = Auth::check() ? (new Wishlist())->exists(Auth::id(),$id) : false;
        $this->view('posts/show',['title'=>$post['title'],'post'=>$post,'comments'=>$comments,'wishlisted'=>$wishlisted]);
    }
}
