<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Post;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        $this->view('wishlist/index',['title'=>'My Wishlist','posts'=>(new Wishlist())->forUser(Auth::id())]);
    }

    public function toggle(): void
    {
        Auth::requireLogin(); verify_csrf();
        $postId = (int)($_POST['post_id'] ?? 0);
        if (!(new Post())->findApproved($postId)) {
            flash('error','Destination post not found.'); redirect('explore');
        }
        $added = (new Wishlist())->toggle(Auth::id(),$postId);
        flash('success',$added ? 'Added to your wishlist.' : 'Removed from your wishlist.');
        $return = (string)($_POST['return'] ?? 'post');
        redirect($return==='wishlist' ? 'wishlist' : 'post', $return==='wishlist' ? [] : ['id'=>$postId]);
    }
}
