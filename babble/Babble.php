<?php

namespace Babble;

use Symfony\Component\HttpFoundation\Request;

class Babble {
    public function __construct()
    {
        $request = Request::createFromGlobals();
        $loader = new ContentLoader();

        $model = $loader->matchPath($request->getPathInfo());
        $page = new Page($request, $model);
        echo $page->render();
    }

}
