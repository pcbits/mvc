<?php
namespace MVC;

use MVC\MVC;
use MVC\View;

abstract class Controller
{
    protected $mvc;
    protected $url;
    protected $title = null;
    protected $description = null;
    protected $layout = 'default';
    protected $params = [];
    protected $post = [];
    protected $get = [];

    public function __construct(MVC $mvc, $params = [])
    {
        $this->mvc = $mvc;
        $this->url = $mvc->get('url');
        $this->params = $params;
        $this->post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $this->get = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
    }

    protected function render($file, array $vars = [])
    {
        $r = '';
        $view = new View($this->mvc);
        $view->title = $this->title;
        $view->description = $this->description;
        $r .= $view->render('layouts/' . $this->layout . '/header.php',
            $vars);
        $r .= $view->render($file, $vars);
        $r .= $view->render('layouts/' . $this->layout . '/footer.php',
            $vars);
        return $r;
    }

    protected function redirect($url)
    {
        header('Location: ' . $this->url . $url);
        exit;
    }
}
