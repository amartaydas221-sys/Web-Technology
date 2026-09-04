<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostRequest;
use App\Models\User;
use PDOException;

class AdminController extends Controller
{
    private function guard(): void { Auth::requireRole('admin'); }

    public function dashboard(): void
    {
        $this->guard();
        $users=new User(); $posts=new Post(); $requests=new PostRequest(); $comments=new Comment();
        $this->view('dashboard/admin',['title'=>'Administrator Dashboard','stats'=>[
            'users'=>$users->countAll(),
            'scouts'=>$users->countByRole('scout'),
            'approved_posts'=>$posts->countApproved(),
            'pending_requests'=>$requests->countStatus('pending'),
            'comments'=>$comments->countAll(),
        ]]);
    }

    public function users(): void
    {
        $this->guard();
        $this->view('admin/users',['title'=>'User Management','users'=>(new User())->all()]);
    }

    public function addUser(): void
    {
        $this->guard(); verify_csrf();
        $name=trim((string)($_POST['name']??''));
        $email=trim((string)($_POST['email']??''));
        $password=(string)($_POST['password']??'');
        $role=(string)($_POST['role']??'user');
        $verified=isset($_POST['is_verified'])?1:0;
        if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<8 || !in_array($role,['user','scout','admin'],true)) {
            flash('error','Provide a name, valid email, password of at least 8 characters and a valid role.'); redirect('admin/users');
        }
        try {
            (new User())->create($name,$email,$password,$role,$verified);
            flash('success','User added successfully.');
        } catch (PDOException $e) {
            flash('error',$e->getCode()==='23000'?'That email already exists.':'Could not add the user.');
        }
        redirect('admin/users');
    }

    public function verifyUser(): void
    {
        $this->guard(); verify_csrf();
        (new User())->verify((int)($_POST['id']??0));
        flash('success','User verified.'); redirect('admin/users');
    }

    public function deleteUser(): void
    {
        $this->guard(); verify_csrf();
        $id=(int)($_POST['id']??0);
        if ($id===Auth::id()) { flash('error','You cannot delete your own administrator account.'); redirect('admin/users'); }
        (new User())->delete($id);
        flash('success','User deleted.'); redirect('admin/users');
    }

    public function requests(): void
    {
        $this->guard();
        $this->view('admin/requests',['title'=>'Destination Post Requests','requests'=>(new PostRequest())->all()]);
    }

    public function reviewRequest(): void
    {
        $this->guard(); verify_csrf();
        $id=(int)($_POST['id']??0);
        $status=(string)($_POST['status']??'');
        $feedback=trim((string)($_POST['admin_feedback']??''));
        if (!in_array($status,['approved','rejected','change_requested'],true)) {
            flash('error','Invalid review status.'); redirect('admin/requests');
        }
        if (in_array($status,['rejected','change_requested'],true) && $feedback==='') {
            flash('error','Admin feedback is required for rejection or a change request.'); redirect('admin/requests');
        }
        $model=new PostRequest();
        $request=$model->find($id);
        if (!$request) { flash('error','Request not found.'); redirect('admin/requests'); }
        $model->setReview($id,$status,$feedback);
        $postModel = new Post();
        if ($status==='approved') {
            $request['id']=$id;
            $postModel->publishFromRequest($request);
        } else {
            // If an already-published request is sent back for changes/rejection, hide its public post until re-approved.
            $postModel->unpublishByRequest($id);
        }
        flash('success','Request status updated to ' . str_replace('_',' ',$status) . '.'); redirect('admin/requests');
    }

    public function posts(): void
    {
        $this->guard();
        $this->view('admin/posts',['title'=>'Post Management','posts'=>(new Post())->allAdmin()]);
    }

    public function editPost(): void
    {
        $this->guard();
        $id=(int)($_GET['id']??0);
        $post=(new Post())->findAny($id);
        if (!$post) { flash('error','Post not found.'); redirect('admin/posts'); }
        $this->view('admin/post_edit',['title'=>'Edit Destination Post','post'=>$post]);
    }

    public function updatePost(): void
    {
        $this->guard(); verify_csrf();
        $id=(int)($_POST['id']??0);
        $data=[
            'title'=>trim((string)($_POST['title']??'')),
            'short_history'=>trim((string)($_POST['short_history']??'')),
            'country'=>trim((string)($_POST['country']??'')),
            'category'=>trim((string)($_POST['category']??'')),
            'cost_level'=>trim((string)($_POST['cost_level']??'')),
            'image_url'=>trim((string)($_POST['image_url']??'')),
            'is_approved'=>isset($_POST['is_approved'])?1:0,
        ];
        if ($data['title']==='' || $data['short_history']==='' || $data['country']==='' || $data['category']==='' || !in_array($data['cost_level'],['Budget','Moderate','Expensive'],true)) {
            flash('error','Please complete all required post fields.'); redirect('admin/post/edit',['id'=>$id]);
        }
        (new Post())->update($id,$data);
        flash('success','Post updated.'); redirect('admin/posts');
    }

    public function deletePost(): void
    {
        $this->guard(); verify_csrf();
        (new Post())->delete((int)($_POST['id']??0));
        flash('success','Post deleted.'); redirect('admin/posts');
    }

    public function comments(): void
    {
        $this->guard();
        $this->view('admin/comments',['title'=>'Comment Management','comments'=>(new Comment())->all()]);
    }

    public function deleteComment(): void
    {
        $this->guard(); verify_csrf();
        (new Comment())->deleteAny((int)($_POST['id']??0));
        flash('success','Comment deleted by administrator.'); redirect('admin/comments');
    }
}
<level> AiUB Agent</level>
