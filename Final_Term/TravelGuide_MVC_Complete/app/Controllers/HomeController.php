<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

class HomeController extends Controller
{
    public function index(): void
    {
        $postModel = new Post();
        $this->view('home/index', [
            'title' => 'Home',
            'featured' => $postModel->approved([], 3),
        ]);
    }

    public function explore(): void
    {
        $filters = [
            'search' => trim((string)($_GET['search'] ?? '')),
            'country' => trim((string)($_GET['country'] ?? '')),
            'category' => trim((string)($_GET['category'] ?? '')),
            'cost_level' => trim((string)($_GET['cost_level'] ?? '')),
        ];
        $postModel = new Post();
        $this->view('home/explore', [
            'title' => 'Explore Destinations',
            'posts' => $postModel->approved($filters),
            'filters' => $filters,
            'countries' => $postModel->distinct('country'),
            'categories' => $postModel->distinct('category'),
            'costLevels' => $postModel->distinct('cost_level'),
        ]);
    }
}
