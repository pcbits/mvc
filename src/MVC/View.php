<?php
namespace MVC;

use MVC\MVC;

class View
{
    protected $mvc;
    protected $viewPath;
    protected $url;
    public $title = null;
    public $description = null;

    public function __construct(MVC $mvc)
    {
        $this->mvc = $mvc;
        $this->viewPath = $mvc->get('viewPath');
        $this->url = $mvc->get('url');
    }

    public function render($file='', $vars='')
    {
        $_file = $file;
        if (is_array($vars)) {
            extract($vars);
        }
        ob_start();
        require($this->viewPath . $_file);
        return ob_get_clean();
    }

    protected function url()
    {
        return $this->url;
    }
}
