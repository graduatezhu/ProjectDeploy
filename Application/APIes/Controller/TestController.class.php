<?php
namespace APIes\Controller;
use Think\Controller;
class TestController extends Controller {
    public function index(){
        $this->display();
    }
    
    public function test() {
        echo 'debug success';
    }
}