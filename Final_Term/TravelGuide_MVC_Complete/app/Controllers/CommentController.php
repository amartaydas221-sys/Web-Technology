<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Comment;
use App\Models\Post;

class CommentController extends Controller
{
    public function store(): void
    {
        Auth::requireLogin(); verify_csrf();
        $postId = (int)($_POST['post_id'] ?? 0);
        $content = trim((string)($_POST['content'] ?? ''));
        if (!(new Post())->findApproved($postId) || $content==='' || strlen($content)>1000) {
            flash('error','Please enter a valid comment (maximum 1000 characters).'); redirect('post',['id'=>$postId]);
        }
        (new Comment())->create(Auth::id(),$postId,$content);
        flash('success','Comment added.'); redirect('post',['id'=>$postId]);
    }

    public function deleteOwn(): void
    {
        Auth::requireLogin(); verify_csrf();
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $postId = (int)($_POST['post_id'] ?? 0);
        $ok = (new Comment())->deleteOwn($commentId,Auth::id());
        flash($ok ? 'success' : 'error',$ok ? 'Your comment was deleted.' : 'You can only delete your own comments.');
        redirect('post',['id'=>$postId]);
    }
}
