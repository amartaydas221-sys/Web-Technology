<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\CalculatorLog;
use App\Models\Post;
use App\Models\PostRequest;
use App\Models\Wishlist;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();
        if (Auth::hasRole('admin')) redirect('admin/dashboard');

        $data = ['title'=>'Dashboard'];
        if (Auth::hasRole('scout')) {
            $requests = (new PostRequest())->byScout(Auth::id());
            $data['requests'] = $requests;
            $data['approvedCount'] = count(array_filter($requests, fn($r)=>$r['status']==='approved'));
            $data['pendingCount'] = count(array_filter($requests, fn($r)=>$r['status']==='pending'));
            $this->view('dashboard/scout',$data);
            return;
        }

        $data['wishlistCount'] = (new Wishlist())->countForUser(Auth::id());
        $data['recentCalculations'] = (new CalculatorLog())->recentForUser(Auth::id());
        $data['featured'] = (new Post())->approved([],3);
        $this->view('dashboard/user',$data);
    }
}
