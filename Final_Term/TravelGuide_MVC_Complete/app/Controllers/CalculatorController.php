<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\CalculatorLog;

class CalculatorController extends Controller
{
    public function index(): void
    {
        $this->view('calculator/index',['title'=>'Travel Cost Calculator']);
    }

    public function calculate(): void
    {
        verify_csrf();
        $data = [
            'destination'=>trim((string)($_POST['destination'] ?? '')),
            'travelers'=>max(1,(int)($_POST['travelers'] ?? 1)),
            'days'=>max(1,(int)($_POST['days'] ?? 1)),
            'transport'=>(float)($_POST['transport'] ?? 0),
            'accommodation'=>(float)($_POST['accommodation'] ?? 0),
            'food'=>(float)($_POST['food'] ?? 0),
            'other'=>(float)($_POST['other'] ?? 0),
        ];
        foreach (['transport','accommodation','food','other'] as $key) $data[$key]=max(0,$data[$key]);
        if ($data['destination']==='') json_response(['success'=>false,'message'=>'Destination is required.'],422);

        // transport and other are trip totals; accommodation is per night; food is per person/day.
        $total = $data['transport'] + ($data['accommodation'] * $data['days']) + ($data['food'] * $data['travelers'] * $data['days']) + $data['other'];
        (new CalculatorLog())->create(Auth::id(),$data,$total);
        json_response(['success'=>true,'total'=>round($total,2),'breakdown'=>[
            'transport'=>$data['transport'],
            'accommodation'=>$data['accommodation']*$data['days'],
            'food'=>$data['food']*$data['travelers']*$data['days'],
            'other'=>$data['other'],
        ]]);
    }
}
