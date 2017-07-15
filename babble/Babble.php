<?php

namespace Babble;

use Symfony\Component\HttpFoundation\Request;

class Babble {
    public function __construct()
    {
        $request = Request::createFromGlobals();

        $model = ContentLoader::matchPath($request->getPathInfo());
        $page = new Page($request, $model);
        echo $page->render();

        $loader = new ContentLoader('post');
        var_export($loader->get());
    }

}
