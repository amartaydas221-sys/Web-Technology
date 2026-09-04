<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\PostRequest;

class ScoutController extends Controller
{
    private function guard(): void
    {
        Auth::requireRole('scout');
    }

    public function requests(): void
    {
        $this->guard();
        $this->view('scout/requests',['title'=>'My Destination Requests','requests'=>(new PostRequest())->byScout(Auth::id())]);
    }

    public function create(): void
    {
        $this->guard();
        if ((int)(Auth::user()['is_verified'] ?? 0)!==1) {
            flash('error','Your Scout account must be verified by an Administrator before submitting destination requests.');
            redirect('scout/requests');
        }
        $this->view('scout/form',['title'=>'Create Destination Post Request','request'=>null]);
    }

    public function store(): void
    {
        $this->guard(); verify_csrf();
        if ((int)(Auth::user()['is_verified'] ?? 0)!==1) {
            flash('error','Your Scout account is not verified.'); redirect('scout/requests');
        }
        $data = $this->payload();
        $errors = $this->validateRequest($data);
        if ($errors) { set_old($data); flash('error',implode(' ',$errors)); redirect('scout/request/create'); }
        (new PostRequest())->create(Auth::id(),$data);
        flash('success','Destination request submitted for administrator review.');
        redirect('scout/requests');
    }

    public function edit(): void
    {
        $this->guard();
        $id=(int)($_GET['id']??0);
        $request=(new PostRequest())->findForScout($id,Auth::id());
        if (!$request || !in_array($request['status'],['pending','change_requested','rejected'],true)) {
            flash('error','This request cannot be edited.'); redirect('scout/requests');
        }
        $this->view('scout/form',['title'=>'Edit / Resubmit Destination Request','request'=>$request]);
    }

    public function update(): void
    {
        $this->guard(); verify_csrf();
        $id=(int)($_POST['id']??0);
        $model=new PostRequest();
        $request=$model->findForScout($id,Auth::id());
        if (!$request || !in_array($request['status'],['pending','change_requested','rejected'],true)) {
            flash('error','This request cannot be updated.'); redirect('scout/requests');
        }
        $data=$this->payload();
        $errors=$this->validateRequest($data);
        if ($errors) { flash('error',implode(' ',$errors)); redirect('scout/request/edit',['id'=>$id]); }
        $model->updateForScout($id,Auth::id(),$data);
        flash('success','Request updated and resubmitted as pending.'); redirect('scout/requests');
    }

    public function delete(): void
    {
        $this->guard(); verify_csrf();
        $id=(int)($_POST['id']??0);
        $ok=(new PostRequest())->deleteForScout($id,Auth::id());
        flash($ok?'success':'error',$ok?'Request deleted.':'Approved requests cannot be deleted by Scouts.');
        redirect('scout/requests');
    }

    private function payload(): array
    {
        return [
            'title'=>trim((string)($_POST['title']??'')),
            'short_history'=>trim((string)($_POST['short_history']??'')),
            'country'=>trim((string)($_POST['country']??'')),
            'category'=>trim((string)($_POST['category']??'')),
            'cost_level'=>trim((string)($_POST['cost_level']??'')),
            'image_url'=>trim((string)($_POST['image_url']??'')),
        ];
    }

    private function validateRequest(array $data): array
    {
        $errors=$this->validateRequired($data,['title'=>'Title','short_history'=>'Short history','country'=>'Country','category'=>'Category','cost_level'=>'Cost level']);
        if (!in_array($data['cost_level'],['Budget','Moderate','Expensive'],true)) $errors[]='Choose a valid cost level.';
        if ($data['image_url']!=='' && !filter_var($data['image_url'],FILTER_VALIDATE_URL)) $errors[]='Image URL must be a valid URL or left blank.';
        return $errors;
    }
}
