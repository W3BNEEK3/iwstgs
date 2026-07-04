<?php
namespace Src\Simulation\Presentation\Http\Controller\Admin;

use Illuminate\Http\Request;

class ScenarioController
{
    public function index() { return view('admin.scenarios.index'); }
    public function create() { return view('admin.scenarios.create'); }
    public function store() { return redirect()->back(); }
    public function edit() { return view('admin.scenarios.edit'); }
    public function update() { return redirect()->back(); }
    public function publish() { return redirect()->back(); }
    public function addMaterial() { return redirect()->back(); }
    public function removeMaterial() { return redirect()->back(); }
}
